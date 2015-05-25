<?php

namespace phplib\image;

class CDWaterMark
{
    const TYPE_TEXT = 1;
    const TYPE_IMAGE = 2;

    const POS_TOP_LEFT = 1;
    const POS_TOP_CENTER = 2;
    const POS_TOP_RIGHT = 3;
    const POS_RIGHT_MIDDLE = 4;
    const POS_BOTTOM_RIGHT = 5;
    const POS_BOTTOM_CENTER = 6;
    const POS_BOTTOM_LEFT = 7;
    const POS_LEFT_MIDDLE = 8;
    const POS_CENTER_MIDDLE = 9;

    private $_type;
    private $_position = self::POS_BOTTOM_LEFT;
    private $_data;
    private $_color = array(255, 255, 255);
    private $_font;
    private $_fontsize;
    private $_angle = 0;
    private $_borderColor = false;
    private $_minWidth = 0;
    private $_minHeight = 0;

    public function __construct($type)
    {
        $this->type($type);
    }
    
    public function apply($image, $padding = 0, $alpha = null)
    {
        if ($this->isText()) {
            $alpha = $alpha === null ? 0 : $alpha;
            $this->applyText($image, $padding, $alpha);
        }
        elseif ($this->isImage()) {
            $alpha = $alpha === null ? 100 : $alpha;
            $this->applyImage($image, $padding, $alpha);
        }
        else
            throw new \CDWaterMarkException('type is invalid');
        
        return $this;
    }
    
    public function applyText($image, $padding = 0, $alpha = 0)
    {
        if ($this->isText()) {
            $pos = $this->textPosition($image, $padding);
            if ($image instanceof CDImage) {
                if ($this->_borderColor)
                    $image->textborder($this->_data, $this->_font, $this->_fontsize, $pos, $this->_color, $this->_borderColor, $alpha, $padding, $this->_angle);
                else
                    $image->text($this->_data, $this->_font, $this->_fontsize, $pos, $this->_color, $alpha, $padding, $this->_angle);
            }
            elseif (is_resource($image)) {
                $x = (int)$pos[0];
                $y = (int)$pos[1];
                
                if ($this->_borderColor)
                    CDImage::textouter($image, $this->_data, $this->_font, $this->_fontsize, $x, $y, $this->_color, $this->_borderColor, $alpha, $padding, $this->_angle);
                else
                    imagettftext($image, $this->_fontsize, $this->_angle, $x, $y, $this->_color, $this->_font, $this->_data);
            }
            return $this;
        }
        else
            throw new \CDWaterMarkException('type is not text type');
        
    }
    
    public function applyImage($image, $padding = 0, $alpha = 100)
    {
        if ($this->isImage()) {
            $pos = $this->imagePosition($image, $padding);
            if ($image instanceof CDImage) {
                $image->merge($this->_data, $pos, $alpha);
            }
            elseif (is_resource($image)) {
                
            }
            
            return $this;
        }
        else
            throw new \CDWaterMarkException('type is not image type');
    }
    
    public function textPosition($im, $padding = 0)
    {
        return self::fetchTextPosition($im, $this->_data, $this->font(), $this->_fontsize, $this->position(), $padding, $this->_angle);
    }
    
    public function imagePosition($dst, $padding = 0)
    {
        if ($this->isImage() && $this->_data)
            $src = CDImage::loadImage($this->_data);
        
        return self::fetchImagePosition($this->_position, $dst, $src, $padding);
    }
    
    public static function fetchTextPosition($im, $text, $font, $size, $position, $padding = 5, $angle = 0)
    {
        if (is_array($position))
            return $position;
        
        if (is_resource($im)) {
            $imWidth = imagesx($im);
            $imHeight = imagesy($im);
        }
        elseif ($im instanceof CDImage) {
            $imWidth = $im->width();
            $imHeight = $im->height();
        }
        else
            return false;
        
        $points = imagettfbbox($size, $angle, $font, $text);
        $textWidth = $points[2] - $points[0];
        $textHeight = $points[1] - $points[7];
        switch ($position) {
            case self::POS_TOP_LEFT:
                $x = $points[0] + $padding;
                $y = $textHeight + $padding;
                break;
            case self::POS_TOP_CENTER:
                $x = ($imWidth - $textWidth) / 2;
                $y = $textHeight + $padding;
                break;
            case self::POS_TOP_RIGHT:
                $x = $imWidth - $textWidth - $padding;
                $y = $textHeight + $padding;
                break;
            case self::POS_BOTTOM_LEFT:
                $x = $points[0] + $padding;
                $y = $imHeight - $points[1] - $padding;
                break;
            case self::POS_BOTTOM_CENTER:
                $x = ($imWidth - $textWidth) / 2;
                $y = $imHeight - $points[1] - $padding;
                break;
            case self::POS_BOTTOM_RIGHT:
                $x = $imWidth - $textWidth - $padding;
                $y = $imHeight - $points[1] - $padding;
                break;
            case self::POS_RIGHT_MIDDLE:
                $x = $imWidth - $textWidth - $padding;
                $y = $imHeight/2 + $textHeight/2;
                break;
            case self::POS_LEFT_MIDDLE:
                $x = $points[0] + $padding;
                $y = $imHeight/2 + $textHeight/2;
                break;
            case self::POS_CENTER_MIDDLE:
                $x = ($imWidth - $textWidth) / 2;
                $y = $imHeight/2 + $textHeight/2;
                break;
            default:
                break;
        }
    
        $position = array(intval($x), intval($y));
    
        return $position;
    }
    
    public static function fetchImagePosition($position, $dst, $src, $padding = 0)
    {
        if (is_array($position))
            return $position;
        
        if (is_resource($src)) {
            $srcW = imagesx($src);
            $srcH = imagesy($src);
        }
        elseif ($src instanceof CDImage) {
            $srcW = $src->width();
            $srcH = $src->height();
        }
        else
            return false;
        
        if (is_resource($dst)) {
            $dstW = imagesx($dst);
            $dstH = imagesy($dst);
        }
        elseif ($dst instanceof CDImage) {
            $dstW = $dst->width();
            $dstH = $dst->height();
        }
        else
            return false;
        
        switch ($position) {
            case self::POS_TOP_LEFT:
                $x = $y = $padding;
                break;
            case self::POS_TOP_CENTER:
                $x = ($dstW - $srcW) / 2;
                $y =  $padding;
                break;
            case self::POS_TOP_RIGHT:
                $x = $dstW - $srcW - $padding;
                $y = $padding;
                break;
            case self::POS_BOTTOM_LEFT:
                $x = $padding;
                $y = $dstH - $srcH - $padding;
                break;
            case self::POS_BOTTOM_CENTER:
                $x = ($dstW - $srcW) / 2;
                $y = $dstH - $srcH - $padding;
                break;
            case self::POS_BOTTOM_RIGHT:
                $position = array(0, $dstH-$srcH);
                $x = $dstW - $srcW - $padding;
                $y = $dstH - $srcH - $padding;
                break;
            case self::POS_CENTER_MIDDLE:
                $x = ($dstW - $srcW) / 2;
                $y = ($dstH - $srcH) / 2;
                break;
            case self::POS_LEFT_MIDDLE:
                $x = $padding;
                $y = ($dstH - $srcH) / 2;
                break;
            case self::POS_RIGHT_MIDDLE:
                $x = $dstW - $srcW - $padding;
                $y = ($dstH - $srcH) / 2;
                break;
            default:
                break;
        }
        
        return array(intval($x), intval($y));
    }
    
    public function type($type = null)
    {
        if ($type === null)
            return $this->_type;
        else{
            if (in_array($type, array(self::TYPE_TEXT, self::TYPE_IMAGE))) {
                $this->_type = $type;
                return $this;
            }
            else
                throw new \CDWaterMarkException('type value is invalid');
        }
    }
    
    public function position($pos = null)
    {
        if ($pos === null)
            return $this->_position;
        else {
            if (is_array($pos) || in_array($pos, self::positions()))
                $this->_position = $pos;
            else
                throw new \CDWaterMarkException('position value is invalid');
            return $this;
        }
            
    }
    
    public function color($color = null)
    {
        if ($color === null)
            return $this->_color;
        elseif (is_array($color) || (is_string($color) && stripos($color, '#') === 0) || is_int($color)) {
            $this->_color = $color;
            return $this;
        }
        else
            throw new \CDWaterMarkException('color value is required array');
    }
    
    public function font($font = null)
    {
        if ($font === null)
            return $this->_font;
        elseif (is_file($font)) {
            $this->_font = $font;
            return $this;
        }
        else
            throw new \CDWaterMarkException('font is not a valid font file');
    }
    
    public function angle($angle = null)
    {
        if ($angle === null)
            return $this->_angle;
        elseif ($angle >= -360 && $angle <=360) {
            $this->_angle = $angle;
            return $this;
        }
        else
            throw new \CDWaterMarkException('angle value is invalid');
    }
    
    public function setText($text)
    {
        if ($this->isText() && $text) {
            $this->_data = $text;
            return $this;
        }
        else
            throw new \CDWaterMarkException('type is not text or text is empty');
    }
    
    public function setImage($image)
    {
        if ($this->isImage() && $image) {
            $this->_data = $image;
            return $this;
        }
        else
            throw new \CDWaterMarkException('type is not text or text is empty');
    }
    
    public function fontsize($size = null)
    {
        if ($size === null)
            return $this->_fontsize;
        else {
            $this->_fontsize = (int)$size;
            return $this;
        }
    }
    
    public function borderColor($color = null)
    {
        if ($color === null)
            return $this->_borderColor;
        elseif (is_array($color) || (is_string($color) && stripos($color, '#') === 0) || is_int($color)) {
            $this->_borderColor = $color;
            return $this;
        }else
            throw new \CDWaterMarkException('border color value is required array');
    }
    
    public function minWidth($width = null)
    {
        if ($width === null)
            return $this->_minWidth;
        else {
            $this->_minWidth = (int)$width;
            return $this;
        }
    }
    
    public function minHeight($height = null)
    {
        if ($height === null)
            return $this->_minHeight;
        else {
            $this->_minHeight = (int)$height;
            return $this;
        }
    }
    
    public function isImage()
    {
        return $this->_type == self::TYPE_IMAGE;
    }
    
    public function isText()
    {
        return $this->_type == self::TYPE_TEXT;
    }
    
    public function revert()
    {
        $this->_type = null;
        $this->_position = self::POS_BOTTOM_LEFT;
        $this->_data = null;
        $this->_color = array(255, 255, 255);
        $this->_font = null;
        $this->_fontsize = null;
        $this->_angle = 0;
        $this->_borderColor = false;
        
        return $this;
    }
    
    public static function positions()
    {
        return array(self::POS_TOP_LEFT, self::POS_TOP_CENTER, self::POS_TOP_RIGHT,
            self::POS_BOTTOM_RIGHT, self::POS_BOTTOM_CENTER, self::POS_BOTTOM_LEFT,
            self::POS_RIGHT_MIDDLE, self::POS_LEFT_MIDDLE, self::POS_CENTER_MIDDLE,
        );
    }

}

class CDWaterMarkException extends Exception
{
}

