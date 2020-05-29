<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePresetTrainingProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('preset_training_programs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 200)->comment("Program Title");
            $table->string('code', 200)->nullable()->comment("code from title");
            $table->string('subtitle', 200)->comment("Program subtitle");
            $table->string('status', 50)->comment("RESISTANCE and CARDIO");
            $table->string('type', 50)->comment("PRESET and CUSTOM");
            $table->boolean('is_active')->default(true)->comment("Active or Deactivate");
            $table->integer('weeks')->nullable()->comment("use for CARDIO only show for date wise week");
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
        Schema::dropIfExists('preset_training_programs');
    }
}
