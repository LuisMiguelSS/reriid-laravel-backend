<?php

namespace App\Http\Controllers\File;

use App\Post;
use App\User;
use Throwable;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    public const MAX_FILE_SIZE = 4096;
    public const VALID_MIMES = array('jpg', 'jpeg', 'png', 'gif');

    /**
     * Creates (if needed) a folder for the user
     * and stores the given image in it.
     * 
     * @param User $user
     * @param Input $file
     * 
     * @return url The url of the saved file or null if there was a problem.
     * 
     */
    public static function store_profilepic(User $user, $file)
    {
        try {
            if ($user == null || $file == null) {
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
        } catch(Throwable $exception) {
            Log::debug('Error trying to store user image:\n' . $exception);
            return null;
        }
    }

    /**
     * Saves the indicated file to the passed Post's folder.
     *
     * @param  \App\Post  $post
     * @param  Input  $file
     * @return url
     *
     */
    public static function store_postimage(Post $post, $file)
    {
        if ($post == null || $file == null) {
            return null;
        }

        try {
            $post_storage_folder = public_path() . '/uploads/user/'. $post->user_id . '/post/' . $post->id . '/';

            // Check if Post folder exists
            if (!File::exists($post_storage_folder)) {
                File::makeDirectory($post_storage_folder, 0755, true);
            }

            // Store (and replace if necessary) file
            $filename = self::create_file_number($post_storage_folder) . '-' . date('d_M_Y') . '.'
                        . $file->getClientOriginalExtension();

            if (File::exists($post_storage_folder . $filename)) {
                File::delete($post_storage_folder . $filename);
            }

            // Store file
            $file->move($post_storage_folder, $filename);

            return url('uploads/user/'. $post->user_id . '/post/' . $post->id . '/' . $filename);

        } catch(Throwable $throwable) {
            return null;
        }
    }

    /**
     * Creates a number depending on
     * the number of files in the folder.
     * 
     * @param string $folder A folder from where count the files.
     * @return int The generated number.
     * 
     */
    private static function create_file_number($folder) {
        if (!File::exists($folder)) {
            return 0;
        }

        return count(glob($folder.'*'));
    }

}
