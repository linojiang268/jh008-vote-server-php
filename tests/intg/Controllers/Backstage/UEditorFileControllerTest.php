<?php
namespace intg\Jihe\Controllers\Backstage;

use Mockery;
use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UEditorFileControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;

    //=========================================
    //              uploadImage
    //=========================================
    public function testSuccessfulGetConfig()
    {
        $user = factory(\Jihe\Models\User::class)->create();
        $this->startSession();

        $this->actingAs($user)->call('GET', '/community/ueditor', [
            'action' => 'config'
        ]);
        $this->seeJsonContains([ 'imageActionName' => 'uploadimage' ]);
    }
    
    //=========================================
    //              uploadImage
    //=========================================
    public function testSuccessfulUploadImage()
    {
        $user = factory(\Jihe\Models\User::class)->create();
        $this->mockStorageService();
        $this->mockImageForUploading(__DIR__ . '/test-data/panda.jpg');
        $this->startSession();
    
        $this->actingAs($user)->call('POST', '/community/ueditor', [
            'action' => 'uploadimage'
        ], [], [
            'upfile' => $this->makeUploadFile($this->getMockedImagePath('panda.jpg'))
        ]);
        $this->seeJsonContains([ 'state' => 'SUCCESS' ]);

        $result = json_decode($this->response->getContent());
        self::assertObjectHasAttribute('url', $result);
        self::assertObjectHasAttribute('title', $result);
        self::assertObjectHasAttribute('original', $result);
        self::assertObjectHasAttribute('type', $result);
        self::assertObjectHasAttribute('size', $result);
    }

    /**
     * mock image file for uploading
     * @param string $path    file path to existing image file
     * @param string $name    name in the mocked file system (virtual file system, exactly)
     */
    private function mockImageForUploading($path, $name = null)
    {
        // populate name if not given
        !$name && $name = pathinfo($path, PATHINFO_BASENAME);

        // put the file to mock root
        $this->mockFiles('imageupload', [
            $name => file_get_contents($path),
        ]);
    }

    /**
     * get mocked image file's full path (including scheme and root directory)
     *
     * @param $path      path to mocked image file
     * @return string    full path of the mocked image file
     */
    private function getMockedImagePath($path)
    {
        return $this->getMockedFilePath(sprintf('imageupload/%s', ltrim($path, '/')));
    }

    private function mockStorageService($return = 'http://domain/tmp/key.png')
    {
        $storageService = \Mockery::mock(\Jihe\Services\StorageService::class);
        $storageService->shouldReceive('isTmp')->withAnyArgs()->andReturn(true);
        $storageService->shouldReceive('storeAsFile')->withAnyArgs()->andReturn($return);
        $storageService->shouldReceive('storeAsImage')->withAnyArgs()->andReturn($return);
        $storageService->shouldReceive('remove')->withAnyArgs()->andReturn(false);
        $storageService->shouldReceive('storeAsTmpImage')->withAnyArgs()->andReturn($return);
        $this->app [\Jihe\Services\StorageService::class] = $storageService;

        return $storageService;
    }
}
