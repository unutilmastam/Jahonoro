<?php

declare(strict_types=1);

/*
 * API marshrutlari (ARCHITECTURE.md 4-bo'lim). Yo'llar /api prefiksisiz.
 */

use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Core\Router;

return static function (Router $r): void {
    $health = new HealthController();
    $auth = new AuthController();

    $r->get('/ping', [$health, 'ping']);

    $r->post('/auth/telegram', [$auth, 'telegram']);
    $r->get('/me', [$auth, 'me']);
};
