<?php
namespace spec\Jihe\Services;

use Jihe\Contracts\Services\Storage\StorageService;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class StorageServiceSpec extends ObjectBehavior
{
    /**
     * @var array $imageSize that will be returned by getimagesize()
     */
    public static $imageSize;

    function let(StorageService $service)
    {
        $this->beConstructedWith($service, [
            'base_url'       => 'http://file.domain.com',
            'base_image_url' => 'http://image.domain.com'
        ]);
    }

    //========================================
    //               Is Tmp
    //========================================
    function it_should_returned_true_if_use_tmp_file_url()
    {
        $this->isTmp('http://file.domain.com/tmp/1029923')
             ->shouldBe(true);
    }

    function it_should_returned_true_if_use_tmp_image_url()
    {
        $this->isTmp('http://image.domain.com/tmp/1029923')
            ->shouldBe(true);
    }

    function it_should_returned_false_if_use_normal_file_url()
    {
        $this->isTmp('http://file.domain.com/1029923')
            ->shouldBe(false);
    }

    function it_should_returned_false_if_use_invalid_url()
    {
        $this->isTmp('http://none.domain.com/1029923')
            ->shouldBe(false);
    }

    //========================================
    //               Is File
    //========================================
    function it_should_returned_true_if_use_file_url()
    {
        $this->isFile('http://file.domain.com/1029923')
             ->shouldBe(true);
    }

    function it_should_returned_false_if_use_image_url()
    {
        $this->isFile('http://image.domain.com/1029923')
            ->shouldBe(false);
    }

    function it_should_returned_false_if_use_invalid_file_url()
    {
        $this->isFile('http://none.domain.com/1029923')
            ->shouldBe(false);
    }

    //========================================
    //               Is Image
    //========================================
    function it_should_returned_true_if_it_is_image_url()
    {
        $this->isImage('http://image.domain.com/1029923')
            ->shouldBe(true);
    }

    function it_should_returned_false_if_it_is_file_url()
    {
        $this->isImage('http://file.domain.com/1029923')
            ->shouldBe(false);
    }

    function it_should_returned_false_if_it_is_invalid_file_url()
    {
        $this->isImage('http://none.domain.com/1029923')
            ->shouldBe(false);
    }

    //========================================
    //           Store Tmp File
    //========================================
    function it_has_tmp_file_prefix_in_key_returned_successful(StorageService $service)
    {
        $service->store(__DIR__ . '/test-data/panda.jpg', ['prefix' => 'tmp/'])
                ->shouldBeCalled()->willReturn('tmp/20151103');

        $this->storeAsTmpFile(__DIR__ . '/test-data/panda.jpg')
             //->shouldMatch('/(\/tmp\/)/is');
             ->shouldBe('http://file.domain.com/tmp/20151103');
    }

    //========================================
    //           Store Tmp Image
    //========================================
    function it_has_tmp_image_prefix_in_key_returned_successful(StorageService $service)
    {
        $service->store(__DIR__ . '/test-data/panda.jpg', ['prefix' => 'tmp/', 'ext' => 'jpg'])
            ->shouldBeCalled()->willReturn('tmp/20151103');

        $this->storeAsTmpImage(__DIR__ . '/test-data/panda.jpg')
            ->shouldBe('http://image.domain.com/tmp/20151103');
    }

    //========================================
    //           Store As File
    //========================================
    function it_has_file_prefix_in_key_returned_successful(StorageService $service)
    {
        $service->store(__DIR__ . '/test-data/panda.jpg', [])
            ->shouldBeCalled()->willReturn('20151103');

        $this->storeAsFile(__DIR__ . '/test-data/panda.jpg')
            ->shouldBe('http://file.domain.com/20151103');
    }

    function it_will_throw_exception_if_file_is_not_exists()
    {
        $this->shouldThrow(new \Exception('file is invalid'))
            ->duringStoreAsFile(__DIR__ . '/test-data/panda_none.jpg');
    }

    function it_has_file_prefix_in_key_returned_successful_if_source_is_tmp_file_url(StorageService $service)
    {
        $service->copy('tmp/20190987', [])
            ->shouldBeCalled()->willReturn('20151103');
        $service->store(Argument::cetera())->shouldNotBeCalled();

        $this->storeAsFile('http://file.domain.com/tmp/20190987')
            ->shouldBe('http://file.domain.com/20151103');
    }

    function it_has_file_prefix_in_key_returned_successful_if_source_is_tmp_image_url(StorageService $service)
    {
        $service->copy('tmp/20190987', [])
            ->shouldBeCalled()->willReturn('20151103');
        $service->store(Argument::cetera())->shouldNotBeCalled();

        $this->storeAsFile('http://image.domain.com/tmp/20190987')
            ->shouldBe('http://file.domain.com/20151103');
    }

    function it_will_throw_exception_if_tmp_url_is_not_invalid()
    {
        $this->shouldThrow(new \Exception('file is invalid'))
            ->duringStoreAsFile('http://none.domain.com/tmp/2016887');
    }

    function it_will_throw_exception_if_url_is_not_tmp()
    {
        $this->shouldThrow(new \Exception('file is invalid'))
            ->duringStoreAsFile('http://file.domain.com/2016887');
    }

    //========================================
    //           Store As Image
    //========================================
    function it_has_image_prefix_in_key_returned_successful(StorageService $service)
    {
        $service->store(__DIR__ . '/test-data/panda.jpg', ['ext' => 'jpg'])
            ->shouldBeCalled()->willReturn('20151103');

        $this->storeAsImage(__DIR__ . '/test-data/panda.jpg')
            ->shouldBe('http://image.domain.com/20151103');
    }

    function it_has_image_prefix_in_key_returned_successful_if_source_is_tmp_file_url(StorageService $service)
    {
        self::$imageSize = [194, 220, 2, 'width="194" height="220"', 'mime' => 'image/jpeg'];

        $service->copy('tmp/20190987', ['ext' => 'jpg'])
            ->shouldBeCalled()->willReturn('20151103');
        $service->store(Argument::cetera())->shouldNotBeCalled();

        $this->storeAsImage('http://file.domain.com/tmp/20190987')
            ->shouldBe('http://image.domain.com/20151103');

        self::$imageSize = null;
    }

    //========================================
    //                 Get
    //========================================
    function it_will_throw_exception_if_url_is_invalid_and_null_for_nonexistence_setted_false()
    {
        $this->shouldThrow(new \Exception('url is invalid'))
            ->duringGet('http://none.domain/com/09878', ['null_for_nonexistence' => false]);
    }

    function it_will_get_null_is_url_is_invalid_and_null_for_nonexistence_setted_true()
    {
        $this->get('http://none.domain/com/09878', ['null_for_nonexistence' => true])
             ->shouldBe(null);
    }

    //========================================
    //               Remove
    //========================================
    function it_will_get_null_is_url_is_invalid(StorageService $service)
    {
        $service->remove(Argument::cetera())->shouldNotBeCalled();

        $this->remove('http://none.domain/com/09878')
            ->shouldBe(null);
    }

    function it_will_get_null_returned_successful(StorageService $service)
    {
        $service->remove('09878', [])->shouldBeCalled()->willReturn(null);

        $this->remove('http://file.domain.com/09878')
            ->shouldBe(null);
    }
}

namespace Jihe\Services;

/**
 * Override getimagesize() in current namespace for testing
 *
 * @return array
 */
function getimagesize($filename, array &$imageinfo = null)
{
    return \spec\Jihe\Services\StorageServiceSpec::$imageSize ?: \getimagesize($filename, $imageinfo);
}
