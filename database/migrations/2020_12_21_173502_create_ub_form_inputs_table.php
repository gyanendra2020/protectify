<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUbFormInputsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ub_form_inputs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('page_id')->index();
            $table->unsignedBigInteger('form_id')->index();
            $table->string('type')->nullable();
            $table->string('name')->index();
            $table->string('title')->nullable();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE ub_form_inputs ADD FULLTEXT value_fulltext(value)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ub_form_inputs');
    }
}
