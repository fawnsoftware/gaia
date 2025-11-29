<?php

declare(strict_types=1);

namespace Arrowtide\Gaia\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Statamic\Facades\Collection;
use Statamic\Facades\YAML;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'gaia:install')]
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gaia:install
        {--file= : Use a custom configuration file }
        {--with-symlinks : Create symbolic links during installation }
        {--force-symlinks : Force the creation of symbolic links by deleting existing folders or files }
        ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Gaia starter kit';

    /**
     * Execute the console command.
     */
    public function handle(): bool
    {
        $this->info('Installing Gaia components and resources...');

        // If --with-symlinks is set, create symlinks instead of copying the files over.
        if ($this->option('with-symlinks')) {
            $this->createSymlinksFromConfig();
        } else {
            $this->copyResourcesFromConfig();
        }

        $this->setProductCollectionTemplate();

        return true;
    }

    /**
     * Create symlinks from the config file
     */
    protected function createSymlinksFromConfig(): void
    {
        foreach ($this->exportPathsConfig() as $path) {
            $from = $this->stubLocation($path);
            $to = base_path($path);

            if ($this->option('force-symlinks')) {
                (new Filesystem)->delete($to);
                (new Filesystem)->deleteDirectory($to);
            }

            if ((new Filesystem)->exists($to)) {
                $this->warn("{$from} already exists, skipping.");

                continue;
            }

            $this->info("Symlinking {$from} to {$to}...");

            (new Filesystem)->ensureDirectoryExists(base_path(dirname($path)));
            (new Filesystem)->link($from, $to);
        }

        $this->info('Gaia scaffolding installed successfully.');
    }

    /**
     * Copy resources from the config file
     */
    protected function copyResourcesFromConfig(): void
    {
        foreach ($this->exportPathsConfig() as $path) {
            if (is_dir($this->stubLocation($path))) {
                $this->info("Copying {$path}...");
                (new Filesystem)->ensureDirectoryExists(base_path($path));
                (new Filesystem)->copyDirectory($this->stubLocation($path), base_path($path));
            }

            if (is_file($this->stubLocation($path))) {
                $this->info("Copying {$path}...");
                (new Filesystem)->ensureDirectoryExists(base_path(dirname($path)));
                copy($this->stubLocation($path), base_path($path));
            }
        }

        $this->info('Gaia scaffolding installed successfully.');
    }

    /**
     * Gets the export paths required to install Gaia
     */
    protected function exportPathsConfig(): array
    {
        if ($this->option('file')) {
            return collect(YAML::parse((new Filesystem)->get($this->option('file'))))->get('export_paths');
        }

        return collect(YAML::parse((new Filesystem)->get(__DIR__.'/../../src/paths.yaml')))->get('export_paths');
    }

    protected function setProductCollectionTemplate(): void
    {
        if (! Collection::find('products')) {
            $this->warn("Can't find [products] collection whilst setting collection template. This means the Shopify Addon has not been installed correctly.");

            return;
        }

        $this->info('Setting [product] collection template to [shop/product/default/index]');
        $collection = Collection::find('products');
        $collection->template('shop/product/default/index');
        $collection->save();
    }

    /**
     * Gets the absolute path to the stub location in the addon
     */
    protected function stubLocation($path): string
    {
        return Str::replaceLast('/src/Console', '', __DIR__).'/stubs/'.$path;
    }
}
