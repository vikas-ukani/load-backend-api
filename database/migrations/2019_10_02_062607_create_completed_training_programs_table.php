<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompletedTrainingProgramsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('completed_training_programs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('program_id')->comment("training program id");
            $table->foreign('program_id')->references('id')->on('training_programs')->onDelete('cascade');

            $table->unsignedInteger('common_programs_weeks_id')->comment("training program id");
            $table->foreign('common_programs_weeks_id')->references('id')->on('common_programs_weeks')->onDelete('cascade');

            $table->unsignedInteger('week_wise_workout_id')->nullable()->comment("week wise workouts id");
            $table->foreign('week_wise_workout_id')->references('id')->on('week_wise_workouts')->onDelete('cascade');

            // $table->unsignedInteger('week_wise_frequency_master_id')->comment("training program id");
            // $table->foreign('week_wise_frequency_master_id')->references('id')->on('week_wise_frequency_masters')->onDelete('cascade');

            $table->text('exercise')->nullable()->comment("Store exercise object");
            $table->boolean('is_complete')->default(false)->comment("User Complete this training log exercise");
            $table->timestamp('date')->comment("completed program selected date")->nullable();
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
        Schema::dropIfExists('completed_training_programs');
    }
}
