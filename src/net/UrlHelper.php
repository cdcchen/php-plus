<?php
/**
 * Created by PhpStorm.
 * User: chendong
 * Date: 16/3/13
 * Time: 12:30
 */

namespace phpplus\net;


class UrlHelper
{
    public static function isUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}