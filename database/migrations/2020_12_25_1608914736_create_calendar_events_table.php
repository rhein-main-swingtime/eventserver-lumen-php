<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateCalendarEventsTable extends Migration
{
    public function up()
    {
        Schema::create('calendar_events', function (Blueprint $table) {

		$table->string('event_id');
		$table->datetime('updated')->nullable();
		$table->datetime('created')->nullable();
		$table->string('creator');
		$table->string('calendar');
		$table->string('category')->nullable()->default('NULL');
		$table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
		$table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP'));
		$table->text('recurrence')->nullable()->default('NULL');

        });
    }

    public function down()
    {
        Schema::dropIfExists('calendar_events');
    }
}