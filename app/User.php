<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $dates = ['deleted_at'];

    /**
     * The attributes that are re-assignable.
     * @var array
     */
    protected $fillable = [
        'full_name', 'email', 'password', 'profile_pic', 'date_of_birth', 'latitude', 'longitude'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime'
    ];

    public function posts() {
        return $this->hasMany(Post::class);
    }

    /**
     * Override of the boot function
     * 
     * Links soft-deletable posts with the user to be
     * able to softdelete in cascade.
     */
    public static function boot() {
        parent::boot();

        static::deleting(function ($user) {
            $user->posts()->delete();
        });

        static::restoring(function ($user) {
            $user->posts()->withTrashed()->restore();
        });

    }
}
