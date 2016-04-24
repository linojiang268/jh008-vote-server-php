<?php
namespace Jihe\Services\Qrcode;

use Jihe\Contracts\Services\Qrcode\QrcodeService as QrcodeServiceContract;
use Endroid\QrCode\QrCode;
use GuzzleHttp\Promise\Tests\Thennable;

class QrcodeService implements QrcodeServiceContract
{
    /**
     * (non-PHPdoc)
     * 
     * @see \Jihe\Contracts\Servcies\Qrcode\QrcodeService::generate()
     */
    public function generate($content, array $options = [])
    {
        // check size: size of the qrcode - 2 * padding >= size of logo 
        $size = array_get($options, 'size', 200);
        $padding = array_get($options, 'padding', 10);
        
        $widthOfLogo  = array_get($options, 'logo_scale_width',  0);
        $heightOfLogo = array_get($options, 'logo_scale_height', $widthOfLogo);
        $logoFormat = array_get($options, 'logo_format', 'png');
        if ($logo = array_get($options, 'logo')) { // logo given
            if ($widthOfLogo > 0 && $heightOfLogo > 0) { // scale width and height specified
                $logo = $this->scale($logo, $logoFormat, $widthOfLogo, $heightOfLogo);
            } else {
               list($widthOfLogo, $heightOfLogo) = getimagesize($logo);
               $logo = $this->createImage($logo, $logoFormat);
            }
        }
        if ($size - 2 * $padding <= max($widthOfLogo, $heightOfLogo)) {
            throw new \Exception('Size of qrcode is too small to hold both padding and logo image');
            // we can make this constraint more restrictive since just meeting
            // this requirement cannot guarantee the generated qrcode being recognized.
        }
        
        $qrcode = (new Qrcode)->setText($content)
                              ->setSize($size - $padding * 2)  // actual size of qrcode is $size - $padding * 2
                              ->setPadding($padding)
                              ->setErrorCorrection(array_get($options, 'ecl', 'high'))
                              ->setForegroundColor($this->parseColor(array_get($options, 'fgcolor', '00000000')))
                              ->setBackgroundColor($this->parseColor(array_get($options, 'bgcolor', 'FFFFFF00')))
                              ->getImage();
        // $logo is a resource refers to the logo image
        if ($logo != null) {
            $qrcode = $this->merge($qrcode, $size, $padding, 
                                   $logo, $widthOfLogo, $heightOfLogo);
            imagedestroy($logo);
        }
        
        $ret = $this->save($qrcode, array_get($options, 'save_as'), 
                                    array_get($options, 'save_as_format', 'png'));
        imagedestroy($qrcode); // destroy the resouce to free some memory
        
        return $ret;
    }
    
    /**
     * scale given image
     * @param resouce|string $image  image resource or its path
     * @param string $format         format of the image
     * @param int $width             width to scale to
     * @param int $height            height to scale to
     * 
     * @return resource              scaled image resource
     */
    private function scale($image, $format, $width, $height)
    {
        if (is_string($image)) {
            $image = $this->createImage($image, $format);
        }
        
        return imagescale($image, $width, $height, IMG_BICUBIC_FIXED);
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
    
    // merge the qrcode and a logo on the center of it
    private function merge($dest, $sizeOfQrcode, $paddingOfQrcode, 
                           $src, $widthOfLogo, $heightOfLogo)
    {
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        
        $this->imageCopyMergeAlpha($dest, $src, 
                                   $paddingOfQrcode + ($sizeOfQrcode - $widthOfLogo) / 2, 
                                   $paddingOfQrcode + ($sizeOfQrcode - $heightOfLogo) / 2, 
                                   0, 0, 
                                   $widthOfLogo, $heightOfLogo, 
                                   100);
        
        return $dest;
    }
    
    private function createImage($file, $format = 'png')
    {
        // going to call imagecreatfromXXX function to turn $logo into resource
        // ensure that the format is acceptable
        $method = 'imagecreatefrom' . $format;
        if (!function_exists($method)) {
            throw new \Exception(sprintf('Unsupported logo format - %s', $format));
        }
        
        return call_user_func($method, $file);
    }
    
    // convert 'RRGGBBAA' color to ['r' => RR, 'g' => GG, 'b' => BB, 'a' => AA]
    private function parseColor($color) 
    {
        return ['r' => intval(substr($color, 0, 2), 16),
                'g' => intval(substr($color, 2, 2), 16),
                'b' => intval(substr($color, 4, 2), 16),
                'a' => intval(substr($color, 6, 2), 16), 
        ];
    }

    // imagecopymerge inverts the aplha of logo file, which is not desired
    // and this method does not do that
    // this function takes the same argumenets a imagecopymerge(), refer to
    // http://php.net/manual/en/function.imagecopymerge.php
    private function imageCopyMergeAlpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct)
    {
        // creating a cut resource
        $cut = imagecreatetruecolor($src_w, $src_h);
        // copying relevant section from background to the cut resource
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
        // copying relevant section from watermark to the cut resource
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
        // insert cut resource to destination image
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);
    }
}