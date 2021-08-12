<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DatesToStrings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('event_instances', function (Blueprint $table) {
            $table->decimal('start_date_time_offset')->default(0);
            $table->decimal('end_date_time_offset')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('event_instances', function (Blueprint $table) {
            $table->removeColumn('start_date_time_offset');
            $table->removeColumn('start_date_time_offset');
        });
    }
}
