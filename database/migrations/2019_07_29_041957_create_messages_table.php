<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('conversation_id')->comment('Store conversation ids');

            $table->bigInteger("from_id")->nullable()->comment("Message from id (user_id)")->unsigned();
            $table->foreign("from_id")->references("id")->on("users");
            $table->bigInteger("to_id")->nullable()->comment("Message to id (user_id)")->unsigned();
            $table->foreign("to_id")->references("id")->on("users");
            $table->text("message")->comment("message body here")->nullable();

            $table->bigInteger("training_log_id")->nullable()->comment("training log id")->unsigned();
            $table->bigInteger("event_id")->nullable()->comment("event id")->unsigned();
            $table->bigInteger("booked_client_id")->nullable()->comment("booked client id")->unsigned();

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
        Schema::dropIfExists('messages');
    }
}
