<?php

use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/upload', [UploadController::class, 'upload'])->name('upload');
Route::get('/recent-uploads', [UploadController::class, 'recentUploads'])->name('recent-uploads');
