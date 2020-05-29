<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBookedClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('booked_clients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('from_id')->comment(" Request Sender-Id (user_id).");
            $table->bigInteger('to_id')->comment(" Request Receiver-Id (user_id).");
            $table->timestamp('selected_date')->comment("Request selected date");
            $table->bigInteger('available_time_id')->comment("From available table id to show name");
            $table->text("notes")->comment("Set any notes for professional users");
            $table->integer('confirmed_status')->comment("0 => pending, 1 => accepted, 2 => rejected");
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
        Schema::dropIfExists('booked_clients');
    }
}
