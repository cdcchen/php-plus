<?php
/**
 * Created by PhpStorm.
 * User: chendong
 * Date: 15/5/20
 * Time: 下午5:27
 */

namespace phplib\graphics\filters;


use phplib\graphics\FilterBase;
use phplib\graphics\ImageException;
use phplib\graphics\SimpleImage;


/**
 * Class StitchingFilter
 * @package phplib\graphics\filters
 * @require php > 5.4
 */
class StitchingFilter  extends FilterBase
{
    protected $images = [];
    protected $spacing = 10;
    protected $padding = 0;


    public function init(array $args)
    {
        $this->images = $args['images'];
        if (count($this->images) === 1)
            throw new ImageException('Need at least two images.');

        $this->spacing = (int)$args['spacing'];
        $this->padding = (array)$args['padding'];
    }

    protected function run(array $args)
    {
        $images = $this->convertImageToResource();
        $size = $this->calculateImageSize($images);


        $bgColor = isset($args['bgColor']) ? $args['bgColor'] : '#FFFFFF';
        $alpha = isset($args['alpha']) ? $args['alpha'] : 0;
        $type = isset($args['type']) ? $args['type'] : IMAGETYPE_PNG;

        if (!$this->image || !$this->image) {
            $image = SimpleImage::create($size[0], $size[1], $bgColor, $alpha, $type);
            $this->setSimpleImage($image);
        }
        else {
            $bgWidth = $this->image->width();
            $bgHeight = $this->image->height();
            $width = ($bgWidth > $size[0]) ? $bgWidth : $size[0];
            $height = ($bgHeight > $size[1]) ? $bgHeight : $size[1];
            $this->image->resizeCanvas(false, $width, $height);
        }

        $x = (int)$this->padding[0];
        $y = isset($this->padding[1]) ? (int)$this->padding[1] : (int)$this->padding;
        foreach ($images as $im) {
            $this->image->merge($im, [$x, $y]);
            $y += imagesy($im) + $this->spacing;

        }
    }

    protected function convertImageToResource()
    {
        $_images = [];
        foreach ($this->images as $index => $image) {
            if (@file_exists($image) && @is_readable($image))
                $_images[] = SimpleImage::loadFile($image);
            elseif (is_resource($image) && imagesx($image))
                $_images[] = $image;
            elseif (getimagesizefromstring($image))
                $_images[] = imagecreatefromstring($image);
            else
                throw new ImageException('image ' . $index .' is not a valid mode image data.');
        }

        return $_images;
    }

    protected function calculateImageSize($images)
    {
        $width = $height = [];
        foreach ($images as $im) {
            $width[] = imagesx($im);
            $height[] = imagesy($im);
        }

        $width = max($width) + (int)$this->padding[0] * 2;
        $padding = isset($this->padding[1]) ? $this->padding[1] : $this->padding[0];
        $height = array_sum($height) + $padding * 2 + $this->spacing * (count($images) - 1);

        return [$width, $height];
    }
}