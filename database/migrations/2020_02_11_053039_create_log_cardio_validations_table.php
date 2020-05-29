<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogCardioValidationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_cardio_validations', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('training_activity_id')->index()->comment('coming from training activity table');
            $table->bigInteger('training_goal_id')->index()->comment('coming from training goal table');
        
            $table->string('distance_range', 200)->nullable()->comment("distance range");
            $table->string('duration_range', 200)->nullable()->comment("duration range");
            $table->string('speed_range', 200)->nullable()->comment("speed range");
            $table->string('pace_range', 200)->nullable()->comment("pace range");
            $table->string('percentage_range', 200)->nullable()->comment("percentage range");
            $table->string('rest_range', 200)->nullable()->comment("rest range");

            $table->boolean('is_active')->default(true)->comment('check for active validation or not.');

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
        Schema::dropIfExists('log_cardio_validations');
    }
}
