<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWeekWiseFrequencyMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('week_wise_frequency_masters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('training_plan_type', 50)->comment('use Constant like ' . COMMON_PROGRAMS_PLAN_TYPE_5K);
            $table->integer('week_number')->comment('week number');
            $table->integer('frequency')->comment('frequency number X');
            $table->string('workouts', 50)->comment('week workouts W1,W2,W3');
            $table->integer('base')->nullable()->comment('week workouts W1,W2,W3');
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
        Schema::dropIfExists('week_wise_frequency_masters');
    }
}
