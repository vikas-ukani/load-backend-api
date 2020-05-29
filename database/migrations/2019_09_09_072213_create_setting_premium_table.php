<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSettingPremiaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('setting_premium', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->comment('who created it');
            $table->text('about')->nullable()->comment('about premium user detail');
            $table->text('specialization_ids')->nullable()->comment('multiple specialization ids (max 3)');
            $table->text('language_ids')->nullable()->comment('multiple language ids');

            // ADD TOP UP
            $table->boolean('is_auto_topup')->default(false)->comment('is auto refil wallet or not');
            $table->integer('auto_topup_amount')->nullable()->comment('auto refil wallet amount');
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
        Schema::dropIfExists('setting_premium');
    }
}
