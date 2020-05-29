<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommonProgramsWeeksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('common_programs_weeks', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name', 100)->nullable()->comment("Title of the common program weeks");
            // $table->string('title', 100)->nullable()->comment("Title of the week ( use constant here).");

            $table->bigInteger('training_activity_id')->comment("Training Activity id");
            $table->foreign('training_activity_id')->references('id')->on('training_activity')->onDelete('cascade');

            $table->bigInteger('training_goal_id')->comment("Training Goal id");
            $table->foreign('training_goal_id')->references('id')->on('training_goal')->onDelete('cascade');

            $table->bigInteger('training_intensity_id')->comment("Training intencity id");
            $table->foreign('training_intensity_id')->references('id')->on('training_intensity')->onDelete('cascade');

            $table->string('thr', 10)->nullable();

            $table->text('note')->nullable()->comment("notes of common program weeks");
            $table->integer('sequence')->nullable()->comment("sequence wise weeks");

            $table->boolean('is_active')->default(true)->comment("To check active or not");

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
        Schema::dropIfExists('common_programs_weeks');
    }
}
