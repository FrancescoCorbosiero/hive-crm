<?php

declare(strict_types=1);

namespace App\Domains\Mail\Filament\Pages;

use App\Domains\Contacts\Services\Public\ContactsService;
use App\Domains\Mail\Services\Public\MailService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class MailTestPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static string $view = 'filament.pages.mail-test';

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.mail');
    }

    public static function getNavigationLabel(): string
    {
        return __('mail/test.page_title');
    }

    public function getTitle(): string
    {
        return __('mail/test.page_title');
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('recipients')
                    ->label(__('mail/test.fields.recipients'))
                    ->helperText(__('mail/test.fields.recipients_help'))
                    ->multiple()
                    ->required()
                    ->getSearchResultsUsing(fn (string $search) => app(ContactsService::class)
                        ->searchMailable($search, 20)
                        ->mapWithKeys(fn ($dto) => [$dto->email => "{$dto->name} <{$dto->email}>"])
                        ->all())
                    ->getOptionLabelsUsing(fn (array $values) => collect($values)->mapWithKeys(fn ($v) => [$v => $v])->all())
                    ->createOptionForm([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required(),
                    ])
                    ->createOptionUsing(fn (array $data): string => $data['email']),

                Forms\Components\TextInput::make('subject')
                    ->label(__('mail/test.fields.subject'))
                    ->required(),

                Forms\Components\RichEditor::make('body')
                    ->label(__('mail/test.fields.body'))
                    ->required(),
            ])
            ->statePath('data');
    }

    public function send(): void
    {
        $state = $this->form->getState();

        try {
            foreach ($state['recipients'] as $email) {
                app(MailService::class)->sendTest($email, $state['subject'], $state['body']);
            }

            Notification::make()
                ->success()
                ->title(__('mail/test.success'))
                ->send();

            $this->form->fill();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title(__('mail/test.failure'))
                ->body($e->getMessage())
                ->send();
        }
    }
}
