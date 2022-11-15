<?php

namespace Laravel\Breeze\Console;

use Illuminate\Filesystem\Filesystem;
use ProtoneMedia\Splade\Commands\InstallsSpladeExceptionHandler;
use ProtoneMedia\Splade\Commands\InstallsSpladeRouteMiddleware;

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
        $this->installExceptionHandler();
        $this->installRouteMiddleware();

        // NPM Packages...
        $this->updateNodePackages(function ($packages) {
            return [
                '@protonemedia/laravel-splade' => '^0.7.0',
                '@tailwindcss/forms'           => '^0.5.2',
                '@tailwindcss/typography'      => '^0.5.2',
                '@vitejs/plugin-vue'           => '^3.0.0',
                'autoprefixer'                 => '^10.4.7',
                'laravel-vite-plugin'          => '^0.5.0',
                'postcss'                      => '^8.4.14',
                'tailwindcss'                  => '^3.1.0',
                'vite'                         => '^3.0.0',
                'vue'                          => '^3.2.37',
            ] + $packages;
        });

        // Add SSR build step...
        $this->updateNodeScript();

        $defaultStubsDir      = __DIR__ . '/../../stubs/default/';
        $spladeBreezeStubsDir = __DIR__ . '/../../stubs/splade/';
        $spladeBaseStubsDir   = base_path('vendor/protonemedia/laravel-splade/stubs/');

        // Controllers...
        (new Filesystem)->ensureDirectoryExists(app_path('Http/Controllers/Auth'));
        (new Filesystem)->copyDirectory($defaultStubsDir . 'App/Http/Controllers/Auth', app_path('Http/Controllers/Auth'));

        // Requests...
        (new Filesystem)->ensureDirectoryExists(app_path('Http/Requests/Auth'));
        (new Filesystem)->copyDirectory($defaultStubsDir . 'App/Http/Requests/Auth', app_path('Http/Requests/Auth'));

        // Views...
        (new Filesystem)->ensureDirectoryExists(resource_path('views/auth'));
        (new Filesystem)->ensureDirectoryExists(resource_path('views/layouts'));
        (new Filesystem)->ensureDirectoryExists(resource_path('views/components'));

        (new Filesystem)->copyDirectory($spladeBreezeStubsDir . 'resources/views/auth', resource_path('views/auth'));
        (new Filesystem)->copyDirectory($spladeBreezeStubsDir . 'resources/views/layouts', resource_path('views/layouts'));
        (new Filesystem)->copyDirectory($spladeBreezeStubsDir . 'resources/views/components', resource_path('views/components'));

        copy($spladeBreezeStubsDir . 'resources/views/dashboard.blade.php', resource_path('views/dashboard.blade.php'));
        copy($spladeBreezeStubsDir . 'resources/views/root.blade.php', resource_path('views/root.blade.php'));
        copy($spladeBreezeStubsDir . 'resources/views/welcome.blade.php', resource_path('views/welcome.blade.php'));

        // Components...
        (new Filesystem)->ensureDirectoryExists(app_path('View/Components'));
        (new Filesystem)->copyDirectory($defaultStubsDir . 'App/View/Components', app_path('View/Components'));

        // Tests...
        $this->installTests();

        // Routes...
        copy($spladeBreezeStubsDir . 'routes/web.php', base_path('routes/web.php'));
        copy($spladeBreezeStubsDir . 'routes/auth.php', base_path('routes/auth.php'));

        // "Dashboard" Route...
        $this->replaceInFile('/home', '/dashboard', app_path('Providers/RouteServiceProvider.php'));

        // Tailwind / Vite...
        copy($spladeBaseStubsDir . 'tailwind.config.js', base_path('tailwind.config.js'));
        copy($spladeBaseStubsDir . 'postcss.config.js', base_path('postcss.config.js'));
        copy($spladeBaseStubsDir . 'vite.config.js', base_path('vite.config.js'));
        copy($spladeBaseStubsDir . 'resources/css/app.css', resource_path('css/app.css'));
        copy($spladeBaseStubsDir . 'resources/js/app.js', resource_path('js/app.js'));
        copy($spladeBaseStubsDir . 'resources/js/ssr.js', resource_path('js/ssr.js'));

        $this->runCommands(['npm install', 'npm run build']);

        $this->line('');
        $this->components->info('Breeze scaffolding installed successfully.');
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
