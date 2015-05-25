<?php
/**
 * Created by PhpStorm.
 * User: chendong
 * Date: 15/5/14
 * Time: 下午10:17
 */

namespace phplib\graphics;


class FilterBase
{
    /**
     * @var SimpleImage
     */
    protected $image;

    /**
     * @var resource
     */
    protected $imageHandle;

    /**
     * @var int
     */
    protected $imageType = IMAGETYPE_PNG;

    /**
     * @return string
     */
    public static function className()
    {
        return get_called_class();
    }

    protected function init(array $args)
    {
    }

    protected function run(array $args)
    {
        throw new \BadMethodCallException('run method is require override.');
    }

    /**
     * @param SimpleImage $simpleImage
     * @param $im
     * @param $imageType
     * @param array $args
     * @return bool
     */
    public function apply(SimpleImage $simpleImage, $im, $imageType, array $args)
    {
        $this->image = $simpleImage;
        $this->imageHandle = $im;
        $this->imageType = $imageType;

        $this->init($args);
        if ($this->beforeApply($args)) {
            $this->run($args);
            $this->afterApply($args);
            return true;
        }
        return false;
    }

    public function execute(array $args, $imageType = null)
    {
        $this->imageType = $imageType;

        $this->init($args);
        if ($this->beforeApply($args)) {
            $this->run($args);
            $this->afterApply($args);
            return true;
        }
        return false;
    }

    public function setSimpleImage(SimpleImage $image)
    {
        $this->image = $image;
        $this->imageHandle = $image->getImageHandle();
        return $this;
    }

    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param $args
     * @return bool
     */
    protected function beforeApply(array $args)
    {
        return true;
    }

    /**
     * @param $args
     */
    protected function afterApply(array $args)
    {
    }
}