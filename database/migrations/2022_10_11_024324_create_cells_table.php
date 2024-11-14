<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCellsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cells', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("row")->default("");
            $table->string("col")->default("");
            $table->string('note')->default("");
            $table->string("name")->default("");
            $table->uuid('sheft_id');
            $table->timestamps();
        });


        Schema::create('cell_product', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->uuid("cell_id");
            $table->uuid("product_id");
            $table->double("quantity")->default(0);
            $table->text("info")->nullable(true);
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
        Schema::dropIfExists('cells');
    }
}
