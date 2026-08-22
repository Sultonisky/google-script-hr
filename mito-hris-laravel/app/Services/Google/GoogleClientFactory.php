<?php

namespace App\Services\Google;

use Google\Client as GoogleClient;
use Google\Service\Sheets;
use Google\Service\Drive;
use Illuminate\Support\Facades\File;
use RuntimeException;

class GoogleClientFactory
{
    protected ?GoogleClient $client = null;

    /**
     * Create or retrieve singleton Google Client instance.
     */
    public function getClient(): GoogleClient
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $credentialsPath = config('google.credentials_path');

        $client = new GoogleClient();
        $client->setApplicationName('MITO HRIS Laravel');
        $client->setScopes([
            Sheets::SPREADSHEETS,
            Drive::DRIVE_FILE,
            Drive::DRIVE,
        ]);

        // Resolve credentials path using base_path if relative
        if (!File::exists($credentialsPath)) {
            // Try to resolve relative to project root
            $absolutePath = base_path($credentialsPath);
            if (File::exists($absolutePath)) {
                $credentialsPath = $absolutePath;
            }
        }

        if (File::exists($credentialsPath)) {
            $client->setAuthConfig($credentialsPath);
        } else {
            // Check if JSON content is provided via env
            $jsonCreds = env('GOOGLE_SERVICE_ACCOUNT_JSON');
            if (!empty($jsonCreds)) {
                $client->setAuthConfig(json_decode($jsonCreds, true));
            } else {
                throw new RuntimeException("Google credentials file not found at: {$credentialsPath}");
            }
        }

        $client->setAccessType('offline');

        $this->client = $client;
        return $this->client;
    }

    /**
     * Get Google Sheets Service instance.
     */
    public function getSheetsService(): Sheets
    {
        return new Sheets($this->getClient());
    }

    /**
     * Get Google Drive Service instance.
     */
    public function getDriveService(): Drive
    {
        return new Drive($this->getClient());
    }
}
