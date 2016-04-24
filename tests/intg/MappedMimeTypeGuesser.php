<?php
namespace intg\Jihe;

use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

class MappedMimeTypeGuesser implements MimeTypeGuesserInterface
{
    private $mappings = [];

    public function __construct(array $mappings = null)
    {
        $mappings && $this->mappings = $mappings;
    }

    /**
     * add mapping
     *
     * @param string $path
     * @param string $mime
     */
    public function map($path, $mime)
    {
        $this->mappings[$path] = $mime;
    }

    /**
     * Guesses the mime type of the file with the given path.
     *
     * @param string $path The path to the file
     *
     * @return string The mime type or NULL, if none could be guessed
     *
     */
    public function guess($path)
    {
        return array_get($this->mappings, $path);
    }
}