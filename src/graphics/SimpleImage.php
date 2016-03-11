<?php
/**
 * Created by PhpStorm.
 * User: chendong
 * Date: 15/5/7
 * Time: 上午11:17
 * @author Chris Chen(cdcchen@gmail.com)
 * @site http://www.24beta.com/
 */

namespace phpplus\graphics;

/**
 * Class SimpleGD
 * @package yiiext\image
 */
class SimpleImage
{
    const POS_TOP_LEFT = 1;
    const POS_TOP_CENTER = 2;
    const POS_TOP_RIGHT = 3;
    const POS_RIGHT_MIDDLE = 4;
    const POS_BOTTOM_RIGHT = 5;
    const POS_BOTTOM_CENTER = 6;
    const POS_BOTTOM_LEFT = 7;
    const POS_LEFT_MIDDLE = 8;
    const POS_CENTER_MIDDLE = 9;
    
    const CANVAS_VERTICAL_TOP = 1;
    const CANVAS_VERTICAL_BOTH = 2;
    const CANVAS_VERTICAL_BOTTOM = 3;
    const CANVAS_HORIZONTAL_LEFT = 4;
    const CANVAS_HORIZONTAL_BOTH = 5;
    const CANVAS_HORIZONTAL_RIGHT = 6;
    
    public $version = '1.0';
    public $fontPath;

    protected $_im;
    protected $_imageType = IMAGETYPE_PNG;
    
    protected static $_createFunctions = array(
        IMAGETYPE_GIF => 'imagecreatefromgif',
        IMAGETYPE_JPEG => 'imagecreatefromjpeg',
        IMAGETYPE_PNG => 'imagecreatefrompng',
        IMAGETYPE_WBMP => 'imagecreatefromwbmp',
        IMAGETYPE_XBM => 'imagecreatefromxmb',
        IMAGETYPE_XBM => 'imagecreatefromwebp',
    );
    
    protected static $_outputFuntions = [
        IMAGETYPE_GIF => 'imagegif',
        IMAGETYPE_JPEG => 'imagejpeg',
        IMAGETYPE_PNG => 'imagepng',
        IMAGETYPE_WBMP => 'imagewbmp',
        IMAGETYPE_XBM => 'imagexmb',
        IMAGETYPE_WEBP => 'imagewebp',
    ];
    
    public function __construct($im, $imageType = null)
    {
        if (is_resource($im))
            $this->setImageHandle($im);
        else
            throw new ImageException('$im is not valid image resource.');

        if ($imageType !== null)
            $this->setImageType($imageType);

        if (!static::validateImageType($this->_imageType))
            throw new ImageException('$imageType is not supported.');
    }
    
    public static function create($width, $height, $bgColor = '#FFFFFF', $alpha = 0, $type = IMAGETYPE_PNG)
    {
        $im = imagecreatetruecolor($width, $height);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        $color = static::colorAllocateAlpha($im, $bgColor, $alpha);
        imagefill($im, 0, 0, $color);

        return new static($im, $type);
    }

    /**
     * 从文件地址载入图像
     * @param string $data 图像路径或图像数据
     * @throws ImageException
     * @return $this
     */
    public static function load($data)
    {
        $im = static::createImage($data);
        $imageType = static::getImageType($data);

        if ($imageType === IMAGETYPE_UNKNOWN)
            throw new ImageException('image type is unknown.');

        $instance = new static($im, $imageType);
        return $instance;
    }

    /**
     * @param string $data 图像路径或图像数据
     * @return resource 图像资源
     */
    public static function createImage($data)
    {
        if (@file_exists($data) && @is_readable($data)) {
            $image = static::loadFile($data);
        }
        else
            $image = imagecreatefromstring($data);

        return $image;
    }

    /**
     * 从文件地址载入图像
     * @param string $file 图像路径
     * @throws ImageException
     * @return resource 图像资源
     */
    public static function loadFile($file)
    {
        $info = getimagesize($file);
        $type = $info[2];
        $function = self::$_createFunctions[$type];
        if (static::validateImageType($type) && function_exists($function)) {
            return call_user_func($function, $file);
        }
        else
            throw new ImageException('不支持' . $type . '图像格式', 0);
    }

    public function setImageType($type)
    {
        $this->_imageType = $type;
        return $this;
    }

    public function setFontPath($path)
    {
        if (@file_exists($path) && @is_readable($path)) {
            $this->fontPath = $path;
            return $this;
        }
        else
            throw new ImageException('font path is not exist or unreadable.');
    }

    public function setImageHandle($im)
    {
        $this->_im = $im;
        return $this;
    }



    /**
     * 返回图像宽度
     * @return integer 图像的宽度
     */
    public function width()
    {
        return imagesx($this->_im);
    }
    
    /**
     * 返回图像高度
     * @return integer 图像高度
     */
    public function height()
    {
        return imagesy($this->_im);
    }
    
    public function getMimeType()
    {
        return image_type_to_mime_type($this->_imageType);
    }
    
    public function getExtensionName($includeDot = true)
    {
        return image_type_to_extension($this->_imageType, $includeDot);
    }
    
    
    public function convertType($type)
    {
        $function = self::$_createFunctions[$type];
        if (static::validateImageType($type) && function_exists($function))
            $this->_imageType = $type;
        else
            throw new ImageException('Not support this type.');

        return $this;
    }

    /**
     * @param $filename
     * @param int|bool $mode
     * @return $this|bool
     */
    public function save($filename, $mode = false)
    {
        static::saveAlpha($this->_im);
        $function = self::$_outputFuntions[$this->_imageType];
        if (function_exists($function) && call_user_func($function, $this->_im, $filename)) {
            if ($mode) chmod($filename, $mode);
            return $this;
        }
        else
            return false;
    }
    
    /**
     * 将图像保存为gif类型
     * @param string $filename 图片文件路径，不带扩展名
     * @param int|bool $mode 图像文件的权限
     * @return $this|bool
     */
    public function saveAsGif($filename, $mode = false)
    {
        static::validateImageType(IMAGETYPE_GIF, true);

        if (imagegif($this->_im, $filename)) {
            if ($mode) chmod($filename, $mode);
            return $this;
        }
        else
            return false;
    }
    
    /**
     * 将图像保存为jpeg类型
     * @param string $filename 图片文件路径，不带扩展名
     * @param int $quality 图像质量，取值为0-100
     * @param int|bool $mode 图像文件的权限
     * @return $this|bool
     */
    public function saveAsJpeg($filename, $quality = 100, $mode = false)
    {
        static::validateImageType(IMAGETYPE_JPEG, true);

        if (imagejpeg($this->_im, $filename, $quality)) {
            if ($mode) chmod($filename, $mode);
            return $this;
        }
        else
            return false;
    }

	/**
     * 将图像保存为png类型
     * @param string $filename 图片文件路径，不带扩展名
     * @param int $quality 图像质量，取值为0-9
     * @param int $filters PNG图像过滤器，取值参考imagepng函数
     * @param int|bool $mode 图像文件的权限
     * @return $this|bool
     */
    public function saveAsPng($filename, $quality = 9, $filters = null, $mode = false)
    {
        static::validateImageType(IMAGETYPE_PNG, true);

        static::saveAlpha($this->_im);
        if (imagepng($this->_im, $filename, $quality, $filters)) {
            if ($mode) chmod($filename, $mode);
            return $this;
        }
        else
            return false;
    }
    
    /**
     * 将图像保存为wbmp类型
     * @param string $filename 图片文件路径，不带扩展名
     * @param int $foreground 前景色，取值为imagecolorallocate()的返回的颜色标识符
     * @param int|bool $mode 图像文件的权限
     * @return $this|bool
     */
    public function saveAsWbmp($filename, $foreground  = null, $mode = false)
    {
        static::validateImageType(IMAGETYPE_WBMP, true);

        if (imagewbmp($this->_im, $filename, $foreground)) {
            if ($mode) chmod($filename, $mode);
            return $this;
        }
        else
            return false;
    }
    
    /**
     * 将图像保存为xbm类型
     * @param string $filename 图片文件路径，不带扩展名
     * @param int $foreground 前景色，取值为imagecolorallocate()的返回的颜色标识符
     * @param int $mode 图像文件的权限
     * @return $this|bool
     */
    public function saveAsXbm($filename, $foreground  = 0, $mode = null)
    {
        static::validateImageType(IMAGETYPE_XBM, true);

        if (imagexbm($this->_im, $filename, $foreground)) {
            if ($mode) chmod($filename, $mode);
            return $this;
        }
        else
            return false;
    }

    /**
     * 将图像保存为xbm类型
     * @param string $filename 图片文件路径，不带扩展名
     * @param int $mode 图像文件的权限
     * @return $this|bool
     */
    public function saveAsWebp($filename, $mode = null)
    {
        static::validateImageType(IMAGETYPE_WEBP, true);

        if (imagewebp($this->_im, $filename)) {
            if ($mode) chmod($filename, $mode);
            return $this;
        }
        else
            return false;
    }

    /**
     * 按照图像原来的类型输出图像数据
     */
    public function output()
    {
        $function = self::$_outputFuntions[$this->_imageType];
        if (function_exists($function)) {
            static::saveAlpha($this->_im);
            header('Content-Type: ' . $this->getMimeType());
            call_user_func($function, $this->_im);
        }
        else
            throw new ImageException($function . ' function is undefined');
    }
    
    /**
     * 以gif类型输出图像数据
     */
    public function outputAsGif()
    {
        static::validateImageType(IMAGETYPE_GIF, true);

        header('Content-Type: ' . image_type_to_mime_type(IMAGETYPE_GIF));
        imagegif($this->_im);
    }
    
    /**
     * @param int $quality
     * 以jpge类型输出图像数据
     */
    public function outputAsJpeg($quality = 100)
    {
        static::validateImageType(IMAGETYPE_JPEG, true);

        header('Content-Type: ' . image_type_to_mime_type(IMAGETYPE_JPEG));
        imagejpeg($this->_im, null, $quality);
    }

    /**
     * 以png类型输出图像数据
     * @param integer $quality 图像质量，取值为0-9
     * @param integer $filters PNG图像过滤器，取值参考imagepng函数
     */
    public function outputAsPng($quality = 9, $filters = null)
    {
        static::validateImageType(IMAGETYPE_PNG, true);

        header('Content-Type: ' . image_type_to_mime_type(IMAGETYPE_PNG));
        imagepng($this->_im, null, $quality, $filters);
    }
    
    /**
     * @param int $foreground
     * 以wbmp类型输出图像数据
     */
    public function outputAsWbmp($foreground  = null)
    {
        static::validateImageType(IMAGETYPE_WBMP, true);

        header('Content-Type: ' . image_type_to_mime_type(IMAGETYPE_WBMP));
        imagewbmp($this->_im, null, $foreground);
    }
    
    /**
     * @param int $foreground
     * 以xbm类型输出图像数据
     */
    public function outputAsXbm($foreground  = null)
    {
        static::validateImageType(IMAGETYPE_XBM, true);

        header('Content-Type: ' . image_type_to_mime_type(IMAGETYPE_XBM));
        imagexbm($this->_im, null, $foreground);
    }

    /**
     * 以Webp类型输出图像数据
     */
    public function outputAsWebp()
    {
        static::validateImageType(IMAGETYPE_WEBP, true);

        header('Content-Type: ' . image_type_to_mime_type(IMAGETYPE_XBM));
        imagewebp($this->_im);
    }
    
    public function outputRaw()
    {
        ob_start();
        $function = self::$_outputFuntions[$this->_imageType];
        call_user_func($function, $this->_im);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
    
    public function outputRawAsGif()
    {
        static::validateImageType(IMAGETYPE_GIF, true);

        ob_start();
        imagegif($this->_im);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    /**
     * @param int $quality
     * @return string
     */
    public function outputRawAsJpeg($quality = 100)
    {
        static::validateImageType(IMAGETYPE_JPEG, true);

        ob_start();
        imagejpeg($this->_im, null, $quality);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    /**
     * @param int $quality
     * @param int $filters
     * @return string
     */
    public function outputRawAsPng($quality = 9, $filters = null)
    {
        static::validateImageType(IMAGETYPE_PNG, true);

        ob_start();
        imagepng($this->_im, null, $quality, $filters);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
    
    public function outputRawAsWbmp($foreground  = null)
    {
        static::validateImageType(IMAGETYPE_WBMP, true);

        ob_start();
        imagewbmp($this->_im, null, $foreground);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
    
    public function outputRawAsXbm($foreground  = null)
    {
        static::validateImageType(IMAGETYPE_XBM, true);

        ob_start();
        imagexbm($this->_im, null, $foreground);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function outputRawAsWebp()
    {
        static::validateImageType(IMAGETYPE_WEBP, true);

        ob_start();
        imagewebp($this->_im);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public static function validateImageType($type, $throw = false)
    {
        if (imagetypes() & $type)
            return true;
        elseif ($throw)
            throw new ImageException('Not support this type');
        else
            return false;
    }
    
    /**
     * 等比例绽放图像
     * @param float $scale 绽放值，取值为0-1
     * @return $this
     */
    public function scale($scale)
    {
        $width = $this->width() * $scale;
        $height = $this->height() * $scale;
        $this->resize($width, $height);
        return $this;
    }
    
    /**
     * 根据设定高度等比例绽放图像
     * @param integer $height 图像高度
     * @param bool $force
     * @return $this
     */
    public function resizeToHeight($height, $force = false)
    {
        if (!$force && $height >= $this->height()) return $this;
        
        $ratio = $height / $this->height();
        $width = $this->width() * $ratio;
        $this->resize($width, $height);
        return $this;
    }

	/**
     * 根据设定宽度等比例绽放图像
     * @param integer $width 图像宽度
     * @param bool $force
     * @return $this
     */
    public function resizeToWidth($width, $force = false)
    {
        if (!$force && $width >= $this->width()) return $this;
        
        $ratio = $width / $this->width();
        $height = $this->height() * $ratio;
        $this->resize($width, $height);
        return $this;
    }
    
    /**
     * 改变图像大小
     * @param integer $width 图像宽度
     * @param integer $height 图像高度
     * @param int $mode default 3 (IMG_BILINEAR_FIXED) php version is require > 5.5.0
     * @return $this
     */
    public function resize($width, $height, $mode = 3)
    {
        if (function_exists('imagescale')) {
            $this->_im = imagescale($this->_im, $width, $height, $mode);
        }
        else {
            $image = imagecreatetruecolor($width, $height);
            static::saveAlpha($this->_im);
            static::saveAlpha($image);
            imagecopyresampled($image, $this->_im, 0, 0, 0, 0, $width, $height, $this->width(), $this->height());
            $this->_im = $image;
        }

        return $this;
    }
    
    /**
     * 裁剪图像
     * @param integer $width 图像宽度
     * @param integer $height 图像高度
     * @param integer $position
     * @return $this
     */
    public function cropByPosition($width, $height, $position = self::POS_CENTER_MIDDLE)
    {
        $pos = $this->getCropPosition($width, $height, $position);

        $this->crop($width, $height, $pos['x'], $pos['y']);

        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     * @param int $position
     * @return array keys 'x' and 'y'
     */
    public function getCropPosition($width, $height, $position)
    {
        $sourceWidth = $this->width();
        $sourceHeight = $this->height();
        if ($width > $sourceWidth) $width = $sourceWidth;
        if ($height > $sourceHeight) $height = $sourceHeight;

        switch ($position)
        {
            case self::POS_TOP_LEFT:
                $dstX = $dstY = 0;
                break;
            case self::POS_TOP_CENTER:
                $dstX = ($sourceWidth - $width) / 2;
                $dstY = 0;
                break;
            case self::POS_TOP_RIGHT:
                $dstX = $sourceWidth - $width;
                $dstY = 0;
                break;
            case self::POS_BOTTOM_LEFT:
                $dstX = 0;
                $dstY = $sourceHeight - $height;
                break;
            case self::POS_BOTTOM_CENTER:
                $dstX = ($sourceWidth - $width) / 2;
                $dstY = $sourceHeight - $height;
                break;
            case self::POS_BOTTOM_RIGHT:
                $dstX = $sourceWidth - $width;
                $dstY = $sourceHeight - $height;
                break;
            case self::POS_LEFT_MIDDLE:
                $dstX = 0;
                $dstY = ($sourceHeight - $height) / 2;
                break;
            case self::POS_RIGHT_MIDDLE:
                $dstX = $sourceWidth - $width;
                $dstY = ($sourceHeight - $height) / 2;
                break;
            default:
                $dstX = ($sourceWidth - $width) / 2;
                $dstY = ($sourceHeight - $height) / 2;
                break;
        }

        return ['x' => $dstX, 'y' => $dstY];
    }
    
    public function crop($width, $height, $x = 0, $y = 0)
    {
        $sourceWidth = $this->width();
        $sourceHeight = $this->height();

        if (($x + $width) > $sourceWidth)
            $width = $sourceWidth - $x;

        if (($y + $height) > $sourceHeight)
            $height = $sourceHeight - $y;

        if (function_exists('imagecrop')) {
            $rect = ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height];
            $this->_im = imagecrop($this->_im, $rect);
        }
        else {
            $image = imagecreatetruecolor($width, $height);
            imagecopyresampled($image, $this->_im, 0, 0, $x, $y, $width, $height, $width, $height);
            $this->_im = $image;
        }

        return $this;
    }

    /**
     * 顺时针旋转图片
     * @param float $degree 取值为0-360
     * @param int $bgdColor 空白区域填充颜色
     * @param int $ignoreTransparent
     * @return $this
     */
    public function rotate($degree = 90.0, $bgdColor, $ignoreTransparent = 0)
    {
        $degree = (int)$degree;
        $this->_im = imagerotate($this->_im, $degree, $bgdColor, $ignoreTransparent);
        return $this;
    }
    
    /**
     * 将图像转换为灰度的
     * @return $this
     */
    public function gray()
    {
        imagefilter($this->_im, IMG_FILTER_GRAYSCALE);
        return $this;
    }
    
    /**
     * 将图像颜色反转
     * @return $this
     */
    public function negate()
    {
        imagefilter($this->_im, IMG_FILTER_NEGATE);
        return $this;
    }
    
    /**
     * 调整图像亮度
     * @param integer $bright 亮度值
     * @return $this
     */
    public function brightness($bright)
    {
        $bright = (int)$bright;
        ($bright > 0) && imagefilter($this->_im, IMG_FILTER_BRIGHTNESS, $bright);
        return $this;
    }
    
    /**
     * 调整图像对比度
     * @param integer $contrast 对比度值
     * @return $this
     */
    public function contrast($contrast)
    {
        $contrast = (int)$contrast;
        ($contrast > 0) && imagefilter($this->_im, IMG_FILTER_CONTRAST, $contrast);
        return $this;
    }
    
    /**
     * 将图像浮雕化
     * @return $this
     */
    public function emboss()
    {
        imagefilter($this->_im, IMG_FILTER_EMBOSS,0);
        return $this;
    }
    
    /**
     * 让图像柔滑
     * @param integer $smooth 柔滑度值
     * @return $this
     */
    public function smooth($smooth)
    {
        $smooth = (int)$smooth;
        ($smooth > 0) && imagefilter($this->_im, IMG_FILTER_SMOOTH, $smooth);
        return $this;
    }

    /**
     * 将图像使用高斯模糊
     * @return $this
     */
    public function gaussianBlur()
    {
        imagefilter($this->_im, IMG_FILTER_GAUSSIAN_BLUR);
        return $this;
    }
    
    public function mosaic($x1, $y1, $x2, $y2, $deep)
    {
        for ($x=$x1; $x < $x2; $x+=$deep) {
            for ($y=$y1; $y<$y2; $y+=$deep) {
                $color = imagecolorat($this->_im, $x + round($deep / 2), $y + round($deep / 2));
                imagefilledrectangle($this->_im, $x, $y, $x + $deep, $y + $deep, $color);
            }
        }
        return $this;
    }

    public function flip($mode)
    {
        imageflip($this->_im, $mode);
        return $this;
    }

    public function border($color, $thick = 1, $alpha = 0)
    {
        if ($thick > 1)
            imagesetthickness($this->_im, $thick);

        if (is_array($color)) {
            imagesetstyle($this->_im, $color);
            $color = IMG_COLOR_STYLED;
        }
        else
            $color = static::colorAllocateAlpha($this->_im, $color, $alpha);

        $width = $this->width();
        $height = $this->height();
        $points = [
            0, 0,
            $width, 0,
            $width, $height,
            0, $height,
        ];
        imagepolygon($this->_im, $points, count($points) / 2, $color);
        return $this;
    }


    /**
     * @param $x1 int
     * @param $y1 int
     * @param $x2 int
     * @param $y2 int
     * @param $color int|array|resource
     * @param $thick int
     * @return $this
     */
    public function line($x1, $y1, $x2, $y2, $color, $thick = 1)
    {
        if ($thick > 0 && (is_int($color) || is_array($color)))
            imagesetthickness($this->_im, $thick);

        if (is_array($color)) {
            imagesetthickness($this->_im, $thick);
            imagesetstyle($this->_im, $color);
            $color = IMG_COLOR_STYLED;
        }
        elseif (is_resource($color)) {
            imagesetbrush($this->_im, $color);
            $color = IMG_COLOR_STYLEDBRUSHED;
        }

        imageline($this->_im, $x1, $y1, $x2, $y2, $color);
        return $this;
    }





    /**
     * 在图像上添加文字
     * @param string $text 添加的文字
     * @param string $font 字体文件路径
     * @param integer $size 文字大小
     * @param integer|array $position 文字添加位置
     * @param array|integer $color 颜色值
     * @param int $alpha
     * @param int $padding
     * @param int $angle
     * @throws ImageException
     * @return $this
     */
    public function textByPosition($text, $font, $size, $position = self::POS_BOTTOM_RIGHT, $color = [0, 0, 0], $alpha = 0, $padding = 5, $angle = 0)
    {
        if (is_int($position))
            $pos = $this->getTextPosition($text, $font, $size, $position, $padding);
        elseif (is_array($position))
            $pos = $position;
        else
            throw new ImageException('position error.');

        return $this->text($text, $font, $size, $pos[0], $pos[1], $color, $alpha, $angle);
    }

    public function text($text, $font, $size, $x, $y, $color = [0, 0, 0], $alpha = 0, $angle = 0)
    {
        $color = static::colorAllocateAlpha($this->_im, $color, $alpha);
        imagettftext($this->_im, $size, $angle, $x, $y, $color, $font, $text);

        return $this;
    }
    
    public function getTextPosition($text, $font, $size, $position = self::POS_BOTTOM_RIGHT, $padding = 5, $angle = 0)
    {
        if (is_array($position))
            return $position;
        
        if (@file_exists($font) && @is_readable($font))
            $points = imagettfbbox($size, $angle, $font, $text);
        else {
            $width = strlen($text) * 9;
            $height = 16;
            // 此处需要注意imagestring跟imagettftext的起始坐标意义是不一样的
            $points = [0, $height, $width, 0, $width, -$size, 0, -$size];
        }
        $imWidth = $this->width();
        $imHeight = $this->height();
        $textWidth = $points[2] - $points[0];
        $textHeight = $points[1] - $points[7];
        switch ($position)
        {
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
            case self::POS_BOTTOM_RIGHT:
                $x = $imWidth - $textWidth - $padding;
                $y = $imHeight - $points[1] - $padding;
                break;
            default:
                throw new ImageException('position is invalid.');
                break;
        }
    
        return [$x, $y];
    }

    /**
     * 将一个图像合并到画布上
     * @param string|resource|static $image 要合并的图像
     * @param int|array $position 合并位置
     * @param integer $pct 图像将根据 pct 来决定合并程度，其值范围从 0 到 100。
     * @throws ImageException
     * @return $this
     */
    public function merge($image, $position = self::POS_BOTTOM_RIGHT, $pct = 100)
    {
        if (is_resource($image))
            $src = $image;
        elseif ($image instanceof static)
            $src = $image->getImageHandle();
        else
            $src = static::createImage($image);
        
        if (!is_resource($src))
            throw new ImageException('图像数据错误');

        if (is_int($position))
            $pos = static::getMergePosition($position, $this->_im, $src);
        elseif (is_array($position))
            $pos = $position;
        else
            throw new ImageException('position error.');
        
        $width = imagesx($src);
        $height = imagesy($src);
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($this->_im, true);
        imagealphablending($image, true);
        
        imagecopyresampled($image, $this->_im, 0, 0, $pos[0], $pos[1], $width, $height, $width, $height);
        static::saveAlpha($src);
        imagecopy($image, $src, 0, 0, 0, 0, $width, $height);
        imagecopymerge($this->_im, $image, $pos[0], $pos[1], 0, 0, $width, $height, $pct);
        
        return $this;
    }
    
    public function getImageHandle()
    {
        return $this->_im;
    }
    
    public static function getMergePosition($position, $dst, $src, $padding = 0)
    {
        if (is_array($position))
            return $position;
        
        if (is_resource($src)) {
            $srcW = imagesx($src);
            $srcH = imagesy($src);
        }
        elseif ($src instanceof static) {
            $srcW = $src->width();
            $srcH = $src->height();
        }
        else
            return false;
        
        if (is_resource($dst)) {
            $dstW = imagesx($dst);
            $dstH = imagesy($dst);
        }
        elseif ($dst instanceof static) {
            $dstW = $dst->width();
            $dstH = $dst->height();
        }
        else
            return false;
        
        switch ($position)
        {
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
                throw new ImageException('position is required.');
                break;
        }
        
        return [$x, $y];
    }

    /**
     * @param bool $append
     * @param int $width
     * @param int $height
     * @param int $horizontal
     * @param int $vertical
     * @param string|array|int $color
     * @param int $alpha
     * @throws ImageException
     * @return $this
     */
	public function resizeCanvas($append, $width = 0, $height = 0, $color = '#FFFFFF', $alpha = 0, $horizontal = self::CANVAS_HORIZONTAL_BOTH, $vertical = self::CANVAS_VERTICAL_BOTTOM)
	{
	    $append = (bool)$append;
	    $width = (int)$width;
	    $height = (int)$height;
	    if (($width === 0 && $height === 0) || !$append && ($width === 0 || $height === 0))
	        throw new ImageException('width or height value is invalid.');
	
	    $imWidth = $this->width();
	    $imHeight = $this->height();
	    if (!$append && ($width < $imWidth || $height < $imHeight))
	        throw new ImageException('please call crop method first');

	    if ($append) {
	        $width += $imWidth;
	        $height += $imHeight;
	    }
	
	    switch ($horizontal)
	    {
	        case self::CANVAS_HORIZONTAL_BOTH:
	            $dstX = ($width - $imWidth) / 2;
	            break;
	        case self::CANVAS_HORIZONTAL_LEFT:
	            $dstX = $width - $imWidth;
	            break;
	        case self::CANVAS_HORIZONTAL_RIGHT:
	        default:
	            $dstX = 0;
	            break;
	    }
	
	    switch ($vertical)
	    {
	        case self::CANVAS_VERTICAL_BOTH:
	            $dstY = ($height - $imHeight) / 2;
	            break;
	        case self::CANVAS_VERTICAL_TOP:
	            $dstY = $height - $imHeight;
	            break;
	        case self::CANVAS_VERTICAL_BOTTOM:
	        default:
	            $dstY = 0;
	            break;
	    }

	    $im = imagecreatetruecolor($width, $height);
	    $white = imagecolorallocate($im, 255, 255, 255);
	    imagefill($im, 0, 0, $white);
	    imagealphablending($im, true);
	    $color = static::colorAllocateAlpha($im, $color, $alpha);
	    imagefill($im, 0, 0, $color);
	    imagecopymerge($im, $this->_im, $dstX, $dstY, 0, 0, $imWidth, $imHeight, 100);
	    $this->_im = $im;
	    $im = null;

        return $this;
	}

    public function applyFilter($className, $args = [])
    {
        if (class_exists($className)) {
            $object = new $className();
            return call_user_func([$object, 'apply'], $this, $this->_im, $this->_imageType, $args);
        }
        else
            throw new \BadMethodCallException($className . ' is not fount.');
    }


    public static function saveAlpha($im)
    {
        imagealphablending($im, false);
        imagesavealpha($im, true);
    }

    public static function isAnimatedGif($file)
    {
        if (@file_exists($file) && is_readable($file)) {
            $fp = fopen($file, 'rb');
            $data = fread($fp, 1024);
            fclose($fp);
        }
        else
            $data = $file;

        $p = chr(0x21).chr(0xff).chr(0x0b).chr(0x4e).chr(0x45).chr(0x54).chr(0x53).chr(0x43).chr(0x41).chr(0x50).chr(0x45).chr(0x32).chr(0x2e).chr(0x30);
        return (bool)preg_match("~${p}~", $data);
    }

    public static function getImageInfo($file)
    {
        $info = null;
        if (@file_exists($file) && @is_readable($file))
            $info = getimagesize($file);
        elseif (function_exists('getimagesizefromstring'))
            $info = getimagesizefromstring($file);

        return $info;
    }

    public static function getImageSize($file)
    {
        $info = static::getImageInfo($file);
        return array_slice($info, 0, 2);
    }

    public static function getImageMime($file)
    {
        $info = static::getImageInfo($file);
        return $info['mime'];
    }

    public static function getImageType($file)
    {
        $info = static::getImageInfo($file);
        return $info[2];
    }

    public static function getImageExtName($file, $includeDot = true)
    {
        $imageType = static::getImageType($file);

        $extension = null;
        if ($imageType != IMAGETYPE_UNKNOWN)
            $extension = image_type_to_extension($imageType, $includeDot);

        return $extension;
    }

    public static function framesCount($data)
    {
        if (@file_exists($data) && @is_readable($data))
            $data = file_get_contents($data);

        return substr_count($data, "\x00\x21\xF9\x04");
    }

    /**
     * @param $im resource image resource
     * @param $color int|array
     * @param int $alpha 取值0-127
     * @return int|string
     * @throws ImageException
     */
    public static function colorAllocateAlpha($im, $color, $alpha = 0)
    {
        if (is_int($color))
            return $color;
        elseif (is_array($color))
            return imagecolorallocatealpha($im, $color[0], $color[1], $color[2], $alpha);
        elseif ($color{0} == '#') {
            $color = substr($color, 1);
            $bg_dec = hexdec($color);
            return imagecolorallocatealpha($im,
                ($bg_dec & 0xFF0000) >> 16,
                ($bg_dec & 0x00FF00) >> 8,
                ($bg_dec & 0x0000FF),
                $alpha);
        }
        else
            throw new ImageException('color value is invalid.');
    }
    
    /**
     * 析构函数
     */
    public function __destruct()
    {
        is_resource($this->_im) && imagedestroy($this->_im);
    }
}

