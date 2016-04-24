<?php
namespace spec\Jihe\Services\Photo;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PhotoServiceSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(\Jihe\Services\Photo\PhotoService::class);
    }

    //=====================================
    //               Crop
    //=====================================
    function it_crops()
    {
        $this->crop(
                    __DIR__ . '/test-data/480_600.jpg',
                    [
                        'format' => 'jpeg',
                        'x'      => 0,
                        'y'      => 0,
                        'width'  => 50,
                        'height' => 50,
                    ])
            ->shouldBePngString();
    }

    function it_crops_frame_and_save_it()
    {
        $saveAs = $this->createTempFilename();
        $this->crop(
                        __DIR__ . '/test-data/480_600.jpg',
                        [
                            'format' => 'jpeg',
                            'x'      => 0,
                            'y'      => 0,
                            'width'  => 50,
                            'height' => 50,
                            'save_as' => $saveAs,
                            'save_as_format' => 'png',
                        ])
            ->shouldEqual(true);

        \PHPUnit_Framework_Assert::assertTrue(file_exists($saveAs), 'photo with frame should be saved');
        \PHPUnit_Framework_Assert::assertTrue($this->isPngString(file_get_contents($saveAs)),
                                              'photo after cropped should be in PNG format');

        unlink($saveAs); // clean up
    }

    function it_crops_frame_save_it_deduce_the_format()
    {
        $saveAs = $this->createTempFilename() . '.png';;
        $this->crop(
            __DIR__ . '/test-data/480_600.jpg',
            [
                'format' => 'jpeg',
                'x'      => 0,
                'y'      => 0,
                'width'  => 50,
                'height' => 50,
                'save_as' => $saveAs,
            ])
            ->shouldEqual(true);

        \PHPUnit_Framework_Assert::assertTrue(file_exists($saveAs), 'photo with frame should be saved');
        \PHPUnit_Framework_Assert::assertTrue($this->isPngString(file_get_contents($saveAs)),
                                              'photo after cropped should be in PNG format');

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

    private function createTempFilename($prefix = 'photo_with_frame')
    {
        return sys_get_temp_dir() . '/' .  uniqid($prefix);
    }
}
