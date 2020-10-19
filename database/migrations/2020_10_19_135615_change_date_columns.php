<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDateColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('calendar_events', function(Blueprint $table) {
            $table->renameColumn('startDateTime', 'start_date_time');
            $table->renameColumn('endDateTime', 'end_date_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('calendar_events', function(Blueprint $table) {
            $table->renameColumn('start_date_time', 'startDateTime');
            $table->renameColumn('end_date_time', 'endDateTime');
        });
    }
}
