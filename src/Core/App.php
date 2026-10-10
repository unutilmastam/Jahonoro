<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * So'rovni marshrutga uzatadi va xatolarni JSON javobga aylantiradi.
 */
final class App
{
    private static ?Router $router = null;

    public static function router(): Router
    {
        if (self::$router === null) {
            $router = new Router();
            $register = require APP_ROOT . '/src/routes.php';
            $register($router);
            self::$router = $router;
        }
        return self::$router;
    }

    public static function handle(Request $request): Response
    {
        try {
            return self::router()->dispatch($request);
        } catch (HttpError $e) {
            return $e->toResponse();
        } catch (Throwable $e) {
            Logger::error('unhandled_exception', [
                'type' => get_class($e),
                'error' => $e->getMessage(),
                'file' => basename($e->getFile()) . ':' . $e->getLine(),
                'path' => $request->path,
            ]);
            return Response::error('server_error', 'Serverda xatolik. Birozdan keyin qayta urinib ko‘ring.', 500);
        }
    }
}
