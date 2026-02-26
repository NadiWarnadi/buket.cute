<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Product;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /**
     * Upload media for a model (Product, Message, etc)
     * 
     * Expected multipart form data:
     * - file: UploadedFile (required)
     * - model_type: string (required) - 'App\Models\Product', 'App\Models\Message'
     * - model_id: int (required) - ID of the model
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
        ]);

        try {
            // Validate model type is allowed
            $allowedModels = ['App\Models\Product', 'App\Models\Message', 'App\Models\Customer'];
            if (!in_array($validated['model_type'], $allowedModels)) {
                return response()->json(['error' => 'Invalid model type'], 422);
            }

            $file = $request->file('file');
            
            // Store file in storage/app/uploads/{model_type}/{model_id}/
            $modelName = class_basename($validated['model_type']);
            $path = "uploads/{$modelName}/{$validated['model_id']}/";
            $fileName = time() . '_' . $file->getClientOriginalName();
            
            $filePath = $file->storeAs($path, $fileName, 'local');

            // Create media record
            $model = $validated['model_type']::findOrFail($validated['model_id']);
            
            $media = new Media([
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);

            $model->media()->save($media);

            return response()->json([
                'success' => true,
                'media' => [
                    'id' => $media->id,
                    'file_name' => $media->file_name,
                    'url' => $media->getUrl(),
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'is_image' => $media->isImage(),
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get media details
     */
    public function show(Media $media)
    {
        return response()->json([
            'id' => $media->id,
            'file_name' => $media->file_name,
            'url' => $media->getUrl(),
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'is_image' => $media->isImage(),
            'is_document' => $media->isDocument(),
            'is_audio' => $media->isAudio(),
            'is_video' => $media->isVideo(),
        ]);
    }

    /**
     * Download media file
     */
    public function download(Media $media)
    {
        if (!Storage::disk('local')->exists($media->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return Storage::disk('local')->download(
            $media->file_path,
            $media->file_name
        );
    }

    /**
     * Delete media
     */
    public function destroy(Media $media)
    {
        try {
            // Check authorization - only owner or admin can delete
            $this->authorize('delete', $media);

            $media->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Delete failed'], 500);
        }
    }

    /**
     * Get all media for a model
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
        ]);

        $allowedModels = ['App\Models\Product', 'App\Models\Message', 'App\Models\Customer'];
        if (!in_array($validated['model_type'], $allowedModels)) {
            return response()->json(['error' => 'Invalid model type'], 422);
        }

        $media = Media::where('model_type', $validated['model_type'])
            ->where('model_id', $validated['model_id'])
            ->orderBy('is_featured', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($m) {
                return [
                    'id' => $m->id,
                    'file_name' => $m->file_name,
                    'url' => $m->getUrl(),
                    'mime_type' => $m->mime_type,
                    'size' => $m->size,
                    'is_featured' => $m->is_featured,
                    'is_image' => $m->isImage(),
                    'created_at' => $m->created_at,
                ];
            });

        return response()->json($media);
    }

    /**
     * Set media as featured
     */
    public function setFeatured(Media $media)
    {
        try {
            $this->authorize('update', $media);

            // Unfeatured all media for this model
            Media::where('model_type', $media->model_type)
                ->where('model_id', $media->model_id)
                ->update(['is_featured' => false]);

            // Set this as featured
            $media->update(['is_featured' => true]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Update failed'], 500);
        }
    }
}
