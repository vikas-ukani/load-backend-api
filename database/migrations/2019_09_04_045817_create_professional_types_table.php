<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProfessionalTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('professional_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100)->comment("Name of your payment option");
            $table->string('code', 100)->comment("code for unique payment option type");
            $table->text('description')->nullable()->comment("for more description payment options");
            $table->boolean('is_active')->default(true)->comment("for active or de-active");
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
        Schema::dropIfExists('professional_types');
    }
}
