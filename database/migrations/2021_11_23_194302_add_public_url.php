<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPublicUrl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('event_instances', function (Blueprint $table) {
            $table->addColumn('text', 'foreign_url')
                ->nullable()
                ->default(null)
                ->after('location');
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
            $table->removeColumn('foreign_url');
        });
    }
}
