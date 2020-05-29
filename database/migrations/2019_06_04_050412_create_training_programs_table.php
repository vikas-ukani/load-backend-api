<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrainingProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('training_programs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('status', 50)->comment("RESISTANCE and CARDIO Use Constant");
            $table->string('type', 50)->comment("PRESET and CUSTOM Use Constant");
            $table->bigInteger('preset_training_programs_id')->nullable()->comment("From preset_training_programs table");
            $table->bigInteger('training_frequencies_id')->nullable()->comment("From training_frequencies table");
            $table->timestamp('start_date')->comment("Program start date")->nullable();
            $table->timestamp('end_date')->comment("Program end date")->nullable();
            $table->string('by_date', 10)->nullable()->comment("Use Constant");
            $table->string('days', 100)->nullable()->comment("multiple days per week");
            $table->timestamp('date')->nullable();
            $table->text('phases')->nullable()->comment("Use Only in In Training Program In Cardio and Resistance");
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
        Schema::dropIfExists('training_programs');
    }
}
