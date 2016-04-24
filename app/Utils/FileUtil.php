<?php

namespace Jihe\Utils;

final class FileUtil {

    public static function makeTempFileName($format) {
        $path = storage_path('temp') . DIRECTORY_SEPARATOR . uniqid() . ($format ? ('.' . $format) : '.png');
        return $path;
    }

    public static function outputZip($filename) {
        if (!file_exists($filename)) {
            throw new \Exception("File not found [ {$filename} ]");
        }
        
        header("Cache-Control: max-age=0");
        header("Content-Description: File Transfer");
        header('Content-disposition: attachment; filename=' . basename($filename));
        header("Content-Type: application/zip");
        header("Content-Transfer-Encoding: binary");
        header('Content-Length: ' . filesize($filename));
        @readfile($filename);
        exit(0);
    }

}
