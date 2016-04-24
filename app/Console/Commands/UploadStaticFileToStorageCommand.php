<?php
namespace Jihe\Console\Commands;

use Illuminate\Console\Command;
use Jihe\Contracts\Services\Storage\StorageService;
use Symfony\Component\Console\Input\InputOption;

class UploadStaticFileToStorageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'static:upload';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload static files to storage.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(StorageService $storageService)
    {
        $filter = $this->input->hasOption('filter') ? $this->option('filter') : [];
        $filter = is_array($filter) ? $filter : [$filter];
        $filter = array_map(function ($f) {
            return $this->getLocalDir($f);
        }, $filter);

        $localDir = $this->input->hasOption('local') ?
                        $this->getLocalDir($this->option('local')) : $this->getLocalDir('/');

        $storageDir = $this->input->hasOption('storage') ?
                        $this->getStorageDir($this->option('storage')) : $this->getStorageDir('/');

        try {
            $this->scanDir($localDir, $storageDir, $localDir, $filter, $storageService);
            echo "upload files of public to storage successfully\n";
        } catch (\Exception $e) {
            echo "upload failed: " . $e->getTraceAsString() . "\n";
        }
    }

    private function scanDir($localRoot, $storageRoot, $localDir, $filter, StorageService $storageService)
    {
        if (!is_dir($localDir)) {
            return;
        }

        foreach(glob($localDir . '/*') as $object) {
            if (!$this->checkObjectSupported($object, $filter)) {
                // echo 'not supported: ' . $object . "\n";
                continue;
            }

            if(is_dir($object)) {
                $this->scanDir($localRoot, $storageRoot, $object, $filter, $storageService);
            } else {
                try {
                    $storageService->store($object,
                                           ['id' => $this->getStorageObjectName($localRoot, $storageRoot, $object)]);
                    // echo 'uploaded: ' . $object . "\n";
                } catch (\Exception $e) {
                    // echo 'not supported(exception): ' . $object . "\n";
                }
            }
        }
    }

    private function checkObjectSupported($object, $filter = [])
    {
        if (empty($filter)) {
            return true;
        }

        foreach ($filter as $f) {
            if (is_dir($object)) {
                if (starts_with($f, $object)) {
                    return true;
                }
            }

            if (starts_with($object, $f)) {
                return true;
            }
        }

        return false;
    }

    private function getLocalDir($dir)
    {
        return public_path(trim($dir, '/'));
    }

    private function getStorageDir($dir)
    {
        $dir = trim($dir, '/');
        if (empty($dir)) {
            return 'public/' . date('Y-m-d') . '/';
        }
        return 'public/' . $dir . '/';
    }

    private function getStorageObjectName($localRoot, $storageRoot, $localObjectName)
    {
        return $storageRoot . trim(str_replace($localRoot, '', $localObjectName), '/');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['local',   'l', InputOption::VALUE_OPTIONAL, 'local dir in public', '/'],
            ['storage', 's', InputOption::VALUE_OPTIONAL, 'storage dir in  public)', date('Ymd')],
            // [] means: all objects of local dir is effective
            ['filter',  'f', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'filter file(s) or dir(s)', []],
        ];
    }
}