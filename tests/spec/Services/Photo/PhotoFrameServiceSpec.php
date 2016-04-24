<?php
namespace spec\Jihe\Services\Photo;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PhotoFrameServiceSpec extends ObjectBehavior
{
//    function let()
//    {
//        $this->beConstructedWith(__DIR__ . '/../../../../public/static/fonts/Fangsong.ttf');
//    }
//
//    //=====================================
//    //          Generate
//    //=====================================
//    function it_generates_frame()
//    {
//        $this->generate(
//                        __DIR__ . '/test-data/480_600.jpg',
//                        '约跑族 | 月亮村夜光跑',
//                        ['photo_format' => 'jpeg'])
//            ->shouldBePngString();
//    }
//
//    function it_generates_frame_scale_photo()
//    {
//        $this->generate(
//                        __DIR__ . '/test-data/640_960.jpg',
//                        '约跑族 | 月亮村夜光跑',
//                        ['photo_format' => 'jpeg'])
//            ->shouldBePngString();
//    }
//
//    function it_generates_frame_special_padding()
//    {
//        $this->generate(
//                        __DIR__ . '/test-data/640_960.jpg',
//                        '约跑族 | 月亮村夜光跑',
//                        [
//                            'photo_format' => 'jpeg',
//                            'padding_left' => 15,
//                            'padding_right' => 15,
//                        ])
//            ->shouldBePngString();
//    }
//
//    function it_generates_frame_and_save_it()
//    {
//        $saveAs = $this->createTempFilename();
//        $this->generate(
//                        __DIR__ . '/test-data/480_600.jpg',
//                        '约跑族 | 月亮村夜光跑',
//                        [
//                            'photo_format' => 'jpeg',
//                            'save_as' => $saveAs,
//                            'save_as_format' => 'png',
//                        ])
//            ->shouldEqual(true);
//
//        \PHPUnit_Framework_Assert::assertTrue(file_exists($saveAs), 'photo with frame should be saved');
//        \PHPUnit_Framework_Assert::assertTrue($this->isPngString(file_get_contents($saveAs)),
//                                              'photo with frame should be in PNG format');
//
//        unlink($saveAs); // clean up
//    }
//
//    function it_generates_frame_save_it_deduce_the_format()
//    {
//        $saveAs = $this->createTempFilename() . '.png';;
//        $this->generate(
//            __DIR__ . '/test-data/480_600.jpg',
//            '约跑族 | 月亮村夜光跑',
//            [
//                'photo_format' => 'jpeg',
//                'save_as' => $saveAs,
//            ])
//            ->shouldEqual(true);
//
//        \PHPUnit_Framework_Assert::assertTrue(file_exists($saveAs), 'photo with frame should be saved');
//        \PHPUnit_Framework_Assert::assertTrue($this->isPngString(file_get_contents($saveAs)),
//                                              'photo with frame should be in PNG format');
//
//        unlink($saveAs); // clean up
//    }
//
//    public function getMatchers()
//    {
//        return [
//            'bePngString' => function ($image) {
//                return $this->isPngString($image);
//            },
//        ];
//    }
//
//    private function isPngString($image)
//    {
//        if (!is_string($image)) {
//            return false;
//        }
//
//        // check the string by inspecting its first 4 letters
//        return (bin2hex($image[0]) == '89' &&
//            $image[1] == 'P' && $image[2] == 'N' && $image[3] == 'G');
//    }
//
//    private function createTempFilename($prefix = 'photo_with_frame')
//    {
//        return sys_get_temp_dir() . '/' .  uniqid($prefix);
//    }
}
