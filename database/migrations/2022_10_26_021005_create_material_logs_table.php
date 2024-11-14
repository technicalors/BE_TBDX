<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaterialLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('material_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('type')->default(1);
            $table->string('cell_id')->nullable();
            $table->string('material_id');
            $table->string('product_id')->nullable();
            $table->integer('quantity')->default(0);

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
        Schema::dropIfExists('material_logs');
    }
}
