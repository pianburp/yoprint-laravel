<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCsvUpload;
use App\Models\Upload;
use App\Traits\Utf8Cleaner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class UploadController extends Controller
{
    /**
     * Handle CSV file upload
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();

        // Read raw content, clean non-UTF-8 characters and compute a stable hash.
        $rawContent = file_get_contents($file->getRealPath());
        $cleanedContent = Utf8Cleaner::cleanUtf8($rawContent);

        // Use sha1 of cleaned content to derive a stable filename so repeated uploads of the same file
        // don't create duplicate upload records.
        $hash = sha1($cleanedContent);
        $sanitizedOriginal = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $fileName = $hash . '_' . $sanitizedOriginal;

        // Store cleaned content; overwrite if same file already exists.
        $filePath = Storage::put('uploads/' . $fileName, $cleanedContent);

        if ($filePath === false) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save the uploaded file. Please try again.',
            ], 500);
        }

        // Create or update upload entry (idempotent)
        // Create or update upload entry (idempotent). Update uploaded_at to now for repeated uploads.
        $upload = Upload::updateOrCreate(
            ['file_name' => $fileName],
            ['status' => 'pending', 'uploaded_at' => Carbon::now()]
        );

        // Dispatch the job to process the file in background
        ProcessCsvUpload::dispatch($upload);

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully and is being processed.',
            'upload' => [
                'id' => $upload->id,
                'file_name' => $originalName,
                'status' => $upload->status,
                'uploaded_at' => $upload->uploaded_at,
            ]
        ], 201);
    }

    /**
     * Get recent uploads
     *
     * @param int $limit
     * @return \Illuminate\Http\JsonResponse
     */
    public function recentUploads($limit = 10)
    {
        try {
            $uploads = Upload::orderBy('uploaded_at', 'desc')
                ->take($limit)
                ->get()
                ->map(function ($upload) {
                    return [
                        'time' => $upload->uploaded_at,
                        'fileName' => $upload->file_name,
                        'status' => ucfirst($upload->status),
                    ];
                });

            return response()->json($uploads, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch uploads: ' . $e->getMessage(),
            ], 500);
        }
    }

}
