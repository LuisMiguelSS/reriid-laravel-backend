<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApikeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('key',64)->unique();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->string('associated_email');
            $table->string('responsible_fullname');
            $table->boolean('active')->default('1');

            $table->softDeletes();
            $table->timestamps();

            // Indexes for faster lookup
            $table->index('name');
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('apikeys');
    }
}
