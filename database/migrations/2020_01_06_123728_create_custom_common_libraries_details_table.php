<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomCommonLibrariesDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_common_libraries_details', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('common_libraries_id')->comment('Relation Common libraries to user custom libraries');
            $table->bigInteger('user_id')->comment('User created id');
            $table->text('repetition_max')->comment('custom repetition_max details.');
            $table->boolean('is_show_again_message')->default(false)->comment('for show alert message');

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
        Schema::dropIfExists('custom_common_libraries_details');
    }
}
