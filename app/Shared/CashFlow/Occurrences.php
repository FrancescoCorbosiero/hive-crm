<?php

declare(strict_types=1);

namespace App\Shared\CashFlow;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Generator;
use LogicException;

final class Occurrences
{
    /**
     * Walk a schedule from its next pending date up to $until.
     *
     * $next must be the step the domain itself applies when an
     * occurrence is issued or logged, so projected dates are the dates
     * that will actually happen.
     *
     * @param  callable(CarbonImmutable): CarbonInterface  $next
     * @return Generator<int, CarbonImmutable>
     */
    public static function until(CarbonInterface $first, callable $next, CarbonImmutable $until): Generator
    {
        $date = CarbonImmutable::instance($first)->startOfDay();

        while ($date->lte($until)) {
            yield $date;

            $following = CarbonImmutable::instance($next($date))->startOfDay();

            if ($following->lte($date)) {
                throw new LogicException("Schedule step does not move forward from {$date->toDateString()}.");
            }

            $date = $following;
        }
    }
}
