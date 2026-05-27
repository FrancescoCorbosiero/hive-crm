<?php

declare(strict_types=1);

namespace App\Shared\Filament\Concerns;

use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * Adds a row of status tabs above a Filament ListRecords table.
 *
 * One tab per case of the chosen enum, plus an "All" tab and (when the
 * enum exposes an isOpen() method) an "Open" meta-tab that excludes
 * closed cases. Each tab carries a numeric badge with the matching count.
 */
trait HasStatusTabs
{
    /** @return class-string<\BackedEnum> */
    abstract protected function statusEnum(): string;

    protected function statusColumn(): string
    {
        return 'status';
    }

    /** @return array<int, \BackedEnum> */
    protected function openStatuses(): array
    {
        $cases = ($this->statusEnum())::cases();
        $first = $cases[0] ?? null;

        if ($first !== null && method_exists($first, 'isOpen')) {
            return array_values(array_filter($cases, fn ($c) => $c->isOpen()));
        }

        return [];
    }

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        $enum = $this->statusEnum();
        $column = $this->statusColumn();
        $open = $this->openStatuses();
        $base = fn () => static::getResource()::getEloquentQuery();

        $tabs = [
            'all' => Tab::make()
                ->label(__('shared/tabs.all'))
                ->badge(fn () => $base()->count()),
        ];

        if (! empty($open)) {
            $openValues = array_map(fn ($c) => $c->value, $open);
            $tabs['open'] = Tab::make()
                ->label(__('shared/tabs.open'))
                ->badge(fn () => $base()->whereIn($column, $openValues)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn($column, $openValues));
        }

        foreach ($enum::cases() as $case) {
            $value = $case->value;
            $tabs[$value] = Tab::make()
                ->label($case->label())
                ->badge(fn () => $base()->where($column, $value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where($column, $value));
        }

        return $tabs;
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return ! empty($this->openStatuses()) ? 'open' : 'all';
    }
}
