<?php

namespace App\Services;

use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use Google_Service_Drive_Permission;

class GoogleDriveService
{
    protected $service;

    public function __construct()
    {
        $client = new Google_Client();
        $clientId = env('GOOGLE_DRIVE_CLIENT_ID');
        $clientSecret = env('GOOGLE_DRIVE_CLIENT_SECRET');
        $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');

        if (empty($clientId) || empty($clientSecret) || empty($refreshToken)) {
            throw new \RuntimeException('Google Drive OAuth credentials are missing. Set GOOGLE_DRIVE_CLIENT_ID, GOOGLE_DRIVE_CLIENT_SECRET, and GOOGLE_DRIVE_REFRESH_TOKEN.');
        }

        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->refreshToken($refreshToken);
        $client->addScope(Google_Service_Drive::DRIVE);

        $this->service = new Google_Service_Drive($client);
    }

    public function uploadFile($file, $fileName, $rootFolderId = null)
    {
        $rootFolderId = $rootFolderId ?: trim((string) env('GOOGLE_DRIVE_FOLDER_ID', env('GOOGLE_DRIVE_FOLDER', '')));

        if ($rootFolderId === '') {
            throw new \RuntimeException('GOOGLE_DRIVE_FOLDER_ID is required for Google Drive uploads.');
        }

        $chequeFolderId = $this->ensureFolder('Cheque', $rootFolderId);
        $yearFolderId = $this->ensureFolder(date('Y'), $chequeFolderId);
        $monthFolderId = $this->ensureFolder(date('m'), $yearFolderId);

        $filePath = is_string($file) ? $file : $file->getRealPath();
        $mimeType = method_exists($file, 'getMimeType') && $file->getMimeType() ? $file->getMimeType() : mime_content_type($filePath);

        $fileMetadata = new Google_Service_Drive_DriveFile([
            'name' => $fileName,
            'parents' => [$monthFolderId],
        ]);

        $content = file_get_contents($filePath);

        $uploadedFile = $this->service->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id,webViewLink'
        ]);

        // Make file public
        $this->service->permissions->create($uploadedFile->id, new Google_Service_Drive_Permission([
            'type' => 'anyone',
            'role' => 'reader',
        ]));

        return $uploadedFile->webViewLink ?: ('https://drive.google.com/file/d/' . $uploadedFile->id . '/view');
    }

    public function deleteFile($fileUrl)
    {
        try {
            // Extract file ID from URL: https://drive.google.com/file/d/FILE_ID/view
            if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $fileUrl, $matches)) {
                $fileId = $matches[1];
                $this->service->files->delete($fileId);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Google Drive deletion failed.', ['error' => $e->getMessage(), 'url' => $fileUrl]);
            return false;
        }
    }

    public function getDirectImageUrl($fileUrl)
    {
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $fileUrl, $matches)) {
            return "https://lh3.googleusercontent.com/u/0/d/" . $matches[1];
        }
        return $fileUrl;
    }

    protected function ensureFolder(string $name, string $parentId): string
    {
        $escapedName = str_replace("'", "\\'", $name);
        $query = sprintf(
            "name='%s' and mimeType='application/vnd.google-apps.folder' and '%s' in parents and trashed=false",
            $escapedName,
            $parentId
        );

        $results = $this->service->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'fields' => 'files(id,name)',
            'pageSize' => 1,
        ]);

        $files = $results->getFiles();
        if (!empty($files)) {
            return $files[0]->getId();
        }

        $folderMetadata = new Google_Service_Drive_DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId],
        ]);

        $createdFolder = $this->service->files->create($folderMetadata, ['fields' => 'id']);

        if (!$createdFolder || !$createdFolder->getId()) {
            throw new \RuntimeException('Unable to create Google Drive folder: ' . $name);
        }

        return $createdFolder->getId();
    }
}
