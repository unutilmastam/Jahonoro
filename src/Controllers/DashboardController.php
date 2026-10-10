<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Access;
use App\Core\Money;
use App\Core\Request;
use App\Core\Response;
use App\Core\Services;

final class DashboardController
{
    /**
     * GET /dashboard — keyingi to'y, hafta/oy soni, shu hafta ro'yxati, so'nggi o'zgarishlar;
     * owner'ga qoldiq yig'indisi va kutayotgan a'zolar.
     */
    public function index(Request $request): Response
    {
        $user = Access::requireUser($request);
        $events = Services::events();
        $guard = Services::financeGuard();

        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', (int) strtotime('monday this week'));
        $weekEnd = date('Y-m-d', (int) strtotime('sunday this week'));
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');

        $next = $events->next();
        $nextPresented = null;
        if ($next !== null) {
            $nextPresented = $guard->present($next, $user, Services::payments()->paidCents((int) $next['id']));
        }

        $data = [
            'now' => date('c'),
            'user' => ['name' => $user['display_name'], 'role' => $user['role']],
            'next' => $nextPresented,
            'counts' => [
                'week' => $events->countBetween($weekStart, $weekEnd),
                'month' => $events->countBetween($monthStart, $monthEnd),
                'upcoming' => $events->countUpcoming(),
            ],
            'week' => $guard->presentRows($events->between($today, $weekEnd, ['planned', 'confirmed', 'cancelled'])),
            'recent' => Services::audit()->recentEventChanges(5),
        ];

        if (Access::isOwner($user)) {
            $debt = Services::payments()->outstanding();
            $data['debt'] = [
                'balance' => Money::fromCents($debt['balance_cents']),
                'events' => $debt['events'],
            ];
            $data['pending_members'] = Services::users()->countPending();
            $data['group_linked'] = Services::groupChatId() !== '';
        }

        return Response::ok($data);
    }
}
