<?php
namespace Jihe\Services\Photo;

class PhotoService
{
    /**
     * generate frame of photo
     *
     * @param $photo            path to an image that's supposed to be cropped
     * @param array $options    options:
     *                           - format         (optional)photo format. might be one of 'png'(default) and 'jpeg'
     *                           - x
     *                           - y
     *                           - width
     *                           - height
     *                           - save_as         file path this generated photo will be saved as
     *                           - save_as_format  the format of the save as file. if not set, it can be deduced
     *                                             from save_as option. (defaults to 'png')
     *
     * @return bool|string  if save_as option is enabled, a boolean indicating the success or failure will be returned.
     *                      otherwise, the binary string(NOT stream) will be returned.
     */
    public function crop($photo, array $options = [])
    {
        $format = array_get($options, 'format', 'png');

        $method = 'imagecreatefrom' . $format;
        if (!function_exists($method)) {
            throw new \Exception(sprintf('Unsupported read as format - %s', $format));
        }

        $image = call_user_func($method, $photo);

        if (!is_resource($image)) {
            throw new \Exception('resource exception');
        }

        $dest = imagecrop($image, [
            'x'      => array_get($options, 'x'),
            'y'      => array_get($options, 'y'),
            'width'  => array_get($options, 'width'),
            'height' => array_get($options, 'height'),
        ]);

        return $this->save($dest, array_get($options, 'save_as'),
                           array_get($options, 'save_as_format', 'png'));
    }

    /**
     * save given image
     *
     * @param resource $image      image to save
     * @param string|null $saveAs  file path to save. if null, the content of the image
     *                             will be returned.
     * @param string $format       format to save as
     *
     * @throws \Exception          if save as format is not supported
     * @return bool|string         if save as file is specified, a boolean value indicating
     *                             the success or failure will be returned. otherwise, the
     *                             content(binary string) of the image will be returned
     */
    private function save($image, $saveAs, $format = 'png')
    {
        $format = pathinfo($saveAs, PATHINFO_EXTENSION) ?: $format;
        $format = ('jpg' == $format) ? 'jpeg' : $format;

        // call 'imagexxx' method to save the qrcode
        $method = 'image' . $format;
        if (!function_exists($method)) {
            throw new \Exception(sprintf('Unsupported save as format - %s', $format));
        }

        if ($saveAs) { // save as file specified
            return call_user_func($method, $image, $saveAs);
        }

        // capture the output and return the binary form of the qrcode
        ob_start();
        call_user_func($method, $image);
        return ob_get_clean();
    }
}