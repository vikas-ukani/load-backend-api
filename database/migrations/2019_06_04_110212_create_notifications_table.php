<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string( "title", 100)->comment("Notification Title");
            $table->string( "message", 100)->comment("Notification full Message");
            $table->timestamp( "read_at")->default(null)->comment("when read at");
            $table->text( "body")->default(null)->comment("Notification Body in json");
            $table->bigInteger( "user_id")->comment("Which use send this notification");
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
        Schema::dropIfExists('notifications');
    }
}
