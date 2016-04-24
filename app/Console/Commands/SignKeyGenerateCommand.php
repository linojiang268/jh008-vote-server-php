<?php
namespace Jihe\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class SignKeyGenerateCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'signkey:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the key for signing response or verifying request';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $key = $this->genRandomKey();

        if ($this->option('show')) {
            return $this->line('<comment>'.$key.'</comment>');
        }

        $path = base_path('.env');

        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                $this->laravel['config']['signature.key'], $key, file_get_contents($path)
            ));
        }

        $this->laravel['config']['signature.key'] = $key;

        $this->info("Sign key [$key] set successfully.");
    }

    /**
     * Generate a random signature key.
     *
     * @return string
     */
    private function genRandomKey()
    {
        return strtoupper(str_random(32));
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['show', null, InputOption::VALUE_NONE, 'Simply display the key instead of modifying files.'],
        ];
    }
}
