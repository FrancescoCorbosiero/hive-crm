<?php

use App\Domains\Calendar\Filament\Resources\CalendarEventResource\Pages\ListCalendarEvents;
use App\Domains\Calendar\Filament\Widgets\TodayCalendarWidget;
use App\Domains\Calendar\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('renders the calendar events index page', function () {
    CalendarEvent::factory()->count(3)->create();
    Livewire::test(ListCalendarEvents::class)->assertSuccessful();
});

it('renders the today widget without making any HTTP calls', function () {
    // Block all outgoing HTTP — the widget MUST read from the local table.
    Http::preventStrayRequests();

    CalendarEvent::factory()->today()->create();
    CalendarEvent::factory()->today()->create();

    Livewire::test(TodayCalendarWidget::class)->assertSuccessful();
});
