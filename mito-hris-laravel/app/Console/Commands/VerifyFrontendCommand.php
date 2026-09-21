<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VerifyFrontendCommand extends Command
{
    protected $signature = 'mito:verify-frontend {--build-path : Print the Vite build directory after a successful check}';

    protected $description = 'Verify Vite build output exists so public and HR pages do not 404';

    /**
     * @var list<string>
     */
    private const REQUIRED_ENTRIES = [
        'resources/js/app.js',
        'resources/scss/app.scss',
        'resources/scss/public.scss',
    ];

    public function handle(): int
    {
        $manifestPath = public_path('build/manifest.json');
        if (!is_file($manifestPath)) {
            $this->error('public/build/manifest.json is missing. Run npm run build.');

            return self::FAILURE;
        }

        $entries = json_decode((string) file_get_contents($manifestPath), true);
        if (!is_array($entries) || $entries === []) {
            $this->error('Vite manifest is empty or invalid.');

            return self::FAILURE;
        }

        $missing = [];
        foreach (self::REQUIRED_ENTRIES as $key) {
            $file = is_array($entries[$key] ?? null) ? ($entries[$key]['file'] ?? null) : null;
            if (!is_string($file) || $file === '') {
                $missing[] = $key;
                continue;
            }

            $absolute = public_path('build/' . ltrim($file, '/'));
            if (!is_file($absolute)) {
                $missing[] = $file;
            }
        }

        if ($missing !== []) {
            $this->error('Missing Vite files: ' . implode(', ', $missing));

            return self::FAILURE;
        }

        if ($this->option('build-path')) {
            $this->output->writeln(public_path('build'));

            return self::SUCCESS;
        }

        $this->info('Vite frontend assets are present.');

        return self::SUCCESS;
    }
}
