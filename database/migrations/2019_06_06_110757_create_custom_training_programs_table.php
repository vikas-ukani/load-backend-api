<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomTrainingProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_training_programs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string( 'title', 200)->comment( "Phase title");
            $table->string( 'code', 200)->comment( "from title");
            $table->boolean( 'is_active')->default(true)->comment( "Phase is active or not");
            $table->bigInteger( 'parent_id')->nullable()->comment( "Parent id from this table");
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
        Schema::dropIfExists('custom_training_programs');
    }
}
