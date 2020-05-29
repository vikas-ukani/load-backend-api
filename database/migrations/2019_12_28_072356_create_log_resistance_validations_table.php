<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogResistanceValidationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_resistance_validations', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('training_intensity_id')->comment('coming from training intensity table');
            $table->bigInteger('training_goal_id')->comment('coming from training goal table');

            $table->string('weight_range', 200)->nullable()->comment("Weight range");
            $table->string('reps_range', 200)->nullable()->comment("Reps range");
            $table->string('rest_range', 200)->nullable()->comment("Rest range");

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
        Schema::dropIfExists('log_resistance_validations');
    }
}
