<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaUploadController extends Controller
{
    //
public function store(Request $request)
    {
        // Validasi API Key
        if ($request->header('x-api-key') !== env('WHATSAPP_WEBHOOK_KEY')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'file'          => 'required|file|max:15360', // 15MB
            'message_id'    => 'required|string',
            'sender_number' => 'required|string',
            'media_type'    => 'required|string',
            'mime_type'     => 'nullable|string',
            'caption'       => 'nullable|string',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();
        $originalName = $file->getClientOriginalName();

        // Simpan ke storage/public
        $folder = 'whatsapp/' . date('Y/m');
        $filename = Str::uuid() . '.' . $extension;
        $path = $file->storeAs($folder, $filename, 'public');

        // Buat record di tabel media
        $media = Media::create([
            'model_type' => \App\Models\Message::class,
            'model_id'   => 0,
            'collection' => 'whatsapp-incoming',
            'file_path'  => $path,
            'file_name'  => $originalName,
            'mime_type'  => $mimeType,
            'file_type'  => $request->media_type,
            'size'       => $size,
            'file_size'  => $this->formatBytes($size),
        ]);

        return response()->json([
            'success' => true,
            'id'      => $media->id,
            'path'    => $path,
            'url'     => Storage::url($path),
        ]);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
    }
}
