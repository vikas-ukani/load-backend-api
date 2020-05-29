<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrainingLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('training_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->comment("User Id Who Created this log");
            $table->string('status', 50)->comment(TRAINING_LOG_STATUS_CARDIO . " and " . TRAINING_LOG_STATUS_RESISTANCE . " use constant");
            $table->timestamp('date')->nullable()->comment("Training Log Date");
            $table->string('workout_name', 200)->nullable()->comment("Workout Title");
            $table->bigInteger('training_goal_id')->nullable()->comment("Training Goal");
            $table->string('training_goal_custom', 200)->nullable()->comment("Text for training goal custom text");
            $table->string('training_goal_custom_id', 200)->nullable()->comment("For Store Custom Trainng Goal Id Validation from device side");
            $table->bigInteger('training_intensity_id')->comment("Training intensity");
            $table->bigInteger('training_activity_id')->nullable()->comment("No activity on Resistance Status");
            $table->integer('user_own_review')->nullable()->comment("Use give own training session review");
            $table->text('notes')->nullable()->comment("Other Remarks");
            $table->text('exercise')->nullable()->comment("Store exercise object");
            $table->boolean('is_log')->default(true)->comment("if true then show log else show workouts");

            /** location columns */
            $table->string('latitude', 50)->nullable()->comment("Log latitude Location.");
            $table->string('longitude', 50)->nullable()->comment("Log longitude Location.");

            $table->text('comments')->nullable()->comment("User Can leave comment here");
            $table->boolean('is_complete')->default(false)->comment("User Complete this training log exercise");
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
        Schema::dropIfExists('training_logs');
    }
}
