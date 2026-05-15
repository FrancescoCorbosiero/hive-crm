<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trello integration — minimum-viable shape. A nullable URL column on
 * the records most likely to own a board (a customer or a website you
 * built). Pure link-out: no API calls, no token storage, no mirror.
 * If we ever want richer Trello data we can add a TrelloService and a
 * cards mirror later — this column survives that change unchanged.
 *
 * Lives in database/migrations (not a domain folder) because it spans
 * two domains; the alternative would be two near-identical migrations
 * in Contacts/ and Websites/, which is more noise than this is worth.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('trello_board_url')->nullable()->after('notes');
        });

        Schema::table('websites', function (Blueprint $table) {
            $table->string('trello_board_url')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('trello_board_url');
        });

        Schema::table('websites', function (Blueprint $table) {
            $table->dropColumn('trello_board_url');
        });
    }
};
