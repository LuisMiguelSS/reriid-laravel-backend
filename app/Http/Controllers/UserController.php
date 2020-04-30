<?php

namespace App\Http\Controllers;

use App\User;
use \Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Returns the available users in the DB
     * 
     * @return \Illuminate\Http\Response List of active users (not deleted)
     * 
     */
    public function index()
    {
        $pagination_result = User::paginate();

        if ($pagination_result->total() == 0) {
            return response()->noContent();
        }

        return response()->json($pagination_result);
    }

    /**
     * Finds the soft deleted users in the DB
     * 
     * @return \Illuminate\Http\Response List of deleted users
     * 
     */
    public function indexdeleted()
    {
        $pagination_result = User::onlyTrashed()->paginate();

        if ($pagination_result->total() == 0) {
            return response()->noContent();
        }

        return response()->json($pagination_result);
    }

    /**
     * Restores the soft deleted record.
     * 
     * @param $id The user's id
     * 
     * @return \Illuminate\Http\Response
     */
    public function restore($id) {
        $user = User::onlyTrashed()->where('id', $id)->first();

        if ($user) {
            $user->restore();
            return response()->json(['data' => $user]);
        }

        return response()->noContent();
    }

    /**
     * Gets the given user's info
     * 
     * @param $id The user's id
     * 
     * @return \Illuminate\Http\Response
     * 
     */
    public function show($id)
    {
        return response()->json(['data' => User::findOrFail($id)]);
    }
    
    /**
     * Gets the given deleted user's info
     * 
     * @param $id The user's id
     * 
     * @return \Illuminate\Http\Response
     * 
     */
    public function showdeleted($id)
    {
        return response()->json(['data' => User::onlyTrashed()->where('id', $id)->first()]);
    }

    /**
     * Updates the given user's data.
     * 
     * @param \Illuminate\Http\Request The request received from the API
     * @param $id The user's id
     * 
     * @return \Illuminate\Http\Response The updated user if it succeeds
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user != null) {

            // Get Attributes
            if ($request->fullname) {
                $user->full_name = $request->fullname;
            }

            if ($request->email) {
                $user->email = $request->email;
            }

            if ($request->birthdate) {
                $user->date_of_birth = $request->birthdate;
            }

            // LOCATION
            // Latitude
            if ($request->latitude) {
                $user->latitude = $request->latitude;
            }

            if ($request->lat) {
                $user->latitude = $request->lat;
            }

            // Longitude
            if ($request->longitude) {
                $user->longitude = $request->longitude;
            }

            if ($request->long) {
                $user->longitude = $request->long;
            }

            if ($request->hasFile('photo')) {
                try {

                    // Check the image
                    $validator = Validator::make($request->all(), [
                        'photo' => 'max:2048|file|mimes:jpg,jpeg,png,gif',
                    ]);

                    if ($validator->fails()) {
                        return response()->json([
                            'errors' => $validator->errors()
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    // Save image and update user's profile picture
                    $user->profile_pic = $this->store_file($user, $request->file('photo'));
                    
                } catch (Throwable $throwable) {
                    return response()->json([
                        'errors' => ['The image could not be saved']
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            // Save updates
            try {

                $user->save();

                return response()->json([
                    'data' => $user
                ], Response::HTTP_OK);

            } catch (QueryException $queryException) {
                return response()->json([
                    'errors' => ['The user could not be modified']
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }

    /**
     * Soft deletes the user and its posts.
     * 
     * @param $id The user's id
     * @return Response
     * 
     */
    public function destroy($id)
    {
        try {

            // Soft delete user
            $user = User::findOrFail($id);
            $user->delete();

            // Delete profile picture from disk
            if (File::exists(public_path($user->profile_pic))) {
                File::delete(public_path($user->profile_pic));

                // Update user entry
                $user->profile_pic = null;
                $user->save();
            }

            return response()->json([
                'message' => 'User deleted succesfully!'
            ], Response::HTTP_OK);

        } catch (\Throwable $throwable) {
            return response()->json([
                'errors' => ['The user could not be deleted.']
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Creates (if needed) a folder for the user
     * and stores the given image in it.
     * 
     * @param User $user
     * @param string $file
     * 
     * @return url The url of the saved file or null if there was a problem.
     * 
     */
    public static function store_file(\App\User $user, $file)
    {
        if ($user == null || !($user instanceof User) || $file == null) {
            return null;
        }

        $user_storage_folder = public_path() . '/uploads/user/' . $user->id . '/';

        // Check if user folder exists
        if (!File::exists($user_storage_folder)) {
            File::makeDirectory($user_storage_folder, 0755, true);
        }

        // Store (and replace if necessary) file
        $filename = $user->id . '-profile-' . date('d_M_Y') . '.' . $file->getClientOriginalExtension();

        if (File::exists($user_storage_folder . $filename)) {
            File::delete($user_storage_folder . $filename);
        }

        // Store file
        $file->move($user_storage_folder, $filename);

        return url('uploads/user/' . $user->id . '/' . $filename);
    }
}
