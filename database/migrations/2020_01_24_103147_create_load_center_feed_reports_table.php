<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLoadCenterFeedReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('load_center_feed_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100 )->comment('name of the report');
            $table->string('code', 100)->comment('check for unique');
            $table->boolean('is_active')->default(true)->comment('check for active or not');
            $table->integer('sequence')->nullable()->comment('sequence order');
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
        Schema::dropIfExists('load_center_feed_reports');
    }
}
