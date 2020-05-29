<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRepetitionMaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('repetition_maxes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 200)->comment("repetition_maxes name");
            $table->string('code', 200)->comment("code from name");
            $table->integer('weight')->comment("Total Weight");
            // $table->integer('estimated_weight')->comment("Estimated Weight");
            // $table->integer('actual_weight')->comment("Actual Weight");
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
        Schema::dropIfExists('repetition_maxes');
    }
}
