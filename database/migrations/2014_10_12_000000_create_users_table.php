<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('username')->unique();
            $table->string('password');
            $table->string('full_name');
            $table->date('date_of_birth');
            $table->string('profile_pic')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();

            // Location
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->rememberToken();

            $table->softDeletes();
            $table->timestamps();

            // Indexes for faster lookup
            $table->index('username');
            $table->index('latitude');
            $table->index('longitude');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
