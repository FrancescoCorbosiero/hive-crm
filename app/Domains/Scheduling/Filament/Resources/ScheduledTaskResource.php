<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Filament\Resources;

use App\Domains\Scheduling\Filament\Resources\ScheduledTaskResource\Pages;
use App\Domains\Scheduling\Models\ScheduledTask;
use App\Domains\Scheduling\Registry\ScheduledCommandRegistry;
use Cron\CronExpression;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;

class ScheduledTaskResource extends Resource
{
    protected static ?string $model = ScheduledTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 90;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('scheduling/labels.plural');
    }

    public static function getModelLabel(): string
    {
        return __('scheduling/labels.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('scheduling/labels.plural');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('scheduling/labels.section.task'))
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('scheduling/labels.name'))
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    Forms\Components\Select::make('command')
                        ->label(__('scheduling/labels.command'))
                        ->options(fn () => ScheduledCommandRegistry::options())
                        ->searchable()
                        ->required()
                        ->disabled(fn (?ScheduledTask $record) => $record?->is_system === true)
                        ->dehydrated()
                        ->helperText(__('scheduling/labels.command_help')),

                    Forms\Components\TextInput::make('cron_expression')
                        ->label(__('scheduling/labels.cron_expression'))
                        ->required()
                        ->placeholder('*/15 * * * *')
                        ->helperText(__('scheduling/labels.cron_help'))
                        ->rule(function () {
                            return function (string $attribute, mixed $value, \Closure $fail): void {
                                if (! CronExpression::isValidExpression((string) $value)) {
                                    $fail(__('scheduling/labels.cron_invalid'));
                                }
                            };
                        }),

                    Forms\Components\TextInput::make('timezone')
                        ->label(__('scheduling/labels.timezone'))
                        ->placeholder(config('app.timezone'))
                        ->maxLength(64),

                    Forms\Components\Textarea::make('description')
                        ->label(__('scheduling/labels.description'))
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make(__('scheduling/labels.section.execution'))
                ->columns(3)
                ->schema([
                    Forms\Components\Toggle::make('is_enabled')
                        ->label(__('scheduling/labels.is_enabled'))
                        ->default(true),

                    Forms\Components\Toggle::make('without_overlapping')
                        ->label(__('scheduling/labels.without_overlapping'))
                        ->default(true)
                        ->helperText(__('scheduling/labels.without_overlapping_help')),

                    Forms\Components\Toggle::make('on_one_server')
                        ->label(__('scheduling/labels.on_one_server'))
                        ->default(true)
                        ->helperText(__('scheduling/labels.on_one_server_help')),
                ]),

            Forms\Components\Section::make(__('scheduling/labels.section.last_run'))
                ->collapsed()
                ->visible(fn (?ScheduledTask $record) => $record?->last_started_at !== null)
                ->schema([
                    Forms\Components\Placeholder::make('last_started_at')
                        ->label(__('scheduling/labels.last_started_at'))
                        ->content(fn (ScheduledTask $record) => $record->last_started_at?->format('d/m/Y H:i:s') ?? '—'),
                    Forms\Components\Placeholder::make('last_finished_at')
                        ->label(__('scheduling/labels.last_finished_at'))
                        ->content(fn (ScheduledTask $record) => $record->last_finished_at?->format('d/m/Y H:i:s') ?? '—'),
                    Forms\Components\Placeholder::make('last_exit_code')
                        ->label(__('scheduling/labels.last_exit_code'))
                        ->content(fn (ScheduledTask $record) => $record->last_exit_code !== null ? (string) $record->last_exit_code : '—'),
                    Forms\Components\Placeholder::make('duration')
                        ->label(__('scheduling/labels.duration'))
                        ->content(function (ScheduledTask $record): string {
                            $d = $record->durationSeconds();
                            return $d !== null ? $d.'s' : '—';
                        }),
                    Forms\Components\Textarea::make('last_output')
                        ->label(__('scheduling/labels.last_output'))
                        ->rows(10)
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('scheduling/labels.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('command')
                    ->label(__('scheduling/labels.command'))
                    ->fontFamily('mono')
                    ->copyable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('cron_expression')
                    ->label(__('scheduling/labels.cron_expression'))
                    ->fontFamily('mono'),
                Tables\Columns\IconColumn::make('is_system')
                    ->label(__('scheduling/labels.is_system_short'))
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-pencil')
                    ->toggleable(),
                Tables\Columns\ToggleColumn::make('is_enabled')
                    ->label(__('scheduling/labels.is_enabled_short')),
                Tables\Columns\TextColumn::make('next_run')
                    ->label(__('scheduling/labels.next_run'))
                    ->getStateUsing(fn (ScheduledTask $r) => $r->nextRunAt()?->format('d/m/Y H:i') ?? '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('last_started_at')
                    ->label(__('scheduling/labels.last_run'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('last_status')
                    ->label(__('scheduling/labels.last_status'))
                    ->getStateUsing(fn (ScheduledTask $r) => $r->lastStatus())
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'success' => 'success',
                        'failure' => 'danger',
                        'running' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => __('scheduling/labels.status.'.$state)),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_enabled')
                    ->label(__('scheduling/labels.is_enabled')),
                Tables\Filters\TernaryFilter::make('is_system')
                    ->label(__('scheduling/labels.is_system')),
            ])
            ->actions([
                self::runNowAction(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (ScheduledTask $r) => ! $r->is_system),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->reject(fn (ScheduledTask $r) => $r->is_system)
                                ->each(fn (ScheduledTask $r) => $r->delete());
                        }),
                ]),
            ])
            ->defaultSort('name');
    }

    /**
     * Runs the underlying artisan command synchronously and stores the
     * outcome on the row. Skips Laravel's Scheduler entirely — this is
     * a one-shot kick, not a real schedule tick.
     */
    private static function runNowAction(): Action
    {
        return Action::make('runNow')
            ->label(__('scheduling/labels.run_now'))
            ->icon('heroicon-o-play')
            ->requiresConfirmation()
            ->action(function (ScheduledTask $record): void {
                if (! ScheduledCommandRegistry::has($record->command)) {
                    Notification::make()
                        ->danger()
                        ->title(__('scheduling/labels.run_now_unknown'))
                        ->send();
                    return;
                }

                $record->update([
                    'last_started_at' => now(),
                    'last_finished_at' => null,
                    'last_exit_code' => null,
                    'last_output' => null,
                ]);

                try {
                    $exitCode = Artisan::call($record->command);
                    $output = Artisan::output();

                    $record->update([
                        'last_finished_at' => now(),
                        'last_exit_code' => $exitCode,
                        'last_output' => mb_substr($output, -\App\Domains\Scheduling\Services\ScheduleLoader::OUTPUT_LIMIT),
                    ]);

                    Notification::make()
                        ->color($exitCode === 0 ? 'success' : 'danger')
                        ->title(__('scheduling/labels.run_now_done', ['code' => $exitCode]))
                        ->send();
                } catch (\Throwable $e) {
                    $record->update([
                        'last_finished_at' => now(),
                        'last_exit_code' => 1,
                        'last_output' => $e->getMessage(),
                    ]);

                    Notification::make()
                        ->danger()
                        ->title(__('scheduling/labels.run_now_failed'))
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScheduledTasks::route('/'),
            'create' => Pages\CreateScheduledTask::route('/create'),
            'edit' => Pages\EditScheduledTask::route('/{record}/edit'),
        ];
    }
}
