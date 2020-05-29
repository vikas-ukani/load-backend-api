<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLoadCenterRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('load_center_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->comment("who create request");
            $table->foreign('user_id')->references('id')->on('users');
            /** user professional profile id */
            // $table->unsignedBigInteger('user_id')->comment("who create request");
            // $table->foreign('user_id')->references('id')->on('users');
            // 1
            $table->string('title', 200)->comment("Request Title Broadcast");
            $table->timestamp('start_date')->comment("Training Start date");
            // $table->timestamp('birth_date')->nullable()->comment("your Birth date");
            $table->string('birth_date', 20)->nullable()->comment("date from register user");
            $table->text('yourself')->nullable()->comment("about your self");
            // 2 
            $table->unsignedBigInteger('country_id')->nullable()->comment("user select country");
            $table->foreign('country_id')->references('id')->on('countries');
            $table->string('specialization_ids', 200)->nullable()->comment("select specialization ");
            $table->string('training_type_ids', 200)->nullable()->comment("select training types ");
            $table->string('experience_year', 50)->nullable()->comment("experience year");
            $table->double('rating')->nullable()->comment('Request ratting');

            // 3
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
        Schema::dropIfExists('load_center_requests');
    }
}
