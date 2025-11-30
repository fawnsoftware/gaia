<?php

declare(strict_types=1);

namespace Arrowtide\Gaia\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Statamic\Facades\YAML;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'gaia:export')]
class ExportCommand extends Command
{
    protected $signature = 'gaia:export {export_path}';

    protected $description = 'Exports stubs from the working site to the package stubs directory';

    public function handle(): bool
    {
        $this->info('Exporting...');

        $this->export();

        return true;
    }

    protected function export(): void
    {
        $fs = new Filesystem;

        $directory = $this->argument('export_path');
        $exportRoot = realpath(base_path($directory)) ?: base_path($directory);
        $exportRoot = rtrim($exportRoot, '/').'/stubs';

        $this->info("Exporting to: {$exportRoot}");

        foreach ($this->exportPathsConfig() as $path) {

            $from = base_path($path);

            $to = $exportRoot.'/'.$path;

            $fs->delete($to);
            $fs->deleteDirectory($to);

            if (! $fs->exists($from)) {
                $this->warn("Source {$from} does not exist, skipping.");
            }

            if ($fs->exists($to)) {
                $this->warn("{$to} already exists, skipping.");
            }

            $this->info("Copying {$path}");

            $fs->ensureDirectoryExists(dirname($to));

            if (is_dir($from)) {
                $fs->copyDirectory($from, $to);
            } else {
                $fs->copy($from, $to);
            }
        }

        $this->info('Gaia stubs exported successfully.');
    }

    /**
     * Gets the export paths required to install Gaia
     */
    protected function exportPathsConfig(): array
    {
        return collect(
            YAML::parse((new Filesystem)->get(__DIR__.'/../../src/paths.yaml'))
        )->get('export_paths');
    }
}
