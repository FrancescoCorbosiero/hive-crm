<?php

declare(strict_types=1);

namespace App\Domains\Repositories\Enums;

/**
 * Git hosting provider. Pure cosmetic — the canonical identifier of a
 * repository in this app is its URL. The provider mostly drives the
 * icon / colour in the list and the badge label.
 */
enum RepositoryProvider: string
{
    case Github = 'github';
    case Gitlab = 'gitlab';
    case Bitbucket = 'bitbucket';
    case Other = 'other';

    public function label(): string
    {
        return __('repositories/provider.'.$this->value);
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $c) {
            $out[$c->value] = $c->label();
        }

        return $out;
    }

    /**
     * Best-effort guess from a repo URL. Used when the form leaves
     * provider blank — the user can override.
     */
    public static function detect(string $url): self
    {
        $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));

        return match (true) {
            str_contains($host, 'github.com') => self::Github,
            str_contains($host, 'gitlab.com') => self::Gitlab,
            str_contains($host, 'bitbucket.org') => self::Bitbucket,
            default => self::Other,
        };
    }
}
