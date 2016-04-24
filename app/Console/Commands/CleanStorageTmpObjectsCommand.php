<?php
namespace Jihe\Console\Commands;

use Illuminate\Console\Command;
use Jihe\Contracts\Services\Storage\StorageService;
use Symfony\Component\Console\Input\InputOption;

class CleanStorageTmpObjectsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'storage:tmp:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean tmp files in storage.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(StorageService $storageService)
    {
        $expired = $this->input->hasOption('expired') ? $this->option('expired') : 0;

        try {
            $this->scanDir('tmp/', null, $this->convertExpired($expired), $storageService);
            echo "clean storage tmp objects successfully\n";
        } catch (\Exception $e) {
            echo "clean failed: " . $e->getTraceAsString() . "\n";
        }
    }

    private function scanDir($prefix, $marker, $expired, StorageService $storageService)
    {
        $rst = $storageService->listObjects($prefix, [
            'max_keys' => 1,
            'marker'   => $marker,
        ]);

        $nextMarker = $rst['next_marker'];
        $objects = $rst['objects'];

        if (!empty($objects)) {
            $object = $objects[0];
            $this->ensureObjectNeedToDeleted($object, $expired, $storageService);
        }

        if (is_null($nextMarker)) {
            return;
        }

        $this->scanDir($prefix, $nextMarker, $expired, $storageService);
    }

    private function ensureObjectNeedToDeleted($object, $expired, StorageService $storageService)
    {
        $time = $this->convertObjectNameToTime($object['name']);
        if ($time < 0) {
            // echo 'not supported: ' . $object['original_name'] . "\n";
            return;
        }

        if ($object['folder']) {
            // 00:00:00 over expired time line
            if ($this->isOutExpiredTimeLine(strtotime(date('Y-m-d', $time) . ' 00:00:00'), $expired)) {
                $this->scanDir($object['original_name'], null, $expired, $storageService);
                // remove folder is not supported
                // expected remove folder when 23:59:59 over expired time line
            }
        } else {
            if ($this->isOutExpiredTimeLine($time, $expired)) {
                $storageService->remove($object['original_name']);
                // echo 'removed: ' . $object['original_name'] . "\n";
            }
        }
    }

    private function convertObjectNameToTime($objectName)
    {
        $pointPos = strpos($objectName, '.');
        $len = $pointPos ? $pointPos : strlen($objectName);

        if (8 == $len) {
            return strtotime($objectName) ?: -1;
        } elseif (20 == $len) {
            return strtotime(substr($objectName, 0, 14)) ?: -1;
        }

        return -1;
    }

    private function convertExpired($expired)
    {
        if (empty($expired)) return 0;

        // %d days | $d hours | $d minutes | $d(means: sec) |
        $expired = trim($expired);

        if (ends_with($expired, ' sec')) {
            return intval(rtrim($expired, ' sec'));
        } elseif (ends_with($expired, ' minutes')) {
            return 60 * intval(rtrim($expired, ' minutes'));
        } elseif (ends_with($expired, ' hours')) {
            return 3600 * intval(rtrim($expired, ' hours'));
        } elseif (ends_with($expired, ' days')) {
            return 24 * 3600 * intval(rtrim($expired, ' days'));
        }

        return intval($expired);
    }

    private function isOutExpiredTimeLine($timeOfObject, $expired)
    {
        if ($expired < 0) {
            return false;
        }

        $sub = time() - $timeOfObject;
        return $sub > $expired;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['expired', 'e', InputOption::VALUE_OPTIONAL, 'expired time line.(%d days | $d hours | $d minutes | $d sec | $d(means sec) | 0(means all))',  0],
        ];
    }
}