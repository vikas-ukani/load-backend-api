<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSavedWorkoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( 'saved_workouts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger( 'training_log_id')->comment("Training log Id");
            $table->bigInteger( 'user_id')->comment("User Id");
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
        Schema::dropIfExists( 'saved_workouts');
    }
}
