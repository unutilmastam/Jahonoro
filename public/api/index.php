<?php

declare(strict_types=1);

/*
 * Yagona API kirish nuqtasi. .htaccess /api/* so'rovlarini shu yerga yo'naltiradi.
 */

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use App\Controllers\HealthController;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

$router = new Router();

$health = new HealthController();
$router->get('/ping', [$health, 'ping']);

try {
    $response = $router->dispatch(Request::fromGlobals());
} catch (Throwable $e) {
    Logger::error('unhandled_exception', [
        'type' => get_class($e),
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()) . ':' . $e->getLine(),
    ]);
    $response = Response::error('server_error', 'Serverda xatolik. Birozdan keyin qayta urinib koʻring.', 500);
}

$response->send();
