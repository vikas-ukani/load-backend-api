<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSettingRaceDistancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('setting_race_distances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100)->comment('name of race distance');
            $table->string('code', 100)->comment('code from name');
            $table->boolean('is_active')->default(true)->comment('check for active or not');
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
        Schema::dropIfExists('setting_race_distances');
    }
}
