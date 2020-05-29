<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLibrariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('libraries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('exercise_name', 200)->comment("Exercise name");
            $table->bigInteger('user_id')->nullable()->comment("From users table");
            $table->bigInteger('category_id')->comment("Main Body Part.");
            $table->text('regions_ids')->comment("From Sub Body parts table ids,");
            $table->bigInteger('mechanics_id')->nullable()->comment("From mechanics table");
            $table->text('targeted_muscles_ids')->nullable()->comment("From targeted_muscles table");
            $table->bigInteger('action_force_id')->nullable()->comment("From action_force table");
            $table->bigInteger('equipment_id')->nullable()->comment("From equipment table");
            $table->text('repetition_max')->nullable()->comment("Store array object");
            $table->text('exercise_link')->nullable()->comment("link url.");
            $table->boolean('is_favorite')->default(false)->comment("show in favorite list");
            $table->boolean('is_active')->default(false)->comment("active or not");
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
        Schema::dropIfExists('libraries');
    }
}
