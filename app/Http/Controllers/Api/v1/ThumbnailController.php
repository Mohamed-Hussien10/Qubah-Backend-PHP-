<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
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
            'folder' => 'nullable|string|in:stages,grades,sections,subjects,units,lessons,files'
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

        $path = str_replace('thumbnails/', '', $request->input('path'));

        if (\Illuminate\Support\Facades\Storage::disk('thumbnails')->exists($path)) {
            \Illuminate\Support\Facades\Storage::disk('thumbnails')->delete($path);
            return response()->json([
                'success' => true,
                'message' => 'Thumbnail deleted successfully.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Thumbnail not found.'
        ], 404);
    }
}
