<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWeekWiseWorkoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('week_wise_workouts', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('week_wise_frequency_master_id')->unsigned();
            $table->foreign('week_wise_frequency_master_id')->references('id')->on('week_wise_frequency_masters');

            $table->integer('workout')->comment('Workout number');


            $table->bigInteger('training_activity_id')->comment("Training Activity id");
            $table->foreign('training_activity_id')->references('id')->on('training_activity')->onDelete('cascade');

            $table->bigInteger('training_goal_id')->comment("Training Goal id");
            $table->foreign('training_goal_id')->references('id')->on('training_goal')->onDelete('cascade');

            $table->bigInteger('training_intensity_id')->comment("Training intensity id");
            $table->foreign('training_intensity_id')->references('id')->on('training_intensity')->onDelete('cascade');
            $table->string('THR', 20)->nullable()->comment("inputted from pdf");

            $table->string('name', 100)->nullable()->comment("Title of the week wise workouts");
            $table->text('note')->nullable()->comment("notes of week wise workouts");

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
        Schema::dropIfExists('week_wise_workouts');
    }
}
