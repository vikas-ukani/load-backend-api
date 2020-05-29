<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersSnoozesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_snoozes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->comment("Snooze for user id");
            $table->timestamp('start_date')->nullable()->comment("Set snooze time for start date");
            $table->timestamp('end_date')->nullable()->comment("Set snooze time for end date");
            // $table->index(['user_id']);
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
        Schema::dropIfExists('users_snoozes');
    }
}
