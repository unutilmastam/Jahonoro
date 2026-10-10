<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EventRepo;

/**
 * Mojaro: shu kuni boshqa faol to'y bor yoki vaqt oralig'i kesishadi.
 */
final class ConflictChecker
{
    public function __construct(private readonly EventRepo $events)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function find(string $eventDate, string $startsAt, ?int $durationMin, ?int $excludeId = null): array
    {
        return $this->events->conflicts(
            $eventDate,
            $startsAt,
            $durationMin !== null && $durationMin > 0 ? $durationMin : EventRepo::DEFAULT_DURATION_MIN,
            $excludeId
        );
    }
}
