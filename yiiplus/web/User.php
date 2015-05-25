<?php
/**
 * Created by PhpStorm.
 * User: chendong
 * Date: 15/3/28
 * Time: 下午7:42
 */

namespace yiiplus\web;

use Yii;
use yii\base\InvalidConfigException;

/**
 * Class User
 * @package yiiplus\web
 */
class User extends \yii\web\User
{
    public $userConfig = 'userConfig';

    public function getConfig($key, $defaultValue = null)
    {
        if (Yii::$app->has($this->userConfig))
            return Yii::$app->get($this->userConfig)->get($this->id, $key, $defaultValue);
        else
            throw new InvalidConfigException('userConfig component required');
    }

    public function setConfig($key, $value)
    {
        if (Yii::$app->has($this->userConfig))
            return Yii::$app->get($this->userConfig)->set($this->id, $key, $value);
        else
            throw new InvalidConfigException('userConfig component required');
    }
}