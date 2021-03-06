<?php

namespace App\Http\Controllers;

use App\User;
use \Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\File\FileController;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserController extends Controller
{
    /**
     * Returns the available users.
     * 
     * @return \Illuminate\Http\Response List of users (not deleted).
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
     * Finds the soft deleted users.
     * 
     * @return \Illuminate\Http\Response List of the deleted users.
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
     * @param $id The user's id.
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
     * Gets the given user's data.
     * 
     * @param $id The user's id.
     * 
     * @return \Illuminate\Http\Response
     * 
     */
    public function show($id)
    {
        return response()->json(['data' => User::findOrFail($id)]);
    }
    
    /**
     * Gets the given deleted user's data.
     * 
     * @param $id The user's id.
     * 
     * @return \Illuminate\Http\Response
     * 
     */
    public function showdeleted($id)
    {
        return response()->json([
            'data' => User::onlyTrashed()
                            ->where('id', $id)
                            ->firstOrFail()
            ]
        );
    }

    /**
     * Updates the given user's data.
     * 
     * @param \Illuminate\Http\Request The request received from the API.
     * @param $id The user's id.
     * 
     * @return \Illuminate\Http\Response The updated user if it succeeds.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request_empty = true;

        if ($user != null) {

            // Get Attributes
            if ($request->fullname) {
                $user->full_name = $request->fullname;
                $request_empty = false;
            }

            if ($request->email) {
                $user->email = $request->email;
                $request_empty = false;
            }

            if ($request->birthdate) {
                $user->date_of_birth = $request->birthdate;
                $request_empty = false;
            }

            // LOCATION
            // Latitude
            if ($request->latitude) {
                $user->latitude = $request->latitude;
                $request_empty = false;
            }
            if ($request->lat) {
                $user->latitude = $request->lat;
                $request_empty = false;
            }

            // Longitude
            if ($request->longitude) {
                $user->longitude = $request->longitude;
                $request_empty = false;
            }

            if ($request->long) {
                $user->longitude = $request->long;
                $request_empty = false;
            }

            if ($request->hasFile('photo')) {
                $request_empty = false;
                try {

                    // Check the image
                    $validator = Validator::make($request->all(), [
                        'photo' => 'max:'. FileController::MAX_FILE_SIZE .'|file|mimes:'. implode(',', FileController::VALID_MIMES),
                    ]);

                    if ($validator->fails()) {
                        return response()->json([
                            'errors' => $validator->errors()
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    // Save image and update user's profile picture
                    $user->profile_pic = FileController::store_profilepic($user, $request->file('photo'));
                    
                } catch (Throwable $throwable) {
                    return response()->json([
                        'errors' => ['The image could not be saved' . $throwable]
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            // Save updates
            try {

                $user->save();

                if ($request_empty) {
                    return response()->json([
                        'errors' => 'No content was provided'
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                
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
     * @param $id The user's id.
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
     * Hard deletes the user and its posts.
     * 
     * @param $id The user's id.
     * @return Response
     * 
     */
    public function harddestroy($id)
    {
        try {

            // Hard delete user
            $user = User::findOrFail($id);
            $user->forceDelete();

            // Delete profile picture from disk
            if (File::exists(public_path('uploads/user/' . $id))) {
                File::deleteDirectory(public_path('uploads/user/' . $id));
            }

            return response()->json([
                'message' => 'User deleted succesfully!'
            ], Response::HTTP_OK);

        } catch (\Throwable $throwable) {

            if ($throwable instanceof ModelNotFoundException) {
                return response()->json([
                    'errors' => ['The user could not be found.']
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'errors' => ['The user could not be deleted.']
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
