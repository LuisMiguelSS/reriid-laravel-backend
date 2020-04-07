<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\User;

class UserController extends Controller
{
    public function index() {
        return response()->json(["count"=>User::all()->count(), "result"=>User::all()]);
    }

    public function show($id) {
        return User::findOrFail($id);
    }

    public function update(Request $request, $id) {

        $user = User::findOrFail($id);

        if ($user != null) {

            if ($request->fullname)
                $user->full_name = $request->fullname;
            
            if ($request->email)
                $user->email = $request->email;
            
            if ($request->birthdate)
                $user->date_of_birth = $request->birthdate;
            
            // LOCATION
            // Latitude
            if ($request->latitude)
                $user->latitude = $request->latitude;

            if ($request->lat)
                $user->latitude = $request->lat;
            
            // Longitude
            if ($request->longitude)
                $user->longitude = $request->longitude;

            if ($request->long)
                $user->longitude = $request->long;
            
            if($request->hasFile('photo')) {
                try {
                    
                    // Check the image
                    $validator = \Validator::make($request->all(), [
                        'photo' => 'max:2048|file|mimes:jpg,jpeg,png,gif',               
                    ]);
                    
                    if ($validator->fails())
                        return response()->json([
                            'errors' => $validator->errors()
                        ], 422);
                    
                    // Save image
                    $image_name = $id . '_profile-photo.' . $request->file('photo')->getClientOriginalExtension();
                    $path = $request->file('photo')->move(public_path('/storage/users/'), $image_name);
                    $photo_url = url('storage/users/' . $image_name);

                    // Update user's profile picture
                    $user->profile_pic = $photo_url;

                } catch(\Throwable $exc) {
                    return response()->json([
                        'message' => 'The image could not be saved'
                    ], 500);
                }
            }

            // Save updates
            try {
       
                $user->save();

                return response()->json([
                    $user
                ], 200);

            } catch (QueryException $qe) {
                return response()->json([
                    'message' => 'The user could not be modified'
                ], 500);
            }

        }

    }

    public function destroy($id) {
        try {

            $user = User::findOrFail($id);
            $user->delete();

            return response()->json([
                'message' => 'User deleted succesfully!'
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'The user could not be deleted.'
            ], 500);
        }
    }
}
