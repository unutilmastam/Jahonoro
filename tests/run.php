<?php

declare(strict_types=1);

/*
 * Testlarni ishga tushirish: php tests/run.php
 * tests/ dagi har bir *Test.php fayli test(nom, funksiya) chaqiruvlarini qiladi.
 */

require dirname(__DIR__) . '/src/bootstrap.php';

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

foreach (glob(__DIR__ . '/*Test.php') ?: [] as $file) {
    require $file;
}

$failed = 0;
foreach ($GLOBALS['__tests'] as [$name, $fn]) {
    try {
        $fn();
        echo "  ok    {$name}\n";
    } catch (Throwable $e) {
        $failed++;
        echo "  FAIL  {$name}\n      " . $e->getMessage() . "\n";
    }
}

$total = count($GLOBALS['__tests']);
echo "\n" . ($total - $failed) . "/{$total} test o'tdi\n";
exit($failed === 0 ? 0 : 1);
