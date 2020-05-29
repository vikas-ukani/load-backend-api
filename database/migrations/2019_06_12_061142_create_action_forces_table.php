<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActionForcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('action_forces', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 200)->comment("mechanics name");
            $table->string('code', 200)->comment("code from name");
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
        Schema::dropIfExists('action_forces');
    }
}
