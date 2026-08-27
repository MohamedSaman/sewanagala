<?php

namespace App\Providers;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Models\StaffPermission;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Storage::extend('google', function ($app, $config) {
            $options = [];

            if (!empty($config['teamDriveId'] ?? null)) {
                $options['teamDriveId'] = $config['teamDriveId'];
            }

            if (!empty($config['sharedFolderId'] ?? null)) {
                $options['sharedFolderId'] = $config['sharedFolderId'];
            }

            // When a folder id is configured, use it as shared folder id for this adapter.
            if (empty($options['sharedFolderId'] ?? null) && !empty($config['folder'] ?? null)) {
                $options['sharedFolderId'] = $config['folder'];
            }

            $client = new Client();

            // Use service account authentication with proper newline handling
            $privateKey = env('GOOGLE_DRIVE_PRIVATE_KEY', '');
            // Convert literal \n to actual newlines
            $privateKey = str_replace('\\n', "\n", $privateKey);

            $serviceAccountConfig = [
                'type' => 'service_account',
                'project_id' => env('GOOGLE_DRIVE_PROJECT_ID'),
                'private_key_id' => env('GOOGLE_DRIVE_PRIVATE_KEY_ID'),
                'private_key' => $privateKey,
                'client_email' => env('GOOGLE_DRIVE_CLIENT_EMAIL'),
                'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
                'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                'token_uri' => 'https://oauth2.googleapis.com/token',
                'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
            ];

            $client->setAuthConfig($serviceAccountConfig);
            $client->addScope(Drive::DRIVE);

            $service = new Drive($client);
            $folder = null;

            $adapter = new GoogleDriveAdapter($service, $folder, $options);
            $driver = new Filesystem($adapter);

            return new FilesystemAdapter($driver, $adapter);
        });

        // Register custom Blade directive for permission checking
        Blade::if('permission', function ($permission) {
            if (!auth()->check()) {
                return false;
            }

            // Admin has all permissions
            if (auth()->user()->role === 'admin') {
                return true;
            }

            // Check if staff has permission
            if (auth()->user()->role === 'staff') {
                // If staff has no permissions assigned, grant full access by default
                $hasAnyPermissions = StaffPermission::where('user_id', auth()->id())->exists();
                if (!$hasAnyPermissions) {
                    return true; // Full access when no permissions are set
                }

                // If permissions are assigned, check for specific permission
                return StaffPermission::hasPermission(auth()->id(), $permission);
            }

            return false;
        });

        // Check if user is admin
        Blade::if('admin', function () {
            return auth()->check() && auth()->user()->role === 'admin';
        });

        // Check if user is staff
        Blade::if('staff', function () {
            return auth()->check() && auth()->user()->role === 'staff';
        });
    }
}
