<?php

declare(strict_types=1);

/*
 * Testlarni ishga tushirish: php tests/run.php
 * tests/ dagi har bir *Test.php fayli test(nom, funksiya) chaqiruvlarini qiladi.
 * MySQL kerak bo'lgan testlar baza bo'lmasa "skip" bo'ladi.
 */

putenv('JAHONORO_CONFIG=' . __DIR__ . '/config.test.php');

require dirname(__DIR__) . '/src/bootstrap.php';
require __DIR__ . '/helpers.php';

$GLOBALS['__tests'] = [];

function test(string $name, callable $fn): void
{
    $GLOBALS['__tests'][] = [$name, $fn];
}

function assert_same(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(sprintf(
            "%sKutilgan: %s\n      Olingan: %s",
            $message !== '' ? $message . "\n      " : '',
            var_export($expected, true),
            var_export($actual, true)
        ));
    }
}

function assert_true(bool $condition, string $message = 'Shart bajarilmadi'): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$filter = $argv[1] ?? '';
foreach (glob(__DIR__ . '/*Test.php') ?: [] as $file) {
    if ($filter === '' || str_contains(basename($file), $filter)) {
        require $file;
    }
}

$failed = 0;
$skipped = 0;
foreach ($GLOBALS['__tests'] as [$name, $fn]) {
    try {
        $fn();
        echo "  ok    {$name}\n";
    } catch (SkipTest $e) {
        $skipped++;
        echo "  skip  {$name} ({$e->getMessage()})\n";
    } catch (Throwable $e) {
        $failed++;
        echo "  FAIL  {$name}\n      " . $e->getMessage() . "\n";
        if (!$e instanceof RuntimeException || get_class($e) !== RuntimeException::class) {
            echo '      ' . basename($e->getFile()) . ':' . $e->getLine() . "\n";
        }
    }
}

$total = count($GLOBALS['__tests']);
echo "\n" . ($total - $failed - $skipped) . "/{$total} test o'tdi" . ($skipped > 0 ? ", {$skipped} skip" : '') . "\n";
exit($failed === 0 ? 0 : 1);
