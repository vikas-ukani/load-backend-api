<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrainingSettingUnitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('training_setting_units', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 200)->comment('name of the units');
            $table->string('code', 200)->comment('code of the units');
            $table->string('description', 200)->nullable()->comment('description of main records.');
            $table->boolean('is_active')->default(true)->comment('description of main records.');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public static function down()
    {
        Schema::dropIfExists('training_setting_units');
    }
}
