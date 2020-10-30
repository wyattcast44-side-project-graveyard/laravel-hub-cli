<?php

namespace LaravelHub\Installer\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;

class ComposeCommand extends Command
{
    protected $signature = 'compose';

    protected $description = 'Compose a new Laravel application using a compose file';

    /**
     * Flag for installing the lastest dev
     */
    private $dev = false;

    /**
     * Flag whether or not to force install if already exists
     */
    private $force = false;

    /**
     * The name for the directory to install into
     */
    private $appName;

    /**
     * The version of Laravel to install
     */
    private $version;

    /**
     * The command to use when running composer
     */
    private $composer;

    /**
     * The command stack to run
     */
    private $commands;

    public function handle()
    {
        $this->dev = $this->option('dev');
        $this->force = $this->option('force');
        $this->appName = $this->argument('name');

        $this->printBanner()
            ->determineInstallPath()
            ->ensureSafeToInstall()
            ->determineVersionToInstall()
            ->ensureComposerIsAccessible()
            ->installLaravelUsingComposer()
            ->installComposerDependencies()
            ->updateEnvFileWithDefaults()
            ->copyEnvExampleToEnv()
            ->ensureArtisanIsExecutable()
            ->generateAppKey();

        $this->info("\nApplication ready! Build something amazing!");

        return 0;
    }

    protected function printBanner()
    {
        $this->line("
 _                               _   _    _       _     
| |                             | | | |  | |     | |    
| |     __ _ _ __ __ ___   _____| | | |__| |_   _| |__  
| |    / _` | '__/ _` \ \ / / _ \ | |  __  | | | | '_ \ 
| |___| (_| | | | (_| |\ V /  __/ | | |  | | |_| | |_) |
|______\__,_|_|  \__,_| \_/ \___|_| |_|  |_|\__,_|_.__/ " . PHP_EOL, 'fg=red');

        return $this;
    }

    protected function determineInstallPath()
    {
        $this->path = ($this->appName === '.') ? getcwd() : getcwd() . '/' . $this->appName;

        return $this;
    }

    protected function ensureSafeToInstall()
    {
        if ($this->doesDirectoryExist() && !$this->force) {
            if (!$this->confirm('Application/directory already exists, do you want to overide the directory?')) {
                $this->error('Installation cancelled!');

                die(1);
            } else {
                tap(new Filesystem, function ($fs) {
                    $fs->remove($this->path);
                });
            }
        }

        return $this;
    }

    protected function determineVersionToInstall()
    {
        $version = '';

        if ($this->dev) {
            $version = 'dev-develop';
        }

        $this->version = $version;

        return $this;
    }

    protected function ensureComposerIsAccessible()
    {
        $path = getcwd() . '/composer.phar';

        if (file_exists($path)) {
            $path = '"' . PHP_BINARY . '" ' . $path;
        } else {
            $path = 'composer';
        }

        $this->composer = $path;

        return $this;
    }

    protected function installLaravelUsingComposer()
    {
        $this->info('Crafting Your Application');
        $this->info('====================================');
        sleep(0.75);
        $this->line('==> Installing laravel/laravel');

        $command = $this->composer . " create-project laravel/laravel" . " " .
            $this->path . " " .
            trim($this->version . " ") .
            " --remove-vcs --prefer-dist --no-install --no-scripts";

        $process = $this->runProcess($command, null, 120, false);

        if (!$process->isSuccessful()) {
            $this->error('An error occured while installing Laravel!');
            die();
        }

        return $this;
    }

    protected function installComposerDependencies()
    {
        $this->line('==> Installing composer dependencies');

        $process = $this->runProcess($this->composer . " install", $this->path, 60 * 5, false);

        if (!$process->isSuccessful()) {
            $this->error('An error occured while installing dependencies!');
            die();
        }

        return $this;
    }

    protected function updateEnvFileWithDefaults()
    {
        $this->line('==> Updating .env file with defaults');

        $this->replaceInFile(
            'APP_NAME=Laravel',
            'APP_NAME=' . ucwords($this->appName),
            $this->path . DIRECTORY_SEPARATOR . '.env.example'
        );

        $this->replaceInFile(
            'APP_URL=http://localhost',
            'APP_URL=http://' . $this->appName . '.test',
            $this->path . DIRECTORY_SEPARATOR . '.env.example'
        );

        $this->replaceInFile(
            'MAIL_MAILER=smtp',
            'MAIL_MAILER=log',
            $this->path . DIRECTORY_SEPARATOR . '.env.example'
        );

        $this->replaceInFile(
            'DB_DATABASE=laravel',
            'DB_DATABASE=' . str_replace('-', '_', strtolower($this->name)),
            $this->path . DIRECTORY_SEPARATOR . '.env.example'
        );

        return $this;
    }

    protected function copyEnvExampleToEnv()
    {
        $this->line('==> Copying .env.example to .env');

        (new Filesystem)->copy(
            $this->path . DIRECTORY_SEPARATOR . '.env.example',
            $this->path . DIRECTORY_SEPARATOR . '.env'
        );

        return $this;
    }

    protected function ensureArtisanIsExecutable()
    {
        (new Filesystem)->chmod($this->path . '/artisan', 755);

        return $this;
    }

    protected function generateAppKey()
    {
        $this->line('==> Generating app key');

        $this->runProcess(PHP_BINARY . ' artisan key:generate', $this->path, 60, false);

        return $this;
    }

    protected function doesDirectoryExist()
    {
        if ((is_dir($this->path) || is_file($this->path)) && $this->path != getcwd()) {
            return true;
        }

        return false;
    }

    protected function runProcess($command, $path = null, $timeout = 120, $showOutput = true)
    {
        if ($this->option('no-ansi')) {
            $command = $command . " --no-ansi";
        }

        if ($this->option('quiet')) {
            $command = $command . " --quiet";
        }

        $process = Process::fromShellCommandline($command, ($path == null) ? getcwd() : $path);

        $process->setTimeout($timeout);

        $process->setTty(Process::isTtySupported());

        $process->run(function ($type, $line) use ($showOutput) {
            if ($showOutput) {
                echo $line;
            }
        });

        return $process;
    }

    /**
     * Replace the given string in the given file.
     *
     * @param  string  $search
     * @param  string  $replace
     * @param  string  $file
     * @return string
     */
    protected function replaceInFile(string $search, string $replace, string $file)
    {
        file_put_contents(
            $file,
            str_replace($search, $replace, file_get_contents($file))
        );
    }
}
