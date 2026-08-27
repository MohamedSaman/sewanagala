<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HandlesChequeUploads
{
    /**
     * Upload a cheque photo to storage and return the path.
     *
     * @param mixed $photo The uploaded file instance.
     * @param string|null $chequeNumber The cheque number for naming.
     * @param string|null $chequeDate The cheque date for directory organization.
     * @return string|null The path to the stored photo.
     */
    public function uploadChequePhotoToStorage($photo, $chequeNumber = null, $chequeDate = null)
    {
        if (!$photo) {
            return null;
        }

        try {
            // Create a directory structure based on date if provided
            $date = $chequeDate ? date('Y-m', strtotime($chequeDate)) : date('Y-m');
            $directory = "cheques/{$date}";

            // Generate a clean filename
            $extension = $photo->getClientOriginalExtension();
            $cleanNumber = $chequeNumber ? Str::slug($chequeNumber) : Str::random(10);
            $filename = "cheque_{$cleanNumber}_" . time() . ".{$extension}";

            // Store the file in the public disk
            return $photo->storeAs($directory, $filename, 'public');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Cheque photo upload failed', [
                'error' => $e->getMessage(),
                'cheque_number' => $chequeNumber
            ]);
            return null;
        }
    }

    /**
     * Resolve the full URL for a stored cheque photo.
     *
     * @param string|null $path The stored path.
     * @return string|null The full URL.
     */
    public function resolveChequePhotoUrl($path)
    {
        if (!$path) {
            return null;
        }

        // If it's already a full URL, return it
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        // Otherwise, resolve via the public storage disk
        return asset('storage/' . $path);
    }

    /**
     * Alias for resolveChequePhotoUrl (for compatibility).
     */
    public function resolveChequePhotoPreviewUrl($path)
    {
        return $this->resolveChequePhotoUrl($path);
    }
}
