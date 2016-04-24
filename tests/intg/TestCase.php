<?php
namespace intg\Jihe;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TestCase extends BaseTestCase
{
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * override magic __call method to
     * - add ajax ability
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (starts_with($name, 'ajax')) {
            return $this->handleAjaxRequest(substr($name, 4), $arguments);
        }

        return parent::__call($name, $arguments);
    }

    /**
     * /**
     * @param string $method     one of the following will be accepted
     *                           - http verbs, e.g. 'Get', 'Post', etc.
     *                           - http verbs prefixed with 'Secure' for https protocol, e.g.
     *                             'SecureGet', 'SecurePost', etc
     *                           - 'Call'
     *                           - 'CallSecure'
     * @param array  $arguments  arguments
     *
     * @return $this
     * @see \Illuminate\Foundation\Testing\CrawlTrait::call()
     * @see \Illuminate\Foundation\Testing\CrawlTrait::callSecure()
     */
    private function handleAjaxRequest($method, $arguments)
    {
        $method = strtoupper($method);  // to make $method case-insensitive, uppercase it
        $callSecure = $method == 'CALLSECURE'; // are we dealing with 'https' or 'http'

        // prepend $method to $arguments if $method is HTTP verbs
        if (!in_array($method, ['CALL', 'CALLSECURE'])) {
            if (starts_with($method, 'SECURE')) {
                $method = substr($method, 6);
                $callSecure = true;
            }
            array_unshift($arguments, $method);
        }

        // we're going to take advantage of \Illuminate\Foundation\Testing\CrawlTrait::call()
        // to make it ajax-like, we'll stuff a header into $server (the 6th argument of call() method)
        // One thing to note: the header is not HTTP but Symfony (the supporting of Laravel) specific.
        // More precisely, we should add the following header to make it ajax-like (note the heading
        // HTTP_):
        //
        //  'HTTP_X-REQUESTED-WITH' => 'XMLHttpRequest'
        //

        $arguments = array_pad($arguments, 7, []); // give default values to argument
        // special handling for the last $content, since it's not an array.
        // if it is, it's the side effect of array_pad() method above, and
        // in that case, we set it as null (which is its default value).
        is_array($arguments[6]) && ($arguments[6] = null);

        // mixin ajax request header
        $arguments[5] = array_merge($arguments[5], [
            'HTTP_X-REQUESTED-WITH' => 'XMLHttpRequest'
        ]);

        call_user_func_array([$this, $callSecure ? 'callSecure' : 'call'], $arguments);

        return $this;
    }

    /**
     * create an uploaded file from file on file system
     *
     * @param string $path        path of file to be uploaded (MUST exist)
     * @param string|null $name   file name. if not specified, basename of $path will be applied
     * @param string|null $mime   client mime/content-type of file
     *
     * @return UploadedFile
     */
    protected final function makeUploadFile($path, $name = null, $mime = null)
    {
        if (empty($name)) {
            $name = pathinfo($path, PATHINFO_BASENAME);
        }

        return new UploadedFile($path, $name, $mime, filesize($path), null, true);
    }

    /**
     * mock files on file system
     *
     * @param string $root        root directory
     * @param array|null $files   directory structure to add under root directory, refer to vfsStream for more
     *
     * @see \org\bovigo\vfs\vfsStream::create()
     */
    protected final function mockFiles($root, array $files = null)
    {
        vfsStream::setup($root, null, $files ?: []);
    }

    /**
     * get the path of a mocked file
     *
     * @param string $path   relative path (without root directory and scheme)
     * @return string        file path on mocked file system
     */
    protected final function getMockedFilePath($path)
    {
        return vfsStream::url($path);
    }

    protected function mockJob($jobClass, $callback)
    {
        \Bus::shouldReceive('dispatch')
            ->atLeast()
            ->once()
            ->with(\Mockery::on(function ($job) use ($jobClass, $callback) {
                if ($jobClass != get_class($job)) {
                    return false;
                }
                return call_user_func_array($callback, func_get_args());
            }));
    }
}
