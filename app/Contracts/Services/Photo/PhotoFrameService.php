<?php
namespace Jihe\Contracts\Services\Photo;

interface PhotoFrameService
{
    /**
     * generate frame of photo
     *
     * @param $photo            path to an image that's supposed to be put in the top of generated photo
     * @param $text             content to be put in the bottom of generated photo
     * @param array $options    options (include but not limited to):
     *                           - photo_format        (optional)photo format. might be one of
     *                                                 'png'(default) and 'jpeg'
     *                           - font                path to a font that's used to text,
     *                                                 storage  STZHONGS.ttf(default)
     *                           - max_width_or_height the new width or height the photo should be scaled
     *                                                 if width or height of photo is over the maxWidthOrHeight
     *                                                 800(default)
     *                           - padding_left        paddingLeft of text, 20(default)
     *                           - padding_right       paddingRight of text, 20(default)
     *                           - save_as             file path this generated photo will be saved as
     *                           - save_as_format      the format of the save as file. if not set, it can be deduced
     *                                                 from save_as option. (defaults to 'png')
     *
     * @return bool|string  if save_as option is enabled, a boolean indicating the success or failure will be returned.
     *                      otherwise, the binary string(NOT stream) will be returned.
     */
    public function generate($photo, $text, array $options = []);
}