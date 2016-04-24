<?php
namespace Jihe\Utils;

final class StreamUtil
{
    /**
     * get content of given resource
     * 
     * @param resource $resource  resource to get content from
     * @param boolean  $rewind    when true, to rewind the resource (by default it's false)
     * 
     * @return string
     */
    public static function getAsString($resource, $rewind = false)
    {
        if ($rewind) {
            rewind($resource);
        }
        
        return stream_get_contents($resource);
    }
    
    /**
     * save resource to file
     * 
     * @param string $file         file to save resource
     * @param resource $resource   source resource to be saved
     * @param boolean $close       when true, close source resource after 
     *                             save is done
     */
    public static function save($file, $resource, $close = true) 
    {
        $dest = fopen($file, 'w+');
        stream_copy_to_stream($resource, $dest);
        fclose($dest);
        
        if ($close) {
            fclose($resource);
        }
    }
}