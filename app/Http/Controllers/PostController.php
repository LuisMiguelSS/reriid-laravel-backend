<?php

namespace App\Http\Controllers;

use App\Post;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    /**
     *  Attributes
     * 
     */
    const MAX_FILES_POST = 4;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $posts = Post::all();
        $count = count($posts);

        if ($count == 0) {
            return response()->noContent();
        }

        return response()->json([
            'count' => $count,
            'data' => $posts
        ]);
    }
    public function indexdeleted()
    {
        $posts = Post::onlyTrashed()->get();
        $count = count($posts);

        if ($count == 0) {
            return response()->noContent();
        }

        return response()->json([
            'count' => $count,
            'data' => $posts
        ]);
    }

    public function show($id)
    {
        return response()->json([
            'data' => Post::findOrFail($id)
        ]);
    }

    public function showuser($userid) {
        $posts = Post::all()->where('user_id', $userid);
        $count = count($posts);

        if ($count == 0) {
            return response()->noContent();
        }

        return response()->json([
            'count' => $count,
            'data' => $posts
        ]);
    }

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
            ], 422);
        }
        
        // Continue creating the user
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
                        ], 422);
                    }

                    $images = array();

                    // Save images and update the post pictures
                    foreach ($request->file('images') as $photo) {
                        array_push($images, $this->store_file($post, $photo));
                    }

                    $post->images = stripslashes(json_encode($images, JSON_UNESCAPED_SLASHES));
                    $post->save();

                } catch (\Throwable $throwable) {
                    return response()->json([
                        'errors' => ['The image could not be saved']
                    ], 500);
                }

            }

            return response()->json([
                'message' => 'Post created succesfully!',
                'data' => $post
            ], 201);

        } catch(\Illuminate\Database\QueryException $qe) {
            return response()->json([
                'errors' => ['The post could not be created. Does the user exist?']
            ], 500);
        }
        
    }

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
            if (count(json_decode($post->images)) < self::MAX_FILES_POST) {
                if ($request->hasFile('images')) {
                    try {
    
                        // Check the image array
                        $validator = Validator::make($request->all(), [
                            'images.*' => 'required|max:2048|file|mimes:jpg,jpeg,png,gif'
                        ]);

                        if ($validator->fails()) {
                            return response()->json([
                                'errors' => $validator->errors()
                            ], 422);
                        }

                        $images = json_decode($post->images);

                        // Save images and update the post pictures
                        foreach ($request->file('images') as $photo) {
                            array_push($images, $this->store_file($post, $photo));
                        }

                        $post->images = stripslashes(json_encode($images, JSON_UNESCAPED_SLASHES));
                        $post->save();
    
                    } catch (\Throwable $throwable) {
                        return response()->json([
                            'errors' => ['The image could not be saved']
                        ], 500);
                    }
                }
            } else {
                array_push($custom_error_messages, 'Maximum number of files per post ('. self::MAX_FILES_POST .') reached.');
            }
            

            // Save updates
            try {

                if (count($custom_error_messages) > 0) {
                    return response()->json([
                        'errors' => $custom_error_messages
                    ], 200);
                }

                $post->save();

                return response()->json([
                    'data' => $post
                ], 200);

            } catch (QueryException $queryException) {
                return response()->json([
                    'errors' => ['The post could not be modified']
                ], 500);
            }
        }
    }

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
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'The post could not be deleted.'
            ], 500);
        }
    }

    /**
     * Saves the indicated file to the passed Post's folder.
     *
     * @param  \App\Post  $post
     * @param  \Illuminate\Support\Facades\File  $file
     * @return url
     */
    /**
     * store_file
     * @return url 
     */
    public function store_file($post, $file)
    {
        if ($post == null || !($post instanceof Post) || $file == null) {
            return null;
        }

        try {
            $post_storage_folder = public_path() . '/uploads/user/'. $post->user_id . '\/post/' . $post->id . '/';

            // Check if Post folder exists
            if (!File::exists($post_storage_folder)) {
                File::makeDirectory($post_storage_folder, 0755, true);
            }

            // Store (and replace if necessary) file
            $filename = self::create_file_number($post_storage_folder) . '-' . date('d_M_Y') . '.' . $file->getClientOriginalExtension();

            if (File::exists($post_storage_folder . $filename)) {
                File::delete($post_storage_folder . $filename);
            }

            // Store file
            $file->move($post_storage_folder, $filename);

            return url('uploads/user/'. $post->user_id . '\/post/' . $post->id . '/' . $filename);

        } catch(Throwable $throwable) {
            return null;
        }
    }

    public static function create_file_number($folder) {
        if (File::exists($folder)) {
            return count(glob($folder.'*'));
        }

        return 0;
    }
}
