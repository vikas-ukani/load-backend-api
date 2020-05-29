<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLoadCenterEventTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('load_center_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->comment("who create events");
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('event_type_ids', 200)->nullable()->comment("Multiple Event Types");
            // $table->string('title', 200)->nullable()->comment("Title of event");
            $table->string('visible_to', 200)->nullable()->comment("visible to " . LOAD_CENTER_EVENT_VISIBILITY_INVITATION_ONLY . " and " . LOAD_CENTER_EVENT_VISIBILITY_PUBLIC . " use constant here");
            $table->integer('max_guests')->nullable()->comment("number of guests will come");
            $table->string('event_name', 200)->nullable()->comment("name of event");
            $table->string('event_price')->nullable()->comment("event price");
            $table->bigInteger('currency_id')->nullable()->comment("currency_id");
            $table->string('event_image', 200)->nullable()->comment("event image to show in list");
            $table->timestamp('date_time')->nullable()->comment("event date and time");
            // $table->timestamp('time')->comment("event time");
            $table->integer('earlier_time')->nullable()->comment("to come earlier time");
            $table->integer('duration')->nullable()->comment("event durations");
            $table->string('location', 200)->nullable()->comment("event location");
            $table->string('latitude', 20)->nullable()->comment("event map latitude");
            $table->string('longitude', 20)->nullable()->comment("event map longitude");
            // $table->string('location_map', 200)->nullable()->comment("event location on map");
            $table->text('description')->nullable()->comment("more about event description");
            $table->text('amenities_available')->nullable()->comment("event services");
            $table->boolean('is_completed')->default(false)->nullable()->comment("event is completed or not");
            $table->bigInteger('cancellation_policy_id')->nullable()->comment("cancellation policy");
            $table->text('general_rules')->nullable()->comment("rules for events");
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
        Schema::dropIfExists('load_center_event');
    }
}
