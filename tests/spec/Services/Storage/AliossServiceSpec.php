<?php
namespace spec\Jihe\Services\Storage;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Aliyun\OSS\OSSClient;
use Aliyun\OSS\Models\PutObjectResult;
use Aliyun\OSS\Models\OSSObject;
use Aliyun\OSS\Exceptions\OSSException;
use Aliyun\OSS\Models\OSSOptions;

class AliossServiceSpec extends ObjectBehavior
{
    function let(OSSClient $client)
    {
        $this->beConstructedWith($client, [
            'bucket' => 'jhla-test',
        ]);
    }
    
    //========================================
    //           Store File
    //========================================
    function it_has_key_returned_for_a_successful_storage_without_return_all_requested(OSSClient $client) 
    {
        $client->putObject(Argument::that(function (array $options) {
            return ($options['Bucket'] === 'jhla-test' )  && 
                   // extension should be  '.jpg'
                   ends_with($options['Key'], '.jpg') &&
                   // content should be resource
                   is_resource($options['Content'])   && 
                   // content length should be positive integer
                   (is_integer($options['ContentLength']) && $options['ContentLength'] > 0 &&
                   // content should be image/jpeg
                   $options['ContentType'] === 'image/jpeg'); 
        }))->shouldBeCalled();
        
        $this->store(__DIR__ . '/test-data/panda.jpg')
             ->shouldBeString();
    }
    
    function it_has_fileinfo_returned_for_a_successful_store_if_return_all_requested(OSSClient $client)
    {
        $expectedPutObjectResult = new PutObjectResult();
        $expectedPutObjectResult->setETag('my-etag');
        $client->putObject(Argument::cetera())->willReturn($expectedPutObjectResult);

        $result = $this->store(__DIR__ . '/test-data/panda.jpg', ['id_only' => false]);
        // result is an array with 'id' and 'etag' as its key
        $result->shouldBeArray();
        $result->shouldHaveKeyWithValue('etag', 'my-etag');
        $result->shouldHaveKey('id');
    }

    // this behavior is not publicly defined in the interface
    function it_has_specified_key_returned_if_key_is_explictly_assigned(OSSClient $client)
    {
        $client->putObject(Argument::cetera())->shouldBeCalled();

        $this->store(__DIR__ . '/test-data/panda.jpg', ['id' => 'my_specifid_key'])
             //->shouldMatch('/(\/my_specifid_key)/is');
             ->shouldBe('my_specifid_key');
    }

    function it_has_ext_in_key_returned_if_ext_is_specified(OSSClient $client)
    {
        $client->putObject(Argument::cetera())->shouldBeCalled();

        $this->store(__DIR__ . '/test-data/panda.jpg', ['ext' => 'png'])
             ->shouldEndWith('.png');
    }

    //========================================
    //            Deletion
    //========================================
    function it_should_succeed_for_deleting_an_exisiting_file(OSSClient $client)
    {
        $client->deleteObject([
            'Bucket' => 'jhla-test',
            'Key'    => 'existing-file-id',
        ])->shouldBeCalled();

        $this->shouldNotThrow()->duringRemove('existing-file-id');
    }

    function it_should_succeed_for_deleting_an_non_exisit_file(OSSClient $client)
    {
        $client->deleteObject(Argument::withEntry('Key', 'non-existing-file-id'))
               ->shouldBeCalled();

        $this->shouldNotThrow()->duringRemove('non-existing-file-id');
    }

    function it_should_fail_for_deleting_an_non_exisit_file_if_inspection_is_on(OSSClient $client)
    {
        $client->getObjectMetadata(Argument::withEntry('Key', 'non-existing-file-id'))
               ->shouldBeCalled();

        $this->shouldThrow()->duringRemove('non-existing-file-id', [ 'inspect' => true ]);
    }

    function it_should_succeed_for_deleting_an_exisiting_file_if_inspection_is_on(OSSClient $client)
    {
        $client->getObjectMetadata(Argument::withEntry('Key', 'existing-file'))
               ->will(function () {
                   return new OSSObject();
               });
        $client->deleteObject(Argument::withEntry('Key', 'existing-file'))->shouldBeCalled();

        $this->shouldNotThrow()->duringRemove('existing-file', [ 'inspect' => true ]);
    }

    /**
    //========================================
    //          get object url
    //========================================
    function it_should_have_url_for_existing_file_if_inspection_is_turned_off()
    {
        $this->getPortal('existing')
             ->shouldStartWith('http://');
    }

    function it_should_have_url_for_non_exist_file_if_inspection_is_turned_off()
    {
        $this->getPortal('non-exist')
             ->shouldStartWith('http://');
    }

    function it_should_succeed_for_existing_file_if_inspection_is_turned_on(OSSClient $client)
    {
        $client->getObjectMetadata(Argument::withEntry('Key', 'existing'))
               ->will(function () {
                    // we only need an non-null object, actually
                    $object = new OSSObject();
                    $object->setKey('existing');

                    return $object;
               });

     $this->getPortal('existing', [ 'inspect' => true ])  // true to turn inspection on
          ->shouldStartWith('http://');
    }

    function it_should_fail_for_non_exist_file_if_inspection_is_turned_on(OSSClient $client)
    {
        $client->getObject(Argument::withEntry('Key', 'non-exist'))
               ->willThrow(new OSSException('NoSuchKey', '', '', ''));

        $this->shouldThrow('\Exception')
             ->duringGetPortal('non-exist', [ 'inspect' => true ]);  // true to turn inspection on
    }
    */

    //========================================
    //                  get
    //========================================
    function it_should_succeed_for_get_if_only_content_is_turned_on(OSSClient $client)
    {
        $object = new \Aliyun\OSS\Models\OSSObject();
        $object->setKey('key');
        $object->setObjectContent('abcde');
        $object->addMetadata('Content-Length', 5);

        $client->getObject(Argument::withEntry(OSSOptions::KEY, 'key'))
               ->willReturn($object);

        $this->get('key', [
                            'content' => true,
                            'metadata' => false
                          ])
             ->shouldBe('abcde');
    }

    function it_should_succeed_for_get_if_only_metadata_is_turned_on(OSSClient $client)
    {
        $object = new \Aliyun\OSS\Models\OSSObject();
        $object->setKey('key');
        $object->setObjectContent('abcde');
        $object->addMetadata('Content-Length', 5);

        $client->getObject(Argument::withEntry(OSSOptions::KEY, 'key'))
               ->willReturn($object);

        $this->get('key', [
                            'content'  => false,
                            'metadata' => true
                          ])
             ->shouldBe(['Content-Length' => 5]);
    }

    function it_should_throw_exception_for_get_if_both_content_and_metadata_is_turned_off(OSSClient $client)
    {
        $object = new \Aliyun\OSS\Models\OSSObject();
        $object->setKey('key');
        $object->setObjectContent('abcde');
        $object->addMetadata('Content-Length', 5);

        $client->getObject(Argument::withEntry(OSSOptions::KEY, 'key'))
               ->willReturn($object);

        $this->shouldThrow(new \InvalidArgumentException('content and metadata cannot be false meanwhile'))
             ->duringGet('key', [
                            'content'  => false,
                            'metadata' => false
                         ]);
    }

    function it_should_succeed_for_get_if_both_content_and_metadata_is_turned_on(OSSClient $client)
    {
        $object = new \Aliyun\OSS\Models\OSSObject();
        $object->setKey('key');
        $object->setObjectContent('abcde');
        $object->addMetadata('Content-Length', 5);

        $client->getObject(Argument::withEntry(OSSOptions::KEY, 'key'))
               ->willReturn($object);

        $this->get('key', [
                            'content'  => true,
                            'metadata' => true
                          ])
             ->shouldBe([
                         'content'  => 'abcde',
                         'metadata' => [
                                         'Content-Length' => 5,
                                       ],
                       ]);
    }

    //========================================
    //              listObjects
    //========================================
    function it_should_succeed_for_list_objects_return_only_object_summaries(OSSClient $client)
    {
        $objectList = new \Aliyun\OSS\Models\ObjectListing();
        $objectList->setObjectSummarys([
            $this->makeObjectSummary('key1', 120, new \DateTime('1990-12-12 00:00:00')),
            $this->makeObjectSummary('key2', 120, new \DateTime('1990-12-12 00:00:00')),
            $this->makeObjectSummary('key3', 120, new \DateTime('1990-12-12 00:00:00')),
        ]);

        $client->listObjects(Argument::withEntry(OSSOptions::DELIMITER, '/'))
               ->willReturn($objectList);

        $result = $this->listObjects();
        $result->shouldBeArray();
        $result->shouldHaveKeyWithValue('next_marker', null);
        $result->shouldHaveKeyWithValue('objects', [
            $this->makeObjectAttributes('key1', 'key1', false, 120, '1990-12-12 00:00:00'),
            $this->makeObjectAttributes('key2', 'key2', false, 120, '1990-12-12 00:00:00'),
            $this->makeObjectAttributes('key3', 'key3', false, 120, '1990-12-12 00:00:00'),
        ]);
    }

    private function makeObjectSummary($key, $size, $lastModified)
    {
        $objectSummary = new \Aliyun\OSS\Models\OSSObjectSummary();
        $objectSummary->setKey($key);
        $objectSummary->setSize($size);
        $objectSummary->setLastModified($lastModified);

        return $objectSummary;
    }

    private function makeObjectAttributes($name, $originalName, $folder, $size = null, $lastModified = null)
    {
        $returnObject = [
            'name'         => $name,
            'original_name' => $originalName,
            'folder'       => $folder,
        ];

        if (isset($size)) {
            $returnObject['size'] = $size;
        }

        if (isset($lastModified)) {
            $returnObject['last_modified'] = $lastModified;
        }

        return $returnObject;
    }

    function it_should_succeed_for_list_objects_return_only_common_prefixes(OSSClient $client)
    {
        $objectList = new \Aliyun\OSS\Models\ObjectListing();
        $objectList->setCommonPrefixes([
            'key1/',
            'key2/',
            'key3/',
        ]);

        $client->listObjects(Argument::withEntry(OSSOptions::DELIMITER, '/'))
               ->willReturn($objectList);

        $result = $this->listObjects();
        $result->shouldBeArray();
        $result->shouldHaveKeyWithValue('next_marker', null);
        $result->shouldHaveKeyWithValue('objects', [
            $this->makeObjectAttributes('key1', 'key1/', true),
            $this->makeObjectAttributes('key2', 'key2/', true),
            $this->makeObjectAttributes('key3', 'key3/', true),
        ]);
    }

    function it_should_succeed_for_list_objects_return_both_object_summaries_and_common_prefixes(OSSClient $client)
    {
        $objectList = new \Aliyun\OSS\Models\ObjectListing();
        $objectList->setObjectSummarys([
            $this->makeObjectSummary('key1', 120, new \DateTime('1990-12-12 00:00:00')),
            $this->makeObjectSummary('key2', 120, new \DateTime('1990-12-12 00:00:00')),
            $this->makeObjectSummary('key3', 120, new \DateTime('1990-12-12 00:00:00')),
        ]);
        $objectList->setCommonPrefixes([
            'key1/',
            'key2/',
            'key3/',
        ]);

        $client->listObjects(Argument::withEntry(OSSOptions::DELIMITER, '/'))
               ->willReturn($objectList);

        $result = $this->listObjects();
        $result->shouldBeArray();
        $result->shouldHaveKeyWithValue('next_marker', null);
        $result->shouldHaveKeyWithValue('objects', [
            $this->makeObjectAttributes('key1', 'key1', false, 120, '1990-12-12 00:00:00'),
            $this->makeObjectAttributes('key2', 'key2', false, 120, '1990-12-12 00:00:00'),
            $this->makeObjectAttributes('key3', 'key3', false, 120, '1990-12-12 00:00:00'),
            $this->makeObjectAttributes('key1', 'key1/', true),
            $this->makeObjectAttributes('key2', 'key2/', true),
            $this->makeObjectAttributes('key3', 'key3/', true),
        ]);
    }

    function it_should_succeed_for_list_objects_use_prefix(OSSClient $client)
    {
        $objectList = new \Aliyun\OSS\Models\ObjectListing();
        $objectList->setObjectSummarys([
            $this->makeObjectSummary('key/key1', 120, new \DateTime('1990-12-12 00:00:00')),
        ]);
        $objectList->setCommonPrefixes([
            'key/key1/',
        ]);

        $client->listObjects(Argument::withEntry(OSSOptions::DELIMITER, '/'))
               ->willReturn($objectList);

        $result = $this->listObjects('key/');
        $result->shouldBeArray();
        $result->shouldHaveKeyWithValue('next_marker', null);
        $result->shouldHaveKeyWithValue('objects', [
            $this->makeObjectAttributes('key1', 'key/key1', false, 120, '1990-12-12 00:00:00'),
            $this->makeObjectAttributes('key1', 'key/key1/', true),
        ]);
    }

    function it_should_succeed_for_list_objects_get_next_marker(OSSClient $client)
    {
        $objectList = new \Aliyun\OSS\Models\ObjectListing();
        $objectList->setObjectSummarys([
            //$this->makeObjectSummary('key1', 120, new \DateTime('1990-12-12 00:00:00')),
            $this->makeObjectSummary('key2', 120, new \DateTime('1990-12-12 00:00:00')),
            $this->makeObjectSummary('key3', 120, new \DateTime('1990-12-12 00:00:00')),
            //$this->makeObjectSummary('key4', 120, new \DateTime('1990-12-12 00:00:00')),
        ]);
        $objectList->setCommonPrefixes([
            'key2/',
        ]);
        $objectList->setNextMarker('key3');

        $client->listObjects(Argument::withEntry(OSSOptions::DELIMITER, '/'))
               ->willReturn($objectList);

        $result = $this->listObjects(null, ['marker' => 'key1', 'max_keys' => 3]);
        $result->shouldBeArray();
        $result->shouldHaveKeyWithValue('next_marker', 'key3');
        $result->shouldHaveKeyWithValue('objects', [
            $this->makeObjectAttributes('key2', 'key2', false, 120, '1990-12-12 00:00:00'),
            $this->makeObjectAttributes('key3', 'key3', false, 120, '1990-12-12 00:00:00'),
            $this->makeObjectAttributes('key2', 'key2/', true),
        ]);
    }

    //========================================
    //          Store File By Copy
    //========================================
    function it_has_key_returned_for_a_successful_storage_by_copy(OSSClient $client)
    {
        $client->copyObject(Argument::cetera())->shouldBeCalled()->willReturn(null);

        $this->copy('tmp/upload.jpg')->shouldNotMatch('/(\/tmp\/)/is');
    }
}
