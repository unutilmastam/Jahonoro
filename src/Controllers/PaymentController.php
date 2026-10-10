<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Access;
use App\Core\HttpError;
use App\Core\Money;
use App\Core\Request;
use App\Core\Response;
use App\Core\Services;
use App\Core\Validator;
use Throwable;

/**
 * Avanslar — faqat owner. Izohlar va tarix a'zoga hech qachon chiqmaydi.
 */
final class PaymentController
{
    /**
     * GET /events/{id}/payments
     */
    public function index(Request $request): Response
    {
        Access::requireOwner($request);
        return Response::ok($this->summary((int) $request->param('id')));
    }

    /**
     * POST /events/{id}/payments  { amount, paid_on, note }
     */
    public function store(Request $request): Response
    {
        $user = Access::requireOwner($request);
        $eventId = (int) $request->param('id');

        $v = new Validator($request->json());
        $amount = $v->money('amount', true, true, 'Summa');
        $paidOn = $v->date('paid_on', false, 'Toʻlov sanasi') ?? date('Y-m-d');
        $note = $v->string('note', 255, false, 'Izoh') ?? '';
        $v->throwIfErrors();

        $pdo = Services::pdo();
        $pdo->beginTransaction();
        try {
            $event = Services::events()->find($eventId, true) ?? throw HttpError::notFound();
            Services::payments()->insert($eventId, (string) $amount, $paidOn, $note, $user['id']);
            Services::audit()->log($user['id'], 'payment.add', 'event', $eventId, ['title' => $event['title']]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return Response::ok($this->summary($eventId), 201);
    }

    /**
     * DELETE /events/{id}/payments/{payment_id} — xato kiritilgan avansni o'chirish.
     */
    public function destroy(Request $request): Response
    {
        $user = Access::requireOwner($request);
        $eventId = (int) $request->param('id');
        $paymentId = (int) $request->param('payment_id');

        $pdo = Services::pdo();
        $pdo->beginTransaction();
        try {
            $event = Services::events()->find($eventId, true) ?? throw HttpError::notFound();
            if (!Services::payments()->delete($eventId, $paymentId)) {
                throw HttpError::notFound('Avans topilmadi.');
            }
            Services::audit()->log($user['id'], 'payment.delete', 'event', $eventId, ['title' => $event['title']]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return Response::ok($this->summary($eventId));
    }

    /** @return array<string, mixed> */
    private function summary(int $eventId): array
    {
        $event = Services::events()->find($eventId) ?? throw HttpError::notFound();
        $items = [];
        $paid = 0;
        foreach (Services::payments()->listForEvent($eventId) as $row) {
            $paid += Money::toCents((string) $row['amount']);
            $items[] = [
                'id' => (int) $row['id'],
                'amount' => Money::fromCents(Money::toCents((string) $row['amount'])),
                'paid_on' => (string) $row['paid_on'],
                'note' => (string) $row['note'],
                'created_at' => (string) $row['created_at'],
            ];
        }
        $total = $event['total_price'] !== null ? Money::toCents((string) $event['total_price']) : null;
        return [
            'items' => $items,
            'total_price' => $total !== null ? Money::fromCents($total) : null,
            'paid' => Money::fromCents($paid),
            'balance' => $total !== null ? Money::fromCents($total - $paid) : null,
        ];
    }
}
