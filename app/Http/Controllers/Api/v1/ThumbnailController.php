<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\StorageCleaner;
use Illuminate\Http\Request;

class ThumbnailController extends Controller
{
    /**
     * Upload a thumbnail image.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'folder' => 'nullable|string|in:stages,grades,sections,subjects,units,lessons,files,free-trial/stages,free-trial/grades,free_trial_subjects'
        ]);

        if ($request->hasFile('thumbnail')) {
            $folder = $request->input('folder', 'general');
            $file = $request->file('thumbnail');
            
            // Store file to the thumbnails disk
            $path = $file->store($folder, 'thumbnails');
            
            return response()->json([
                'success' => true,
                'data' => [
                    'path' => 'thumbnails/' . $path,
                    'url' => url('storage/thumbnails/' . $path)
                ],
                'message' => 'Thumbnail uploaded successfully.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No file provided.'
        ], 400);
    }

    /**
     * Delete a thumbnail image.
     */
    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        $path = $request->input('path');

        if (StorageCleaner::deleteThumbnail($path)) {
            return response()->json([
                'success' => true,
                'message' => 'Thumbnail deleted successfully.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Thumbnail not found or is currently referenced by a database record.'
        ], 400);
    }
}
