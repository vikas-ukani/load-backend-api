<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrainingFrequenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('training_frequencies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 200)->comment("Title name");
            $table->string('code', 200)->nullable()->comment("from title name");
            $table->integer('max_days')->nullable()->comment("depend on title for day validation");
            $table->text('preset_training_program_ids')->nullable()->comment("To show preset program ids");
            $table->string('is_active', 200)->default(true)->comment("check for active or deactivate");
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
        Schema::dropIfExists('training_frequencies');
    }
}
