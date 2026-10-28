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
 *
 * The badges share one GROUP BY per request instead of running a COUNT
 * per tab on every Livewire round-trip (search, sort, paginate).
 */
trait HasStatusTabs
{
    /** @var array<string, int>|null */
    private ?array $statusTabCounts = null;

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
        $count = fn (array $values) => array_sum(array_intersect_key($this->statusTabCounts(), array_flip($values)));

        $tabs = [
            'all' => Tab::make()
                ->label(__('shared/tabs.all'))
                ->badge(fn () => array_sum($this->statusTabCounts())),
        ];

        if (! empty($open)) {
            $openValues = array_map(fn ($c) => $c->value, $open);
            $tabs['open'] = Tab::make()
                ->label(__('shared/tabs.open'))
                ->badge(fn () => $count($openValues))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn($column, $openValues));
        }

        foreach ($enum::cases() as $case) {
            $value = $case->value;
            $tabs[$value] = Tab::make()
                ->label($case->label())
                ->badge(fn () => $count([$value]))
                ->modifyQueryUsing(fn (Builder $query) => $query->where($column, $value));
        }

        return $tabs;
    }

    /**
     * Row count per status value, over the resource's base query.
     *
     * @return array<string, int>
     */
    private function statusTabCounts(): array
    {
        $column = $this->statusColumn();

        return $this->statusTabCounts ??= static::getResource()::getEloquentQuery()
            ->toBase()
            ->reorder()
            ->select($column)
            ->selectRaw('count(*) as aggregate')
            ->groupBy($column)
            ->pluck('aggregate', $column)
            ->map(fn ($aggregate) => (int) $aggregate)
            ->all();
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return ! empty($this->openStatuses()) ? 'open' : 'all';
    }
}
