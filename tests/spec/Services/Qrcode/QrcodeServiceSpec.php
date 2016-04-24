<?php
namespace spec\Jihe\Services\Qrcode;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class QrcodeServiceSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(\Jihe\Services\Qrcode\QrcodeService::class);
    }
    
    //=====================================
    //          Generate
    //=====================================
    function it_generates_simple_qrcode()
    {
        $this->generate('Go for it, zero2all', [
                'size'  => 300,
                'padding' => 10,
        ])->shouldBePngString();
    }
    
    function it_generates_qrcode_with_logo()
    {
        $this->generate('Go for it, zero2all', [
            'size'  => 300,
            'logo'  => __DIR__ . '/test-data/twitter.png',
            'padding' => 10,
        ])->shouldBePngString();
    }
    
    function it_generates_qrcode_and_save_it()
    {
        $saveAs = $this->createTempFilename();
        $this->generate('Go for it, zero2all', [
            'size'  => 300,
            'logo'  => __DIR__ . '/test-data/twitter.png',
            'padding' => 10,
            'save_as' => $saveAs,
            'save_as_format' => 'png', // explicitly give the save as format
        ])->shouldEqual(true);
        
        \PHPUnit_Framework_Assert::assertTrue(file_exists($saveAs), 'qrcode should be saved');
        
        // check qrcode size 
        list($width, $height) = getimagesize($saveAs);
        \PHPUnit_Framework_Assert::assertEquals(300, $width,  'width of the qrcode should be 300');
        \PHPUnit_Framework_Assert::assertEquals(300, $height, 'height of the qrcode should be 300');
        \PHPUnit_Framework_Assert::assertTrue($this->isPngString(file_get_contents($saveAs)), 
                                              'qrcode should be in PNG format');
        
        unlink($saveAs); // clean up
    }
    
    function it_generates_qrcode_and_save_it_deduce_the_format()
    {
        $saveAs = $this->createTempFilename(). '.png'; // append the format so that it can be deduced
        $this->generate('Go for it, zero2all', [
            'size'  => 400,
            'logo'  => __DIR__ . '/test-data/twitter.png',
            'padding' => 10,
            'save_as' => $saveAs,
        ])->shouldEqual(true);
    
        \PHPUnit_Framework_Assert::assertTrue(file_exists($saveAs), 'qrcode should be saved');
        \PHPUnit_Framework_Assert::assertTrue($this->isPngString(file_get_contents($saveAs)),
                                              'qrcode should be in PNG format');
        
        unlink($saveAs); // clean up
    }
    
    function it_rejects_qrcode_generation_if_size_is_not_proper()
    {
        // the logo file (__DIR__ . '/test-data/twitter.png') is 128 by 128 in size
        // and padding is set to be 10. And hence, the size of the generated qrcode
        // should be at least 148 (128 + 2 * 10) in width and height.
        $this->shouldThrow(\Exception::class)
             ->duringGenerate('Go for it, zero2all', [
                                'size'  => 140,  // 140 is not wide/high enough
                                'logo'  => __DIR__ . '/test-data/twitter.png',
                                'padding' => 10,
                            ]);
    }
    
    function it_generates_qrcode_and_scale_the_logo()
    {
        $saveAs = $this->createTempFilename() . '.png'; 
        $this->generate('Go for it, zero2all', [
            'size'  => 400,
            'logo'  => __DIR__ . '/test-data/twitter.png',
            'logo_scale_width' => 32,    // scale it to 32 x 32
            'padding' => 10,
            'save_as' => $saveAs,
            
        ])->shouldEqual(true);
        \PHPUnit_Framework_Assert::assertTrue(file_exists($saveAs), 'qrcode should be saved');
        \PHPUnit_Framework_Assert::assertTrue($this->isPngString(file_get_contents($saveAs)),
                                              'qrcode should be in PNG format');
    
        unlink($saveAs); // clean up
    }
    
    public function getMatchers()
    {
        return [
            'bePngString' => function ($image) {
                return $this->isPngString($image);
            },
        ];
    }
    
    private function isPngString($image)
    {
        if (!is_string($image)) {
            return false;
        }
         
        // check the string by inspecting its first 4 letters
        return (bin2hex($image[0]) == '89' &&
               $image[1] == 'P' && $image[2] == 'N' && $image[3] == 'G');
    }
    
    private function createTempFilename($prefix = 'qrcode')
    {
        return sys_get_temp_dir() . '/' .  uniqid($prefix);
    }
}
