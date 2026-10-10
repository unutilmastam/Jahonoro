<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Access;
use App\Core\HttpError;
use App\Core\Request;
use App\Core\Response;
use App\Core\Services;

final class CalendarController
{
    /**
     * GET /calendar?month=2026-10 — oy bo'yicha to'ylar (arxivsiz, moliyasiz).
     */
    public function month(Request $request): Response
    {
        Access::requireUser($request);
        $month = (string) $request->query('month', date('Y-m'));
        if (!preg_match('/^(20\d{2})-(0[1-9]|1[0-2])$/', $month)) {
            throw new HttpError(422, 'validation', 'Oy notoʻgʻri. Masalan: 2026-10.');
        }
        $from = $month . '-01';
        $to = date('Y-m-t', (int) strtotime($from));

        return Response::ok([
            'month' => $month,
            'items' => Services::financeGuard()->presentRows(Services::events()->between($from, $to)),
        ]);
    }
}
