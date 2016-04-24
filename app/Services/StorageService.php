<?php 
namespace Jihe\Services;

use Aliyun\OSS\OSSClient;
use Aliyun\OSS\Models\OSSOptions;
use Jihe\Utils\StringUtil;
use Aliyun\OSS\Exceptions\OSSException;
use Jihe\Contracts\Services\Storage\StorageService as StorageServiceContract;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class StorageService
{
    /**
     * @var StorageServiceContract
     */
    private $storageService;

    /**
     * base url for accessing stored objects(typically, files)
     *
     * @var string
     */
    private $baseUrl;

    /**
     * base image url for accessing stored objects(typically, files)
     *
     * @var string
     */
    private $baseImageUrl;

    /**
     *
     * @param array $config        configurations.
     *                             - base_url         base url for accessing stored objects
     *                             - base_image_url   base image url for accessing stored objects
     */
    public function __construct(StorageServiceContract $storageService, array $config)
    {
        $this->storageService = $storageService;
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->baseImageUrl = rtrim($config['base_image_url'], '/');
    }

    /**
     * check whether the given identifier of url is tmp
     *
     * @param $url
     * @return bool
     */
    public function isTmp($url)
    {
        $urlInfo = $this->urlInfo($url);

        return $urlInfo && $urlInfo['tmp'];
    }

    /**
     * check whether the given identifier of url is normal file
     *
     * @param $url
     * @return bool
     */
    public function isFile($url)
    {
        $urlInfo = $this->urlInfo($url);

        return $urlInfo && !$urlInfo['image'];
    }

    /**
     * check whether the given identifier of url is image
     * (means that: could be hander use image tools of cdn)
     *
     * @param $url
     * @return bool
     */
    public function isImage($url)
    {
        $urlInfo = $this->urlInfo($url);

        return $urlInfo && $urlInfo['image'];
    }

    /**
     * store given file as temporary file
     *
     * @param string $file                   file path.   this file MUST exist
     * @param array  $options                options
     *                                       - id_only    when set to true(default), only identifier
     *                                                    of the file will be returned.
     *                                       - bucket     (optional)which bucket to store the file
     *                                       - ext        (Optional) extension of the file. it's possible
     *                                                    that $file does not have extension suffix.
     *                                       - mime       (optional) mime of the file.
     *
     * @return string|array     - if the returned value is a string, it's the identifier
     *                          of the file.
     *                          - if the returned value is an array, identifier of the
     *                          file should be included, which is keyed by 'id'.
     *
     * @throws \Exception       network/storage exception
     */
    public function storeAsTmpFile($file, array $options = [])
    {
        array_set($options, 'prefix', 'tmp/');

        return $this->parseResult(
            $this->store($file, $options),
            $this->baseUrl,
            $options);
    }

    /**
     * store given file as temporary image
     *
     * @param string $file                   file path.   this file MUST exist
     * @param array  $options                options
     *                                       - id_only    when set to true(default), only identifier
     *                                                    of the file will be returned.
     *                                       - bucket     (optional)which bucket to store the file
     *                                       - ext        (Optional) extension of the file. it's possible
     *                                                    that $file does not have extension suffix.
     *                                       - mime       (optional) mime of the file.
     *
     * @return string|array     - if the returned value is a string, it's the identifier
     *                          of the file.
     *                          - if the returned value is an array, identifier of the
     *                          file should be included, which is keyed by 'id'.
     *
     * @throws \Exception       network/storage exception
     */
    public function storeAsTmpImage($file, array $options = [])
    {
        if ($ext = $this->guessExt($file, true)) {
            array_set($options, 'ext', $ext);
        }

        array_set($options, 'prefix', 'tmp/');

        return $this->parseResult(
            $this->store($file, $options),
            $this->baseImageUrl,
            $options);
    }

    /**
     * store given file as normal file
     *
     * @param $file|string      file path:this file MUST exist; string:this url is tmp url
     * @param array $options    options
     *                          - id_only    when set to true(default), only identifier
     *                                       of the file will be returned.
     *                          - id         (optional) special identifier
     *                          - bucket     (optional) which bucket to store the file
     *                          - ext        (optional) extension of the file. it's possible
     *                                       that $file does not have extension suffix.
     *                          - mime       (optional) mime of the file.
     *
     * @return string|array     - if the returned value is a string, it's the identifier(url)
     *                          of the file.
     *                          - if the returned value is an array, identifier of the
     *                          file should be included, which is keyed by 'url id'.
     *
     * @throws \Exception       network/storage exception
     */
    public function storeAsFile($file, array $options = [])
    {
        if ($ext = $this->guessExt($file, false)) {
            array_set($options, 'ext', $ext);
        }

        if (is_string($file) && $this->isTmp($file)) {
            $result = $this->copy($file, $options);
        } else {
            $result = $this->store($file, $options);
        }

        return $this->parseResult(
            $result,
            $this->baseUrl,
            $options);
    }

    /**
     * store given file as image
     *
     * @param $file|string      file path:this file MUST exist; string:this url is tmp url
     * @param array $options    options
     *                          - id_only    when set to true(default), only identifier
     *                                       of the file will be returned.
     *                          - id         (optional) special identifier
     *                          - bucket     (optional) which bucket to store the file
     *                          - ext        (optional) extension of the file. it's possible
     *                                       that $file does not have extension suffix.
     *                          - mime       (optional) mime of the file.
     *
     * @return string|array     - if the returned value is a string, it's the identifier(url)
     *                          of the file.
     *                          - if the returned value is an array, identifier of the
     *                          file should be included, which is keyed by 'url id'.
     *
     * @throws \Exception       network/storage exception
     */
    public function storeAsImage($file, array $options = [])
    {
        if ($ext = $this->guessExt($file, true)) {
            array_set($options, 'ext', $ext);
        }

        if (is_string($file) && $this->isTmp($file)) {
            $result = $this->copy($file, $options);
        } else {
            $result = $this->store($file, $options);
        }

        return $this->parseResult(
            $result,
            $this->baseImageUrl,
            $options);
    }

    private function guessExt($file, $image = false)
    {
        if ($image) {
            // UploadedFile implements __toString() method, and this is the only
            // way to get its full path
            $path = strval($file);

            $info = getimagesize($path);
            if (!$info) {
                return false;
            }

            return ($info[2] == IMAGETYPE_PNG) ? 'png' : 'jpg';
        }

        if ($file instanceof UploadedFile) {
            return $file->getClientOriginalExtension();
        }

        return null;
    }

    private function store($file, array $options = [])
    {
        if (!is_file($file)) {
            throw new \Exception('file is invalid');
        }

        return $this->storageService->store($file, $options);
    }

    private function copy($url, array $options = [])
    {
        $urlInfo = $this->urlInfo($url);
        if (!$urlInfo) {
            throw new \Exception('url is invalid');
        }

        return $this->storageService->copy($urlInfo['id'], $options);
    }

    private function parseResult($result, $base, array $options = [])
    {
        if (array_get($options, 'id_only', true))  {
            return implode('/', [$base, $result]);
        }

        $result['id'] = implode('/', [$base, $result['id']]);
        return $result;
    }

    /**
     * get file (identified by its identifier) from external storage
     *
     * @param string $url         url of the file
     * @param array $options      options
     *                            - content         when set to true(default), content
     *                                              of that file will be returned.
     *                            - metadata        when set to true, mime of that file
     *                                              will be returned. otherwise (false default),
     *                                              no mime returned.
     *                            - null_for_nonexistence
     *                                              when set to true(default), if the file
     *                                              does not exist, return null. otherwise,
     *                                              exception will be thrown.
     *                            - bucket          (optional) which bucket to find the file
     *
     * @return resource|array     - if both content and metadata is false, it will throw exception
     *                            - if only metadata is true, the returned value is a array of
     *                            the metadata.
     *                            - if only content is true, the returned value is a resource,
     *                            it's the content of the file. use stream_get_contents() to read it.
     *                            - if both content and metadata is true, the returned value
     *                            is an array, content of the file and array of metadata should
     *                            be included. which is keyed by 'content' and 'metadata'.
     *
     * @throws \Exception         network/storage exception
     */
    public function get($url, array $options = [])
    {
        $urlInfo = $this->urlInfo($url);
        if (!$urlInfo) {
            if (array_get($options, 'null_for_nonexistence', true)) {
                return null;
            } else {
                throw new \Exception('url is invalid');
            }
        }

        return $this->storageService->get($urlInfo['id'], $options);
    }

    /**
     * remove the stored file by url
     *
     * @param string $url           url of the file to be removed
     * @param array $options         options.
     *                               - inspect  when set to true(default false), no inspection
     *                                          will be performed to check the file's existence.
     *                               - bucket   (optional) which bucket to remove the file
     *
     * @return void    if there's no exception, the removal succeeded.
     */
    public function remove($url, array $options = [])
    {
        $urlInfo = $this->urlInfo($url);
        if (!$urlInfo) {
            return;
        }

        return $this->storageService->remove($urlInfo['id'], $options);
    }

    /**
     * @param $url
     * @return array|bool  keys taken:
     *                      - image boolean
     *                      - tmp   boolean
     *                      - id    string
     */
    private function urlInfo($url)
    {
        if (empty($url)) {
            return false;
        }

        if(starts_with($url, $this->baseUrl)) {
            return [
                'image' => false,
                'tmp'   => starts_with($url, $this->baseUrl . '/tmp/'),
                'id'    => ltrim(str_replace($this->baseUrl, '', $url), '/'),
            ];
        } elseif (starts_with($url, $this->baseImageUrl)) {
            return [
                'image' => true,
                'tmp'   => starts_with($url, $this->baseImageUrl . '/tmp/'),
                'id'    => ltrim(str_replace($this->baseImageUrl, '', $url), '/'),
            ];
        } else {
            return false;
        }
    }
}