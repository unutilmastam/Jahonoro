<?php

declare(strict_types=1);

// Eslatmalar ishchisi. cPanel cron har 5 daqiqada:
//   */5 * * * * /opt/cpanel/ea-php82/root/usr/bin/php /home/USER/jahonoro/cron/reminders.php >/dev/null 2>&1
// Qo'lda sinash: php cron/reminders.php -v

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Logger;
use App\Core\Services;

$verbose = in_array('-v', $argv ?? [], true);

$lockDir = APP_ROOT . '/storage';
$lock = @fopen($lockDir . '/cron.lock', 'c');
if ($lock === false) {
    Logger::error('cron_lock_open_failed');
    exit(1);
}
if (!flock($lock, LOCK_EX | LOCK_NB)) {
    if ($verbose) {
        echo "Boshqa nusxa ishlayapti — chiqildi.\n";
    }
    exit(0);
}

$exit = 0;
try {
    $stats = Services::reminders()->processDue();
    Services::sessions()->purgeExpired();
    Services::settings()->set('cron_last_run', date('Y-m-d H:i:s'));
    if ($stats['claimed'] > 0 || $stats['released'] > 0) {
        Logger::info('cron_reminders', $stats);
    }
    if ($verbose) {
        echo json_encode($stats, JSON_UNESCAPED_UNICODE) . "\n";
    }
} catch (Throwable $e) {
    Logger::error('cron_failed', ['type' => get_class($e), 'error' => $e->getMessage(), 'file' => basename($e->getFile()) . ':' . $e->getLine()]);
    if ($verbose) {
        echo 'Xato: ' . Logger::redact($e->getMessage()) . "\n";
    }
    $exit = 1;
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
exit($exit);
