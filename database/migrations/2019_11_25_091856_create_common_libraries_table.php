<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommonLibrariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('common_libraries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('exercise_name', 200)->comment("Exercise name");
            $table->bigInteger('region_id')->comment("From region table");
            $table->bigInteger('body_part_id')->comment("From Body part table");
            $table->bigInteger('body_sub_part_id')->comment("From Body part table");
            $table->bigInteger('mechanics_id')->comment("From mechanics table");
            $table->bigInteger('targeted_muscles_id')->comment("From targeted_muscles table");
            $table->bigInteger('action_force_id')->comment("From action_force table");
            $table->bigInteger('equipment_ids')->comment("From equipment table multiple");
            $table->string('selected_rm', 10)->nullable()->comment("From equipment table multiple");
            $table->boolean('is_active')->default(true)->comment("active or not");
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
        Schema::dropIfExists('common_libraries');
    }
}
