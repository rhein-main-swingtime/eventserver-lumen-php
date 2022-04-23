<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateEventInstancesTable extends Migration
{
    public function up()
    {
        Schema::create('event_instances', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->string('event_id');
            $table->string('instance_id')->unique();
            $table->string('summary', 1200);
            $table->text('description')->nullable()->default('NULL');
            $table->datetime('start_date_time');
            $table->datetime('end_date_time');
            $table->string('location')->nullable()->default('NULL');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->text('city')->default('');
            // $table->primary('id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('event_instances');
    }
}
