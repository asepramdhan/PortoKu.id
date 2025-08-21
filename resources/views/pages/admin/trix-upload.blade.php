<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// Handle the file upload for Trix editor
// Folio will automatically map this file to the /admin/trix-upload endpoint
// when a POST request is made.

// We are not using a class-based component here, just a simple script.
// Get the uploaded file from the request
$file = request()->file('file');

if (!$file) {
    return response()->json(['error' => 'No file uploaded.'], 422);
}

// Store the file in the 'public/attachments' directory.
// The 'public' disk is defined in config/filesystems.php
$path = $file->store('attachments', 'public');

// Get the public URL for the stored file.
$url = Storage::disk('public')->url($path);

// Return the URL as a JSON response.
// Trix will use this URL to display the image.
return response()->json(['url' => $url]);

?>
