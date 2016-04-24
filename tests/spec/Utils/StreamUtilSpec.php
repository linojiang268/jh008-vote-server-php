<?php
namespace spec\Jihe\Utils;

use PhpSpec\ObjectBehavior;
use \PHPUnit_Framework_Assert as Assert;
use Jihe\Utils\StreamUtil;

class StreamUtilSpec extends ObjectBehavior
{
    function it_can_get_stream_content()
    {
        $text = 'Laravel is a framework for web artisan';
        $handle = fopen('php://memory', 'w');
        fwrite($handle, $text);
        
        // rewind the resource and read its content
        Assert::assertEquals($text, StreamUtil::getAsString($handle, true));
        
        fclose($handle);
    }
    
    function it_can_save_resource()
    {
        $text = 'Laravel is a framework for web artisan';
        $handle = fopen('php://memory', 'w');
        fwrite($handle, $text);
        rewind($handle);
    
        $file = sys_get_temp_dir() . '/' . uniqid('stream_util');
        StreamUtil::save($file, $handle);
        
        // cross check - read the content of the file out
        Assert::assertEquals($text, file_get_contents($file));
        unlink($file);
    }
    
}
