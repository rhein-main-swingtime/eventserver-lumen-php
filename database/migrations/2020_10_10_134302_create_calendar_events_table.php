<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCalendarEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->string('id');
            $table->string('summary', 1200);
            $table->text('description')->nullable();
            $table->dateTime('startDateTime');
            $table->dateTime('endDateTime');
            $table->dateTime('updated');
            $table->dateTime('created');
            $table->string('creator');
            $table->string('calendar');
            $table->string('location')->nullable();
            $table->string('category')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('calendar_events');
    }
}
