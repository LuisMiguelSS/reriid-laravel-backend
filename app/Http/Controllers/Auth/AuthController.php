<?php

namespace App\Http\Controllers\Auth;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller {

    public function login(Request $request) {
        
        try {

            $validator = Validator::make($request->all(), [
                'login'         => 'string|required',
                'password'      => 'required|string',
                'remember_me'   => 'nullable|boolean'
            ]);

            $login = $request->login;
            $password = $request->password;
            $loginType = filter_var($login, FILTER_VALIDATE_EMAIL)? 'email': 'username';
             
            if ($validator->fails()) {
                return response()->json(['errors'=>$validator->errors()]);
            }

            // Check if authentication fails
            if(!Auth::attempt([$loginType => $login, 'password' => $password])) {
                return response()->json([
                    'message' => 'Incorrect credentials'
                ], 401);
            }
    
            $user = $request->user();
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->token;
    
            if ($request->remember_me)
                $token->expires_at = Carbon::now()->addWeeks(1);
    
            $token->save();
    
            return response()->json([
                'access_token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_at' => Carbon::parse(
                    $tokenResult->token->expires_at
                )->toDateTimeString()
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'We couldn\'t log you in' .$th
            ], 500);
        }

    }
    
    public function register(Request $request)
    {
        try{
            
            // Check for all fields except the profile picture
            $validator = Validator::make($request->all(), [
                // Rules
                'username' => 'required|string|max:50|unique:users',
                'fullname' => 'required|string|max:75',
                'email' => 'required|string|email|unique:users',
                'birthdate' => 'date_format:Y-m-d|before:today',
                'password' => 'required|string|max:255',
            ]);
             
            if ($validator->fails())
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            
            // Continue creating the user
            $user = new User;
    
            $user->username = $request->username;
            $user->email = $request->email;
            $user->full_name = $request->fullname;
            $user->date_of_birth = $request->birthdate;
            $user->password = bcrypt($request->password);
            
            // Save the user to be able to get its ID
            $user->save();
            $accessToken = $user->createToken('Personal Access Token')->accessToken;

            if($request->hasFile('photo')) {
                try {
                    // Check the image
                    $validator = Validator::make($request->all(), [
                        'photo' => 'max:2048|file|mimes:jpg,jpeg,png,gif',               
                    ]);
                    
                    if ($validator->fails())
                        return response()->json([
                            'errors' => $validator->errors()
                        ], 422);

                    // Save image
                    $image_name = $user->id. '_profile-photo.' . $request->file('photo')->getClientOriginalExtension();
                    $path = $request->file('photo')->move(public_path('/storage/users/'), $image_name);
                    $photo_url = url('storage/users/' . $image_name);

                    // Update user's profile picture
                    $user->profile_pic = $photo_url;

                } catch(\Throwable $exc) {
                    return response()->json([
                        'message' => 'We could not save the image'
                    ], 500);
                }
            }

            $user->save();
            
            return response()->json([
                'message' => 'User created succesfully!',
                'user' => $user,
                'access_token' => $accessToken
            ], 201);
            
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'The user could not be added.'
            ], 500);
        }

    }
    
    public function logout(Request $request)
    {
        try {

            auth('web')->logout();
            $this->removeTokens($request->user());

            return response()->json([
                'message' => 'Successfully logged out!'
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'We couldn\'t log you off'
            ], 500);
        }

    }
  
    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    // Remove User Token
    public function removeTokens(User $user) {
        $userTokens = $user->tokens;

        foreach($userTokens as $token) {
            $token->revoke();
            $token->delete();
        }
    }

}