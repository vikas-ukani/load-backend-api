<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SettingProfessionalProfiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('setting_professional_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->comment("User Profile");
            $table->foreign('user_id')->references('id')->on('users');
            $table->text('profession')->nullable()->comment("User profession");
            $table->text('location_name')->nullable()->comment("Location Name");
            $table->text('introduction')->nullable()->comment('user introduction');
            $table->text('specialization_ids')->nullable()->comment('user specialized activities');
            $table->string('rate', 10)->default(0)->comment('training rate');
            $table->bigInteger('cancellation_policy_id')->nullable()->comment('cancellation_policy_id');
            $table->text('general_rules')->nullable()->comment('add general rules');
            $table->bigInteger('currency_id')->nullable()->comment('currency id');
            $table->text('academic_credentials')->nullable()->comment('user academic and certifications');
            $table->text('experience_and_achievements')->nullable()->comment('user experience and achievements');
            $table->text('terms_of_service')->nullable()->comment('user terms of service');
            $table->text('languages_spoken_ids')->nullable()->comment('user languages spoken');
            $table->text('languages_written_ids')->nullable()->comment('user languages written');

            // // session details
            $table->string('session_duration', 100)->nullable()->comment("Session duration");
            // $table->string('session_types', 100)->nullable()->comment('Session Types ( Single/Multiple )  ');
            $table->bigInteger('professional_type_id')->nullable()->comment('Session Types ( From Professional Types table ) ');
            $table->integer('session_maximum_clients')->nullable()->comment('maximum clients per sessions');

            //// Client Requirement
            $table->text('basic_requirement')->nullable()->comment("Basics Requirement");
            $table->boolean('is_forms')->nullable()->comment("forms is true or false");
            $table->boolean('is_answerd')->nullable()->comment("get answer from form");

            //// Information
            $table->text('amenities')->nullable()->comment("amenities ");

            //// Payment & Rates
            $table->bigInteger('payment_option_id')->nullable()->comment('Payment Option Id');
            $table->bigInteger('per_session_rate')->nullable()->comment('Client per session price');
            $table->bigInteger('per_multiple_session_rate')->nullable()->comment('Client per Multiple session price');

            //// Availability
            $table->text('days')->nullable()->comment("Client Available at these days");
            $table->boolean('is_auto_accept')->default(false)->comment("check for auto accept is true OR false");
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
        Schema::dropIfExists('setting_professional_profiles');
    }
}
