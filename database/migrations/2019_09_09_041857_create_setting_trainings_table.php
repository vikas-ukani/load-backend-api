<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSettingTrainingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('setting_trainings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->comment("Which user id can store it.");
            $table->float('hr_max')->nullable()->comment("HR max ");
            $table->float('height')->nullable()->comment("height");
            $table->float('weight')->nullable()->comment("weight");
            $table->bigInteger('race_distance_id')->nullable()->comment('from race distance table id');
            $table->string('race_time', 10)->nullable()->comment('race time');
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
        Schema::dropIfExists('setting_trainings');
    }
}
