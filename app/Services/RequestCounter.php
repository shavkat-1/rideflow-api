<?php

declare(strict_types=1);

namespace App\Services;

final class RequestCounter
{
    private static int $count = 0;

    public function increment(): int
    {
        return ++self::$count;
    }
}
