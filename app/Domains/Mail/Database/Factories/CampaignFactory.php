<?php

declare(strict_types=1);

namespace App\Domains\Mail\Database\Factories;

use App\Domains\Mail\Enums\CampaignStatus;
use App\Domains\Mail\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        $name = fake()->randomElement([
            'Newsletter mensile',
            'Lancio nuovo servizio',
            'Sconto per Pasqua',
            'Aggiornamento di prodotto',
        ]);

        return [
            'name' => $name,
            'subject' => [
                'it' => 'Novità da Hive — '.$name,
                'en' => 'News from Hive — '.$name,
            ],
            'body_html' => [
                'it' => '<h1>Ciao {{name}},</h1><p>Grazie di seguirci!</p>',
                'en' => '<h1>Hi {{name}},</h1><p>Thanks for following us!</p>',
            ],
            'status' => CampaignStatus::Draft->value,
            'scheduled_at' => null,
            'sent_at' => null,
            'owner_user_id' => null,
        ];
    }
}
