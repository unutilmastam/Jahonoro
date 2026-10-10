<?php

declare(strict_types=1);

/*
 * Yagona API kirish nuqtasi. .htaccess /api/* so'rovlarini shu yerga yo'naltiradi.
 */

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use App\Core\App;
use App\Core\Request;

App::handle(Request::fromGlobals())->send();
