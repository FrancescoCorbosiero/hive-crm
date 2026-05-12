<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Registry;

use Illuminate\Support\Facades\Artisan;

/**
 * Whitelist of artisan commands that may be scheduled via the UI.
 *
 * Adding a command here is an explicit security decision — the
 * scheduler must never allow arbitrary command execution from the
 * Filament resource. Each entry advertises a human label and the
 * default cadence used when the row is seeded for the first time.
 *
 * Domains register their own schedulable commands by extending this
 * registry from their ServiceProvider boot() method (see
 * SchedulingServiceProvider for the canonical system list).
 */
class ScheduledCommandRegistry
{
    /**
     * @var array<string, array{label: string, description: ?string, default_cron: ?string, is_system: bool}>
     */
    protected static array $commands = [];

    public static function register(
        string $command,
        string $label,
        ?string $description = null,
        ?string $defaultCron = null,
        bool $isSystem = false,
    ): void {
        static::$commands[$command] = [
            'label' => $label,
            'description' => $description,
            'default_cron' => $defaultCron,
            'is_system' => $isSystem,
        ];
    }

    public static function has(string $command): bool
    {
        return array_key_exists($command, static::$commands);
    }

    /**
     * @return array<string, array{label: string, description: ?string, default_cron: ?string, is_system: bool}>
     */
    public static function all(): array
    {
        return static::$commands;
    }

    /**
     * Subset that should be seeded into the scheduled_tasks table on
     * first install / on migration.
     *
     * @return array<string, array{label: string, description: ?string, default_cron: ?string, is_system: bool}>
     */
    public static function systemDefaults(): array
    {
        return array_filter(
            static::$commands,
            fn (array $meta) => $meta['is_system'] && $meta['default_cron'] !== null,
        );
    }

    /**
     * @return array<string, string> Map of artisan signature → label
     *                                for use as a Filament Select.
     */
    public static function options(): array
    {
        $opts = [];
        foreach (static::$commands as $signature => $meta) {
            $opts[$signature] = $meta['label'].' ('.$signature.')';
        }
        ksort($opts);

        return $opts;
    }

    /**
     * Verifies the signature is actually a registered Artisan command.
     * Use this when the whitelist is loaded before Artisan boots — eg.
     * during console.php registration, prefer has() instead.
     */
    public static function existsInArtisan(string $signature): bool
    {
        return array_key_exists($signature, Artisan::all());
    }

    public static function reset(): void
    {
        static::$commands = [];
    }
}
