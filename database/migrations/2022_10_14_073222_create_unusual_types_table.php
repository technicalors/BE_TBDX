<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnusualTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reasons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('note')->nullable();
            $table->timestamps();
        });


        Schema::create('reason_machine', function (Blueprint $table) {
            $table->id();
            $table->uuid('reason_id');
            $table->uuid('machine_id');
            $table->text('result')->nullable();
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
        Schema::dropIfExists('reason_machine');
        Schema::dropIfExists('reasons');

    }
}
