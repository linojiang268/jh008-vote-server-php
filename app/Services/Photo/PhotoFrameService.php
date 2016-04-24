<?php
namespace Jihe\Services\Photo;

use Jihe\Contracts\Services\Photo\PhotoFrameService as PhotoFrameServiceContract;

class PhotoFrameService implements PhotoFrameServiceContract
{
    // limit of font size
    private $minFontSize = 1;

    private $maxFontSize = 48;

    private $fontSizeInterval = 1;

    private $defaultFont;

    public function __construct($defaultFont)
    {
        $this->defaultFont = $defaultFont;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Servcies\Photo\PhotoFrameService::generate()
     */
    public function generate($photo, $text, array $options = [])
    {
        // get photo and font
        $photoFormat = array_get($options, 'photo_format', 'png');
        $font = array_get($options, 'font', $this->defaultFont);

        $maxWidthOrHeight = array_get($options, 'max_width_or_height', 800);
        $paddingLeftOfText = array_get($options, 'padding_left', 20);
        $paddingRightOfText = array_get($options, 'padding_right', 20);
        $border = 0;

        // adjust photo
        list($orgWidth, $orgHeight, $type, $attr) = getimagesize($photo);
        list($width, $height, $resized) = $this->adjustPhotoSize($orgWidth, $orgHeight, $maxWidthOrHeight);

        // adjust text
        list($fontSize, $textWidth, $textHeight) = $this->getFontSize($text, $font, $width,
                                                        $paddingLeftOfText, $paddingRightOfText);

        // ajust border of bottom and base line of text
        $borderBottom = $textHeight * 2;
        $baseLineOfText = ($borderBottom + $textHeight) / 2 - $textHeight * 0.2;

        // create background
        $dest = $this->createBackgroud($width + $border * 2,
                                       $height + $border + $borderBottom);

        // merge background with photo
        $this->merge($dest, $photo, [
            'photo_format' => $photoFormat,
            'width' => $width,
            'height' => $height,
            'border' => $border,
            'resized' => $resized,
            'org_width' => $orgWidth,
            'org_height' => $orgHeight,
        ]);

        // fill text in the background
        $this->fillText($dest, $text, $font, $fontSize,
                        $border + $paddingLeftOfText,
                        $border + $height, $baseLineOfText);

        return $this->save($dest, array_get($options, 'save_as'),
                           array_get($options, 'save_as_format', 'png'));
    }

    /**
     * get the adjustive width and height if given width
     * is over maxWidth or given height is over maxHeight,
     * otherwist return the orgWith and orgHeight
     *
     * @param $width        width of photo
     * @param $height       height of photo
     * @return array        [adjustiveWidth, adjustiveHeight, resized]
     */
    private function adjustPhotoSize($width, $height, $maxWidthOrHeight)
    {
        if ($this->isHorizontal($width, $height)) {
            if ($width > $maxWidthOrHeight) {
                return [
                    $maxWidthOrHeight,
                    intval($height / ($width / $maxWidthOrHeight)),
                    true,
                ];
            }
        } else {
            // photo is vertical
            if ($height > $maxWidthOrHeight) {
                return [
                    intval($width / ($height / $maxWidthOrHeight)),
                    $maxWidthOrHeight,
                    true,
                ];
            }
        }

        return [$width, $height, false];
    }

    /**
     * whether photo is horizontal
     *
     * @param $width         width of photo
     * @param $height        width of height
     * @return bool          true if photo if horizontal,
     *                       otherwise false
     */
    private function isHorizontal($width, $height)
    {
        return ($width > $height) ? true : false;
    }

    /**
     * get font size of text
     *
     * @param $text            text will be filled in
     * @param $font            font used by
     * @param $width           width of photo
     * @param $paddingLeft     paddingLeft of text
     * @param $paddingRight    paddingRight of right
     * @return array           [fontSize, textWidth, textHeight]
     */
    private function getFontSize($text, $font, $width, $paddingLeft, $paddingRight)
    {
        $fontSize = $this->maxFontSize;

        do {
            $box = imagettfbbox($fontSize, 0, $font, $text);
            $textHeight = abs($box[5] - $box[1]);
            $textWidth = abs($box[4] - $box[0]);

            $fontSize -= $this->fontSizeInterval;
        } while ($textWidth + $paddingLeft + $paddingRight > $width && $fontSize > $this->minFontSize);

        return [$fontSize + $this->fontSizeInterval, $textWidth, $textHeight];
    }

    /**
     * create the backgroud of photo
     *
     * @param $width       width of background
     * @param $height      height of background
     * @return resource
     */
    private function createBackgroud($width, $height)
    {
        return imagecreatetruecolor($width, $height);
    }

    /**
     * merge background with photo
     *
     * @param $background
     * @param $photo
     * @param array $options    options to merge background with photo, keys taken:
     *                           - photo_format
     *                           - width
     *                           - height
     *                           - border
     *                           - resized false(default)
     *                           - org_width
     *                           - org_height
     *
     */
    private function merge($background, $photo, array $options)
    {
        $width = array_get($options, 'width');
        $height = array_get($options, 'height');
        $border = array_get($options, 'border');
        $resized = array_get($options, 'resized', false);

        $format = array_get($options, 'photo_format');
        // call 'imagecreatedfromxxx' method
        $method = 'imagecreatefrom' . $format;
        if (!function_exists($method)) {
            throw new \Exception(sprintf('Unsupported create image from format - %s', $format));
        }

        if ($resized) {
            $orgWidth = array_get($options, 'org_width');
            $orgHeight = array_get($options, 'org_height');

            imagecopyresized($background, call_user_func($method, $photo),
                             $border, $border, 0, 0, $width, $height, $orgWidth, $orgHeight);
            return;
        }

        imagecopy($background, call_user_func($method, $photo),
                  $border, $border, 0, 0, $width, $height);
    }

    /**
     * fill font in the text background
     *
     * @param $background   background of photo
     * @param $text         text need to filled
     * @param $font         font file path
     * @param $fontSize     font size of text
     * @param $x
     * @param $y
     * @baseLine            base line of text
     */
    private function fillText($background, $text, $font, $fontSize, $x, $y, $baseLine)
    {
        imagettftext($background, $fontSize, 0, $x, $y + $baseLine,
                     imagecolorallocate($background, 0xFF, 0xFF, 0xFF), $font, $text);

        imagettftext($background, $fontSize, 0, $x + 1, $y + $baseLine + 1,
                     imagecolorallocate($background, 0xFF, 0xFF, 0xFF), $font, $text);
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

        // call 'imagexxx' method to save the photo
        $method = 'image' . $format;
        if (!function_exists($method)) {
            throw new \Exception(sprintf('Unsupported save as format - %s', $format));
        }

        if ($saveAs) { // save as file specified
            return call_user_func($method, $image, $saveAs);
        }

        // capture the output and return the binary form of the photo
        ob_start();
        call_user_func($method, $image);
        return ob_get_clean();
    }
}