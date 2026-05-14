<?php

declare(strict_types=1);

namespace App\Domains\Documents\Filament\Resources\FatturaResource\Pages;

use App\Domains\Documents\Filament\Exports\FatturaExporter;
use App\Domains\Documents\Filament\Resources\FatturaResource;
use App\Domains\Documents\Services\Internal\FatturaPaImporter;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListFatture extends ListRecords
{
    protected static string $resource = FatturaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->importFatturaPaAction(),
            Actions\ExportAction::make()->exporter(FatturaExporter::class),
            Actions\CreateAction::make(),
        ];
    }

    /**
     * Import outbound FatturaPA XMLs (the file you uploaded to / got
     * back from the SdI). One or many files; each is parsed, validated
     * for direction + totals, then idempotently persisted. Re-importing
     * the same file is a no-op — duplicate `(year, number)` rows are
     * skipped, not overwritten.
     */
    private function importFatturaPaAction(): Actions\Action
    {
        return Actions\Action::make('importFatturaPa')
            ->label(__('documents/labels.actions.import_xml'))
            ->icon('heroicon-o-arrow-up-tray')
            ->color('warning')
            ->modalHeading(__('documents/labels.actions.import_xml_heading'))
            ->modalDescription(__('documents/labels.actions.import_xml_description'))
            ->modalSubmitActionLabel(__('documents/labels.actions.import_xml_submit'))
            ->form([
                Forms\Components\FileUpload::make('xml_files')
                    ->label(__('documents/labels.actions.import_xml_files'))
                    ->multiple()
                    ->acceptedFileTypes(['text/xml', 'application/xml'])
                    ->storeFiles(false)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $importer = app(FatturaPaImporter::class);
                $payload = [];

                foreach ((array) $data['xml_files'] as $upload) {
                    $payload[] = [
                        'filename' => method_exists($upload, 'getClientOriginalName')
                            ? $upload->getClientOriginalName()
                            : 'upload.xml',
                        'contents' => $upload->get(),
                    ];
                }

                $results = $importer->importMany($payload, auth()->id());

                $imported = collect($results)->where('status', 'imported');
                $outbound = $imported->where('direction', 'outbound');
                $inbound = $imported->where('direction', 'inbound');
                $skipped = collect($results)->where('status', 'skipped');
                $failed = collect($results)->where('status', 'failed');

                $bodyLines = [];
                if ($outbound->isNotEmpty()) {
                    $bodyLines[] = __('documents/labels.actions.import_xml_outbound_count', [
                        'count' => $outbound->count(),
                    ]);
                }
                if ($inbound->isNotEmpty()) {
                    $bodyLines[] = __('documents/labels.actions.import_xml_inbound_count', [
                        'count' => $inbound->count(),
                    ]);
                }
                if ($skipped->isNotEmpty()) {
                    $bodyLines[] = __('documents/labels.actions.import_xml_skipped', [
                        'count' => $skipped->count(),
                    ]);
                }
                if ($failed->isNotEmpty()) {
                    $bodyLines[] = __('documents/labels.actions.import_xml_failed', [
                        'count' => $failed->count(),
                    ])
                    .":\n"
                    .$failed->map(fn ($r) => '• '.$r['filename'].' — '.$r['reason'])->implode("\n");
                }

                $notification = Notification::make()
                    ->title(__('documents/labels.actions.import_xml_success', [
                        'count' => $imported->count(),
                    ]))
                    ->body($bodyLines ? implode("\n\n", $bodyLines) : null);

                if ($failed->isNotEmpty()) {
                    $notification->danger()->persistent()->send();
                } else {
                    $notification->success()->send();
                }
            });
    }
}
