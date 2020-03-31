<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoadAvgsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('load_avgs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('period_1');
            $table->unsignedInteger('period_2');
            $table->unsignedInteger('period_3');
            $table->unsignedInteger('cpus');
            $table->timestamps();
        });
    }
}
