<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTargetedMusclesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('targeted_muscles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 200)->comment("name of targeted muscles");
            $table->string('code', 200)->nullable()->comment("from name");
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
        Schema::dropIfExists('targeted_muscles');
    }
}
