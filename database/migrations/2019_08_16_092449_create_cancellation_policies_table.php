<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCancellationPoliciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cancellation_policies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 200)->comment("Name of cancellation policies");
            $table->string('code', 200)->nullable()->comment("code for unique");
            $table->text('description')->nullable()->comment("details for cancellation policies.");
            $table->boolean('is_active')->default(true)->comment("cancellation policies is active or not");
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
        Schema::dropIfExists('cancellation_policies');
    }
}
