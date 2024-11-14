<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductionPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('production_plans', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string('soLSX')->nullable(false);
            $table->uuid("product_id");
            $table->integer("somau")->default(0);
            $table->float("soluong")->default(0);
            $table->integer("mDauvao")->default(0);
            $table->integer("somet")->default(0);
            $table->uuid('description_id');
            $table->dateTime("ngaySX");
            $table->text('note')->nullable();
            $table->text('noteX')->nullable();
            $table->uuid('machine_id');
            $table->integer("khomang")->default(0);
            $table->text("info")->default(null);
            $table->integer("buhao")->default(0);
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
        Schema::dropIfExists('production_plans');
    }
}
