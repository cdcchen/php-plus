<?php
/**
 * Created by PhpStorm.
 * User: chendong
 * Date: 15/5/14
 * Time: 下午10:19
 */

namespace phplib\graphics\filters;


use phplib\graphics\FilterBase;
use phplib\graphics\SimpleImage;

class OuterGlowTextFilter extends FilterBase
{
    protected $text;
    protected $font;
    protected $fontSize;
    protected $position;

    protected function init(array $args)
    {
        $this->text = $args['text'];
        $this->font = $args['font'];
        $this->fontSize = $args['fontSize'];
        $this->position = $args['position'];
    }

    protected function run(array $args)
    {
        if (empty($this->text) || empty($this->font) || empty($this->fontSize))
            throw new \InvalidArgumentException('text, font, fontSize args is required.');

        if (is_array($this->position))
            $pos = $this->position;
        else {
            if (empty($this->position))
                $this->position = SimpleImage::POSITION_BOTTOM_RIGHT;
            $pos = $this->image->getTextPosition($this->text, $this->font, $this->fontSize, $this->position, $args['padding']);
        }

        $textColor = $args['textColor'];
        if (empty($textColor)) $textColor = [0, 0, 0];

        $outerColor = $args['outerColor'];
        if (empty($outerColor)) $outerColor = [255, 255, 255];

        static::textOuter($this->imageHandle, $this->text, $this->font, $this->fontSize, (int)$pos[0], (int)$pos[1], $textColor, $outerColor, (int)$args['alpha'], (int)$args['angle']);

        return $this;
    }

    /**
     * @param resource $im
     * @param string$text
     * @param string $font
     * @param int $size
     * @param int $x 注意此处的坐标是以文字左上角为圆点
     * @param int $y
     * @param array|int|string $color
     * @param array $outer
     * @param int $alpha 聚值0-127
     * @param int $angle
     * @return $this
     */
    protected static function textOuter($im, $text, $font, $size, $x, $y, $color = [0, 0, 0], $outer = [255, 255, 255], $alpha = 0, $angle = 0)
    {
        $x = (int)$x;
        $y = (int)$y;

        $ttf = false;
        if (@file_exists($font) && @is_readable($font)) {
            $ttf = true;
            $area = imagettfbbox($size, $angle, $font, $text);
            $width = $area[2] - $area[0] + 2;
            $height = $area[1] - $area[5] + 2;
        }
        else {
            $width = strlen($text) * 10;
            $height = 16;
        }

        $im_tmp = imagecreate($width, $height);
        $white = imagecolorallocatealpha($im_tmp, 255, 255, 255, $alpha);
        $black = imagecolorallocatealpha($im_tmp, 0, 0, 0, $alpha);

        $color = SimpleImage::colorAllocateAlpha($im, $color, $alpha);
        $outer = SimpleImage::colorAllocateAlpha($im, $outer, $alpha);

        if ($ttf) {
            imagettftext($im_tmp, $size, 0, 0, $height - 2, $black, $font, $text);
            imagettftext($im, $size, 0, $x, $y, $color, $font, $text);
            $y = $y - $height + 2;
        }
        else {
            imagestring($im_tmp, $size, 0, 0, $text, $black);
            imagestring($im, $size, $x, $y, $text, $color);
        }

        for ($i = 0; $i < $width; $i ++) {
            for ($j = 0; $j < $height; $j ++) {
                $c = imagecolorat($im_tmp, $i, $j);
                if ($c !== $white) {
                    imagecolorat($im_tmp, $i, $j - 1) != $white || imagesetpixel($im, $x + $i, $y + $j - 1, $outer);
                    imagecolorat($im_tmp, $i, $j + 1) != $white || imagesetpixel($im, $x + $i, $y + $j + 1, $outer);
                    imagecolorat($im_tmp, $i - 1, $j) != $white || imagesetpixel($im, $x + $i - 1, $y + $j, $outer);
                    imagecolorat($im_tmp, $i + 1, $j) != $white || imagesetpixel($im, $x + $i + 1, $y + $j, $outer);

                    // 取消注释，与Fireworks的发光效果相同
                    imagecolorat($im_tmp, $i - 1, $j - 1) != $white || imagesetpixel($im, $x + $i - 1, $y + $j - 1, $outer);
                    imagecolorat($im_tmp, $i + 1, $j - 1) != $white || imagesetpixel($im, $x + $i + 1, $y + $j - 1, $outer);
                    imagecolorat($im_tmp, $i - 1, $j + 1) != $white || imagesetpixel($im, $x + $i - 1, $y + $j + 1, $outer);
                    imagecolorat($im_tmp, $i + 1, $j + 1) != $white || imagesetpixel($im, $x + $i + 1, $y + $j + 1, $outer);
                }
            }
        }

        imagedestroy($im_tmp);

        return $im;
    }
}