<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'user_id', 'images', 'description',
        'reserved', 'views', 'book_title',
        'book_subtitle', 'book_synopsis', 'book_isbn',
        'book_author'
    ];
}
