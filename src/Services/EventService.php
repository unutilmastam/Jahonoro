<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\HttpError;
use App\Core\Validator;
use App\Repositories\AuditRepo;
use App\Repositories\EventRepo;
use PDO;
use Throwable;

/**
 * To'y yaratish, tahrirlash va holatini o'zgartirish. Har amal bitta tranzaksiyada:
 * to'y + eslatmalar + audit.
 */
final class EventService
{
    private const TEXT_LIMIT = 2000;

    public function __construct(
        private readonly PDO $pdo,
        private readonly EventRepo $events,
        private readonly ConflictChecker $conflicts,
        private readonly AuditRepo $audit,
        private readonly ?ReminderScheduler $reminders = null,
    ) {
    }

    /**
     * Forma ma'lumotini tekshiradi va bazaga tayyor massiv qaytaradi.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function validate(array $input): array
    {
        $v = new Validator($input);

        $data = [
            'title' => $v->string('title', 160, true, 'Sarlavha'),
            'event_type' => $v->enum('event_type', EventRepo::TYPES, 'kelin', 'Toʻy turi'),
            'event_date' => $v->date('event_date', true, 'Sana'),
            'start_time' => $v->time('start_time', true, 'Boshlanish vaqti'),
            'nahor_time' => $v->time('nahor_time', false, 'Nahor vaqti'),
            'abet_time' => $v->time('abet_time', false, 'Abet vaqti'),
            'assembly_time' => $v->time('assembly_time', false, 'Yigʻilish vaqti'),
            'departure_time' => $v->time('departure_time', false, 'Joʻnash vaqti'),
            'soundcheck_time' => $v->time('soundcheck_time', false, 'Soundcheck vaqti'),
            'duration_min' => $v->int('duration_min', 0, 1440, false, 'Davomiylik (daqiqa)'),
            'venue_name' => $v->string('venue_name', 160, false, 'Toʻyxona nomi') ?? '',
            'venue_address' => $v->string('venue_address', 255, false, 'Manzil') ?? '',
            'district' => $v->string('district', 100, false, 'Tuman') ?? '',
            'latitude' => $v->coordinate('latitude', 90, 'Kenglik'),
            'longitude' => $v->coordinate('longitude', 180, 'Uzunlik'),
            'location_verified' => $v->bool('location_verified') ? 1 : 0,
            'total_price' => $v->money('total_price', false, false, 'Kelishilgan narx'),
            'financial_visibility' => $v->enum('financial_visibility', ['inherit', 'private', 'members'], 'inherit', 'Moliya koʻrinishi'),
            'client_name' => $v->string('client_name', 128, false, 'Mijoz ismi') ?? '',
            'client_phone' => $v->string('client_phone', 32, false, 'Telefon') ?? '',
            'performance_notes' => $v->string('performance_notes', self::TEXT_LIMIT, false, 'Ijro izohi'),
            'equipment_notes' => $v->string('equipment_notes', self::TEXT_LIMIT, false, 'Jihozlar'),
            'dress_code' => $v->string('dress_code', 255, false, 'Dress code') ?? '',
            'schedule_notes' => $v->string('schedule_notes', self::TEXT_LIMIT, false, 'Vaqt jadvali izohi'),
            'internal_notes' => $v->string('internal_notes', self::TEXT_LIMIT, false, 'Ichki izoh'),
            'status' => $v->enum('status', ['planned', 'confirmed', 'completed'], 'planned', 'Holat'),
        ];

        if ($data['client_phone'] !== '' && !preg_match('/^[0-9+()\-\s]{5,32}$/', $data['client_phone'])) {
            $v->fail('client_phone', 'Telefon raqami notoʻgʻri. Masalan: +998 90 123 45 67.');
        }
        if (($data['latitude'] === null) !== ($data['longitude'] === null)) {
            $v->fail('latitude', 'Kenglik va uzunlik birga kiritilsin.');
        }
        if ($data['latitude'] === null) {
            $data['location_verified'] = 0;
        }

        $v->throwIfErrors();

        $data['starts_at'] = $data['event_date'] . ' ' . $data['start_time'] . ':00';
        return $data;
    }

    /**
     * @param array<string, mixed> $input
     * @return int yangi to'y ID
     */
    public function create(array $input, int $actorId, bool $confirmConflict): int
    {
        $data = $this->validate($input);
        $this->guardConflicts($data, null, $confirmConflict);

        return $this->transaction(function () use ($data, $actorId): int {
            $id = $this->events->insert($data, $actorId);
            $this->reminders?->sync($id);
            $this->audit->log($actorId, 'event.create', 'event', $id, $data);
            return $id;
        });
    }

    /** @param array<string, mixed> $input */
    public function update(int $id, array $input, int $actorId, bool $confirmConflict): void
    {
        $existing = $this->events->find($id) ?? throw HttpError::notFound();
        $data = $this->validate($input);
        if (in_array($existing['status'], ['cancelled', 'archived'], true)) {
            // Bekor qilingan/arxiv to'y holati faqat Tiklash orqali o'zgaradi
            $data['status'] = $existing['status'];
        }
        if (in_array($data['status'], EventRepo::ACTIVE_STATUSES, true)) {
            $this->guardConflicts($data, $id, $confirmConflict);
        }

        $this->transaction(function () use ($id, $data, $actorId): void {
            $current = $this->events->find($id, true) ?? throw HttpError::notFound();
            $changed = [];
            foreach ($data as $key => $value) {
                $old = $current[$key] ?? null;
                if (is_string($old) && str_ends_with($key, '_time')) {
                    $old = substr($old, 0, 5);
                }
                if ((string) ($old ?? '') !== (string) ($value ?? '')) {
                    $changed[] = $key;
                }
            }
            $this->events->update($id, $data);
            $this->reminders?->sync($id);
            $this->audit->log($actorId, 'event.update', 'event', $id, $data + ['changed' => $changed]);
        });
    }

    public function cancel(int $id, int $actorId): void
    {
        $this->changeStatus($id, $actorId, 'cancelled', ['planned', 'confirmed', 'completed'], 'event.cancel');
    }

    public function restore(int $id, int $actorId): void
    {
        $this->changeStatus($id, $actorId, 'planned', ['cancelled', 'archived'], 'event.restore');
    }

    public function archive(int $id, int $actorId): void
    {
        $this->changeStatus($id, $actorId, 'archived', ['planned', 'confirmed', 'completed', 'cancelled'], 'event.archive');
    }

    /** @param list<string> $allowedFrom */
    private function changeStatus(int $id, int $actorId, string $to, array $allowedFrom, string $action): void
    {
        $this->transaction(function () use ($id, $actorId, $to, $allowedFrom, $action): void {
            $event = $this->events->find($id, true) ?? throw HttpError::notFound();
            if (!in_array($event['status'], $allowedFrom, true)) {
                throw new HttpError(409, 'invalid_status', 'Bu toʻy holatida bu amalni bajarib boʻlmaydi.');
            }
            $this->events->setStatus($id, $to);
            if ($to === 'planned') {
                $this->reminders?->sync($id);
            } else {
                $this->reminders?->cancelForEvent($id);
            }
            $this->audit->log($actorId, $action, 'event', $id, [
                'title' => $event['title'],
                'event_date' => $event['event_date'],
                'from_status' => $event['status'],
                'to_status' => $to,
            ]);
        });
    }

    /** @param array<string, mixed> $data */
    private function guardConflicts(array $data, ?int $excludeId, bool $confirmed): void
    {
        if ($confirmed) {
            return;
        }
        $found = $this->conflicts->find($data['event_date'], $data['starts_at'], $data['duration_min'], $excludeId);
        if ($found !== []) {
            $guard = new FinanceGuard(false);
            throw new HttpError(409, 'conflict', 'Bu kunda yana ' . count($found) . ' ta toʻy bor.', [
                'conflicts' => $guard->presentRows($found),
            ]);
        }
    }

    /**
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    private function transaction(callable $fn): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $fn();
            $this->pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
