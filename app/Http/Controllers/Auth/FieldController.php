<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;

class FieldController extends Controller
{
    public function usernameExists(string $username) {
        return User::withTrashed()->where('username', $username)->count() > 0 ? 'true':'false';
    }

    public function emailExists(string $email) {
        return User::withTrashed()->where('email', $email)->count() > 0 ? 'true':'false';
    }

    public function userExists(string $usernameOrEmail) {
        return filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL)
            ?
            $this->emailExists($usernameOrEmail) :
            $this->usernameExists($usernameOrEmail);
    }
}
