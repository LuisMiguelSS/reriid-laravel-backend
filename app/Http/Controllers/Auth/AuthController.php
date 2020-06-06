<?php

namespace App\Http\Controllers\Auth;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\File\FileController;
use App\Notifications\VerifyEmailQueued;
use Swift_TransportException;

class AuthController extends Controller {

    public function validToken(Request $request) {
        return true;
    }

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
                    'errors' => ['Incorrect credentials']
                ], Response::HTTP_UNAUTHORIZED);
            }
    
            $user = $request->user();

            // Check if email has been verified
            if (!$user->hasVerifiedEmail()) {
                return response()->json([
                    'errors' => ['Email not verified']
                ], Response::HTTP_UNAUTHORIZED);
            }

            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->token;
    
            if ($request->remember_me)
                $token->expires_at = Carbon::now()->addMonths(1);
    
            $token->save();
    
            return response()->json([
                'access_token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_at' => Carbon::parse(
                    $tokenResult->token->expires_at
                )->toDateTimeString()
            ]);

        } catch (\Throwable $throwable) {
            return response()->json([
                'errors' => ['We couldn\'t log you in']
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            
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
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);

                    // Save image & update user's profile picture
                    $user->profile_pic = FileController::store_profilepic($user,$request->file('photo'));

                } catch(\Throwable $throwable) {
                    return response()->json([
                        'errors' => ['We could not save the image']
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            // Send verification mail
            $user->sendEmailVerificationNotification();

            $user->save();
            
            return response()->json([
                'verification_link_expiry' => VerifyEmailQueued::VERIFICATION_LINK_EXPIRY . ' minutes',
                'data' => $user,
                'access_token' => $accessToken
            ], Response::HTTP_CREATED);
            
        } catch (\Throwable $th) {
            $user->forceDelete();

            $errors = array('The user could not be created.');

            if ($th instanceof Swift_TransportException) {
                array_push($errors, 'We could not send the verification mail.');
            }

            return response()->json([
                'errors' => $errors
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
    
    public function logout(Request $request)
    {
        try {

            auth('web')->logout();
            $this->revoke_tokens($request->user());

            return response()->json([
                'message' => 'Successfully logged out!'
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'We couldn\'t log you off'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
  
    /**
     * Get the authenticated User
     *
     * @return Response user object
     */
    public function user(Request $request)
    {
        return response()->json(['data' => $request->user()]);
    }

    // Remove User Token
    public function revoke_tokens(User $user) {
        $userTokens = $user->tokens;

        foreach($userTokens as $token) {
            $token->revoke();
            $token->delete();
        }
    }

}