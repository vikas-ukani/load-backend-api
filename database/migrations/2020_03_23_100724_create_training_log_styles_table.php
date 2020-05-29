<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrainingLogStylesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('training_log_styles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 50)->comment("name of style");
            $table->string('code', 100)->comment('check unique name');
            $table->boolean('is_active')->default(true)->comment('check for active or not.');
            
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
        Schema::dropIfExists('training_log_styles');
    }
}
