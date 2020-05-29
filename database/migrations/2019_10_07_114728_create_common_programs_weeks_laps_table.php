<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommonProgramsWeeksLapsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('common_programs_weeks_laps', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('common_programs_week_id')->unsigned();
            $table->foreign('common_programs_week_id')->references('id')->on('common_programs_weeks')->onDelete('cascade');

            $table->integer('lap')->nullable()->comment("how many lap in this program week");
            $table->integer('percent')->nullable()->comment("% for laps");
            $table->string('distance')->nullable()->comment("Distance (km OR miles)");
            $table->string('speed')->nullable()->comment("km/hr OR mile/hr");
            $table->string('rest')->nullable()->comment("km/hr OR mile/hr");
            $table->string('vdot')->nullable()->comment("pace");

            $table->boolean('is_active')->default(true)->comment('to check is active or not');
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
        Schema::dropIfExists('common_programs_weeks_laps');
    }
}
