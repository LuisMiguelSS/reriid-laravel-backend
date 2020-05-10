<?php

namespace App\Http\Controllers;

use App\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\File\FileController;

class PostController extends Controller
{
    /**
     *  Attributes
     * 
     */
    const MAX_FILES_PER_POST = 4;
    const MAX_POSTS_PER_USER = 20;

    /**
     * Getters
     * 
     */
    public function get_posts_user($id) {
        return Post::all()->where('user_id', $id);
    }


    /**
     * Returns a listing of the posts.
     *
     * @return Response An HTTP Response.
     * 
     */
    public function index()
    {
        $pagination_result = Post::paginate();

        if ($pagination_result->total() == 0) {
            return response()->noContent();
        }

        return response()->json($pagination_result);
    }

    /**
     * Returns a listing of the deleted posts.
     *
     * @return Response An HTTP Response.
     * 
     */
    public function indexdeleted()
    {
        $pagination_result = Post::onlyTrashed()->paginate();

        if ($pagination_result->total() == 0) {
            return response()->noContent();
        }

        return response()->json($pagination_result);
    }

    /**
     * Returns a paginated result for nearby posts.
     * 
     * @param Request In case the user didn't especify his location or chosen one,
     * it will be picked from their IP address.
     * 
     * @return array(Post)
     * 
     */
    public function nearby(Request $request) {
        $using_km = true;

        // Measurement unit
        if ($request->unit) {
            switch (strtoupper($request->unit)) {
                case 'MILES':
                case 'M':
                case 'MI':
                    $using_km = false;

                default:
                    break;
            }
        }

        // Distance
        $distance = 25;
        if($request->distance && is_int($request->distance)) {
            $distance = ($using_km)? $request->distance : $request->distance*1.60934;
        }

        $user = $request->user();
        
        try {

            $posts = DB::select(
                'SELECT 
                        p.id as post_id,
                        p.book_title as post_title,
                        p.images as post_images,
                        u.username,
                        u.profile_pic as user_profile_pic,
                        (st_distance_sphere(
                            point(:long_from, :lat_from),
                            point(u.longitude, u.latitude)
                        )/1000) as distance
                    FROM users as u, posts as p
                    WHERE u.id = p.user_id
                        and u.deleted_at IS NULL
                        and u.id <> ' . $user->id . '
                        and u.latitude IS NOT NULL
                        and u.longitude IS NOT NULL
                    HAVING distance <= ' . $distance
            , [
                'long_from' => $user->longitude,
                'lat_from' => $user->latitude
            ]);

            $number_of_results = count($posts);
    
            if ($number_of_results == 0) {
                return response()->noContent();
            }

            // Check for measurement unit
            if (!$using_km) {
                foreach ($posts as $post) {
                    $post->distance *= 0.62137;
                }

            } else {
                // Clean "0.00000..." values to "0"
                foreach ($posts as $post) {
                    if ($post->distance == 0) {
                        $post->distance = 0;
                    }
                }
            }

            return response()->json([
                'count' => $number_of_results,
                'data' => $posts
            ]);

        } catch(QueryException $queryException) {
            return response()->json([
                'errors' => ['We couldn\'t process the search']
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
    }

    /**
     * Returns an specific post.
     *
     * @param int $id The post ID.
     * @return Response An HTTP Response.
     * 
     */
    public function show($id)
    {
        return response()->json([
            'data' => Post::findOrFail($id)
        ]);
    }

    /**
     * Returns a listing of the posts of a given user.
     *
     * @param int $userid The user ID.
     * @return Response An HTTP Response.
     * 
     */
    public function showuser($userid) {
        $posts = self::get_posts_user($userid);
        $count = count($posts);

        if ($count == 0) {
            return response()->noContent();
        }

        return response()->json([
            'count' => $count,
            'data' => $posts
        ]);
    }

    /**
     * Saves a new Post to the database
     * 
     * @param Request $request An HTTP Request
     * @return Response An HTTP Response.
     * 
     */
    public function store(Request $request)
    {
        // Check for all fields except the pictures
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric',
            'description' => 'required|string|max:400',
            'images' => 'required|array',
            'images.*' => 'max:2048|file|mimes:jpg,jpeg,png,gif',
            'book_title' => 'required|string',
            'book_subtitle' => 'string',
            'book_synopsis' => 'string',
            'book_isbn' => 'numeric',
            'book_author' => 'string'
        ]);
         
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Count user posts and see if it exceeds the maximum
        if (count(self::get_posts_user($request->user_id)) >= self::MAX_POSTS_PER_USER) {
            return response()->json([
                'errors' => ['Maximum number of posts per user ('. self::MAX_POSTS_PER_USER .') reached.']
            ], Response::HTTP_CONFLICT);
        }

        // Continue creating the post
        try {

            $post = new Post;
            $post->user_id = $request->user_id;
            $post->description = $request->description;
            $post->book_title = $request->book_title;

            // Optional fields
            if ($request->book_subtitle) {
                $post->book_subtitle = $request->book_subtitle;
            }

            if ($request->book_synopsis) {
                $post->book_synopsis = $request->book_synopsis;
            }

            if ($request->book_isbn) {
                $post->book_isbn = $request->book_isbn;
            }

            if ($request->book_author) {
                $post->book_author = $request->book_author;
            }

            $post->save();

            if ($request->hasFile('images')) {
                try {

                    // Check the image array
                    $validator = Validator::make($request->all(), [
                        'images.*' => 'required|max:2048|file|mimes:jpg,jpeg,png,gif'
                    ]);

                    if ($validator->fails()) {
                        return response()->json([
                            'errors' => $validator->errors()
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    $images = array();

                    // Save images and update the post pictures
                    foreach ($request->file('images') as $photo) {
                        array_push($images, FileController::store_postimage($post, $photo));
                    }

                    $post->images = stripslashes(json_encode($images, JSON_UNESCAPED_SLASHES));
                    $post->save();

                } catch (\Throwable $throwable) {
                    return response()->json([
                        'errors' => ['The image could not be saved']
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }

            }

            return response()->json([
                'message' => 'Post created succesfully!',
                'data' => $post
            ], Response::HTTP_CREATED);

        } catch(\Illuminate\Database\QueryException $qe) {
            return response()->json([
                'errors' => ['The post could not be created. Does the user exist?']
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
    }

    /**
     * Edits/modifies the post info.
     * 
     * @param Request $request An HTTP request.
     * @param int $id The post ID.
     * @return Response An HTTP Response.
     * 
     */
    public function update(Request $request, $id)
    {
        $custom_error_messages = array();
        $post = Post::findOrFail($id);

        if ($post != null) {

            // Get Attributes
            if ($request->description) {
                $post->description = $request->description;
            }

            if ($request->reserved && is_numeric($request->reserved)) {
                $post->reserved = $request->reserved;
            }

            if ($request->views && is_numeric($request->views)) {
                $post->views = $request->views;
            }

            // Book Attributes
            if ($request->book_title) {
                $post->book_title = $request->book_title;
            }

            if ($request->book_subtitle) {
                $post->book_subtitle = $request->book_subtitle;
            }

            if ($request->book_synopsis) {
                $post->book_synopsis = $request->book_synopsis;
            }

            if ($request->book_isbn) {
                $post->book_isbn = $request->book_isbn;
            }

            if ($request->book_author) {
                $post->book_author = $request->book_author;
            }

            // Max photos per post
            if (count(json_decode($post->images)) < self::MAX_FILES_PER_POST) {
                if ($request->hasFile('images')) {
                    try {
    
                        // Check the image array
                        $validator = Validator::make($request->all(), [
                            'images.*' => 'required|max:2048|file|mimes:jpg,jpeg,png,gif'
                        ]);

                        if ($validator->fails()) {
                            return response()->json([
                                'errors' => $validator->errors()
                            ], Response::HTTP_UNPROCESSABLE_ENTITY);
                        }

                        $images = json_decode($post->images);

                        // Save images and update the post pictures
                        foreach ($request->file('images') as $photo) {
                            array_push($images, FileController::store_postimage($post, $photo));
                        }

                        $post->images = stripslashes(json_encode($images, JSON_UNESCAPED_SLASHES));
                        $post->save();
    
                    } catch (\Throwable $throwable) {
                        return response()->json([
                            'errors' => ['The image could not be saved']
                        ], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
                }
                
            } else {
                array_push($custom_error_messages, 'Maximum number of files per post ('. self::MAX_FILES_PER_POST .') reached.');
            }

            // Save updates
            try {

                if (count($custom_error_messages) > 0) {
                    return response()->json([
                        'errors' => $custom_error_messages
                    ], Response::HTTP_OK);
                }

                $post->save();

                return response()->json([
                    'data' => $post
                ], Response::HTTP_OK);

            } catch (QueryException $queryException) {
                return response()->json([
                    'errors' => ['The post could not be modified']
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }

    /**
     * Soft-deletes the post but hard-deletes its images
     * 
     * @param int $id The post ID.
     * @return Response An HTTP Response.
     * 
     */
    public function destroy($id)
    {
        try {

            // Soft delete Post
            $post = Post::findOrFail($id);
            $post->delete();

            // Delete images from disk
            foreach (json_decode($post->images) as $image) {
                if (File::exists(public_path($image))) {
                    File::delete(public_path($image));
    
                    // Update Post entry
                    $post->images = null;
                    $post->save();
                }
            }

            return response()->json([
                'message' => 'Post deleted succesfully!'
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'The post could not be deleted.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
