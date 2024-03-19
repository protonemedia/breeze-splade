<?php

namespace Laravel\Breeze\Console;

use Illuminate\Filesystem\Filesystem;
use ProtoneMedia\Splade\Commands\InstallsSpladeExceptionHandler;
use ProtoneMedia\Splade\Commands\InstallsSpladeRouteMiddleware;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Finder\Finder;

trait InstallsSpladeStack
{
    use InstallsSpladeExceptionHandler;
    use InstallsSpladeRouteMiddleware;

    /**
     * Install the Splade Breeze stack.
     *
     * @return void
     */
    protected function installSpladeStack()
    {
        // Check Laravel version...
        if (version_compare(app()->version(), '10.0', '<')) {
            $this->error('While you can still use Splade with Laravel 9, new projects should use Laravel 10.');

            return Command::FAILURE;
        }

        $this->installExceptionHandler();
        $this->installRouteMiddleware();

        // NPM Packages...
        $this->updateNodePackages(function ($packages) {
            return [
                '@protonemedia/laravel-splade' => '^1.4.16',
                '@tailwindcss/forms' => '^0.5.7',
                '@tailwindcss/typography' => '^0.5.10',
                '@vitejs/plugin-vue' => '^5.0',
                'autoprefixer' => '^10.4.16',
                'laravel-vite-plugin' => '^1.0',
                'postcss' => '^8.4.32',
                'tailwindcss' => '^3.4',
                'vite' => '^5.0',
                'vue' => '^3.4',
            ] + $packages;
        });

        // Add SSR build step...
        $this->updateNodeScript();

        $defaultStubsDir = __DIR__.'/../../stubs/default/';
        $spladeBreezeStubsDir = __DIR__.'/../../stubs/splade/';
        $spladeBaseStubsDir = base_path('vendor/protonemedia/laravel-splade/stubs/');

        // Controllers...
        (new Filesystem)->ensureDirectoryExists(app_path('Http/Controllers'));
        (new Filesystem)->copyDirectory($defaultStubsDir.'app/Http/Controllers', app_path('Http/Controllers'));

        // Requests...
        (new Filesystem)->ensureDirectoryExists(app_path('Http/Requests'));
        (new Filesystem)->copyDirectory($defaultStubsDir.'app/Http/Requests', app_path('Http/Requests'));

        // Views...
        (new Filesystem)->ensureDirectoryExists(resource_path('views'));
        (new Filesystem)->copyDirectory($spladeBreezeStubsDir.'resources/views', resource_path('views'));

        $this->removeDarkClasses(
            (new Finder)
                ->in(resource_path('views'))
                ->name('*.blade.php')
        );

        copy($spladeBreezeStubsDir.'resources/views/dashboard.blade.php', resource_path('views/dashboard.blade.php'));
        copy($spladeBreezeStubsDir.'resources/views/root.blade.php', resource_path('views/root.blade.php'));
        copy($spladeBreezeStubsDir.'resources/views/welcome.blade.php', resource_path('views/welcome.blade.php'));

        // Components...
        (new Filesystem)->ensureDirectoryExists(app_path('View/Components'));
        (new Filesystem)->copyDirectory($defaultStubsDir.'app/View/Components', app_path('View/Components'));

        // Tests...
        $this->installTests();

        // Routes...
        copy($spladeBreezeStubsDir.'routes/web.php', base_path('routes/web.php'));
        copy($spladeBreezeStubsDir.'routes/auth.php', base_path('routes/auth.php'));

        // Tailwind / Vite...
        copy($spladeBaseStubsDir.'tailwind.config.js', base_path('tailwind.config.js'));
        copy($spladeBaseStubsDir.'postcss.config.js', base_path('postcss.config.js'));
        copy($spladeBaseStubsDir.'vite.config.js', base_path('vite.config.js'));
        copy($spladeBaseStubsDir.'resources/css/app.css', resource_path('css/app.css'));
        copy($spladeBaseStubsDir.'resources/js/app.js', resource_path('js/app.js'));
        copy($spladeBaseStubsDir.'resources/js/ssr.js', resource_path('js/ssr.js'));

        if (file_exists(base_path('pnpm-lock.yaml'))) {
            $this->runCommands(['pnpm install', 'pnpm run build']);
        } elseif (file_exists(base_path('yarn.lock'))) {
            $this->runCommands(['yarn install', 'yarn run build']);
        } else {
            $this->runCommands(['npm install', 'npm run build']);
        }

        $this->line('');
        $this->components->info('Breeze scaffolding installed successfully.');
    }

    /**
     * Remove Tailwind dark classes from the given files.
     *
     * @return void
     */
    protected function removeDarkClasses(Finder $finder)
    {
        foreach ($finder as $file) {
            file_put_contents($file->getPathname(), preg_replace('/\sdark:[^\s"\']+/', '', $file->getContents()));
        }
    }

    /**
     * Adds the SSR build step to the 'build' command.
     *
     * @return void
     */
    protected function updateNodeScript()
    {
        if (! file_exists(base_path('package.json'))) {
            return;
        }

        $packageFile = file_get_contents(base_path('package.json'));

        file_put_contents(
            base_path('package.json'),
            str_replace('"vite build"', '"vite build && vite build --ssr"', $packageFile)
        );
    }
}
