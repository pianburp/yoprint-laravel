<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCsvUpload;
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file. Please upload a valid CSV file.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            
            // Generate a unique filename to prevent conflicts
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            
            // Store the file in storage/app/uploads
            $path = $file->storeAs('uploads', $filename);

            // Create upload record
            $upload = Upload::create([
                'file_name' => $filename,
                'status' => 'pending',
            ]);

            // Dispatch job to process CSV
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
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recent uploads
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function recentUploads()
    {
        try {
            $uploads = Upload::orderBy('uploaded_at', 'desc')
                ->take(10)
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
