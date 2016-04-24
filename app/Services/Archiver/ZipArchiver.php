<?php

namespace Jihe\Services\Archiver;

use Alchemy\Zippy\Zippy;

class ZipArchiver {

    public function compress($savePath, array $files) {
        if (file_exists($savePath)) {
            if (is_dir($savePath)) {
                throw new \Exception('Save path invalid(directory)');
            }
        }

        if (empty($files)) {
            throw new \Exception('No files have been inputted');
        }

        foreach ($files as $file) {
            if (!file_exists($file) && !starts_with($file, 'http')) {
                throw new \Exception('File not found! [' . $file . ']');
            }
        }

        $zippy = Zippy::load();
        $archive = $zippy->create($savePath, $files);

        return $archive != NULL;
    }

    public function extract($file, $extractPath) {
        $zippy = Zippy::load();
        $archive = $zippy->open($file);
        // iterates through members
        $fileList = [];
        foreach ($archive as $member) {
            $fileList[] = $member;
            //echo "archive contains $member \n";
        }
        // extract content to $extractPath
        $archive->extract($extractPath);
        
        return $fileList;
    }

}
