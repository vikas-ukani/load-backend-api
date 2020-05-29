<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorkoutWiseLapsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workout_wise_laps', function (Blueprint $table) {
            $table->bigIncrements('id');

            // $table->bigInteger('week_wise_workout_id')->unsigned();
            // $table->foreign('week_wise_workout_id')->references('id')->on('week_wise_workouts');

            $table->text('week_wise_workout_ids')->nullable()->comment('week_wise_workouts ids');

            $table->integer('lap')->comment('lap number');
            $table->string('percent', 50)->nullable()->comment('percent of current lap');
            $table->string('distance', 100)->nullable()->comment('distance of current lap');
            $table->string('duration', 100)->nullable()->comment('duration of current lap');
            $table->string('speed', 100)->nullable()->comment('speed of current lap');
            $table->string('rest', 100)->nullable()->comment('rest of current lap');
            $table->string('VDOT', 10)->nullable()->comment('VDOT for calculate speed');
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
        Schema::dropIfExists('workout_wise_laps');
    }
}
