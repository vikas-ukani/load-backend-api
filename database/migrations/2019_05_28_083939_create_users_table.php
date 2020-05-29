<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable()->comment('first last name');
            $table->string('email')->unique();
            $table->string('password', 100);
            $table->string('country_code', 10)->nullable()->comment("Country code for mobile verification")->nullable();
            $table->string('mobile')->nullable();
            $table->string('facebook')->nullable()->comment("User facebook id");
            $table->string('date_of_birth', 50)->nullable();
            $table->string('gender', 10)->nullable();
            $table->integer('height')->nullable();
            $table->integer('weight')->nullable();
            $table->string('photo')->nullable();
            $table->text('goal')->nullable()->comment("User Goal.");
            $table->unsignedBigInteger('country_id')->nullable()->comment("User Location.");
            $table->foreign('country_id')->references('id')->on('countries');
            $table->string('latitude', 50)->nullable()->comment("User latitude Location.");
            $table->string('longitude', 50)->nullable()->comment("User longitude Location.");
            $table->string('membership_code')->nullable()->comment('member-01');
            $table->string('user_type', 20)->nullable()->comment('admin, user');
            $table->integer('account_id')->nullable()->comment('free, premium, professional');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_profile_complete')->default(false);
            $table->timestamp('email_verified_at')->nullable()->default(null);
            $table->timestamp('mobile_verified_at')->nullable()->default(null);
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('last_login_at')->nullable()->comment("Check for last login at");
            $table->string('socket_id', 20)->nullable()->comment("Store Socket Id");
            $table->boolean('is_online')->nullable()->default(false)->comment("check the user is online for chat");
            $table->boolean('is_snooze')->nullable()->default(false)->comment("for professional user is snooze or not");
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
