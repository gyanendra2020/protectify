<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUbEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ub_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('page_id')->index();
            $table->unsignedBigInteger('index')->index();
            $table->unsignedBigInteger('time')->index();
            $table->string('type');
            $table->string('path')->nullable();
            $table->string('name')->nullable();
            $table->text('data')->nullable();
            $table->timestamps();
            $table->unique(['page_id', 'index']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ub_events');
    }
}
