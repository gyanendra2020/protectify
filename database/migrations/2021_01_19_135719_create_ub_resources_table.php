<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUbResourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ub_resources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('page_id');
            $table->unsignedBigInteger('parent_resource_id')->nullable();
            $table->text('child_resource_ids')->nullable();
            $table->string('url', 2048);
            $table->string('hash', 32);
            $table->string('mime')->nullable();
            $table->text('path')->nullable();
            $table->unsignedTinyInteger('retries_count');
            $table->unsignedSmallInteger('status')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['page_id', 'hash']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ub_resources');
    }
}
