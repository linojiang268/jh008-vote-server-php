<?php
namespace Jihe\Contracts\Services\Qrcode;

interface QrcodeService
{
    /**
     * generate qrcode
     *
     * @param string $content   the content, e.g., links, text, etc
     * @param array $options    options (include but not limited to):
     *                           - size              the width/height the qrcode
     *                           - ecl               (optional)error correction level. might be one of
     *                                               * 'high'     (default)  up to 30% damage
     *                                               * 'quartile' up to 25% damage
     *                                               * 'medium'   up to 15% damage
     *                                               * 'low'      up to 7% damage
     *                           - logo              path to an image that's supposed to be put in
     *                                               the middle of the generated qrcode
     *                           - logo_format       (optional)logo format. might be one of
     *                                               'png'(default) and 'jpeg'
     *                           - logo_scale_width  the new width the logo should be scaled to
     *                           - logo_scale_height the new height the logo should be scaled to
     *                           - fgcolor           foreground color (in RRGGBBAA string), default to '00000000'
     *                           - bgcolor           background color (in RRGGBBAA string), default to 'FFFFFF00'
     *                           - save_as           file path this generated qrcode will be saved as
     *                           - save_as_format    the format of the save as file. if not set, it can be deduced
     *                                               from save_as option. (defaults to 'png')
     *
     * @return bool|string  if save_as option is enabled, a boolean indicating the success or failure will be returned.
     *                      otherwise, the binary string(NOT stream) will be returned.
     */
    public function generate($content, array $options = []);
}
