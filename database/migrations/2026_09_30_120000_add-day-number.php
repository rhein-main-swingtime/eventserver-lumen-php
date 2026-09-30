<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDayNumber extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('event_instances', function (Blueprint $table) {
            $table->unsignedSmallInteger('day_number')->nullable();
            $table->unsignedSmallInteger('day_count')->nullable();
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
            $table->dropColumn(['day_number', 'day_count']);
        });
    }
}
