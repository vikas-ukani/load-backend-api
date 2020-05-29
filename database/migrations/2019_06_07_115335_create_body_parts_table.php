<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBodyPartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('body_parts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 200)->comment("Body Part name");
            $table->string('code', 200)->nullable()->comment("From name");
            $table->string('type', 20)->nullable()->comment("'front' | 'back' | 'front-back'");
            $table->text('image')->nullable()->comment('body part images');
            $table->integer('sequence')->nullable()->comment("sequence ordering");
            $table->boolean('is_active')->default(true)->comment("is active or deactivate");
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
        Schema::dropIfExists('body_parts');
    }
}
