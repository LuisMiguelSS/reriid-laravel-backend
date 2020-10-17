<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');

            $table->string('description');
            $table->boolean('reserved')->default('0');
            $table->mediumInteger('views')->unsigned()->default('0');
            $table->json('images')->nullable();

            // Book Data
            $table->string('book_title');
            $table->string('book_subtitle')->nullable();
            $table->string('book_synopsis')->nullable();
            $table->bigInteger('book_isbn')->unsigned()->nullable();
            $table->string('book_author')->nullable();
            $table->double('book_price')->nullable();
            
            $table->softDeletes();
            $table->timestamps();

            // Indexes for faster lookup
            $table->index('user_id');
            $table->index('book_title');
            $table->index('views');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posts');
    }
}
