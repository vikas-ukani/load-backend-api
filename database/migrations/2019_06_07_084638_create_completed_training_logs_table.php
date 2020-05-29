<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompletedTrainingLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('completed_training_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('exercise')->comment("completed Exercise tile details");
            $table->bigInteger('training_log_id')->comment("training Log Id");
            $table->boolean('is_complete')->default(false)->comment("check is program is completed or not");
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
        Schema::dropIfExists('completed_training_logs');
    }
}
