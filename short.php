<?php
/**
 * @link https://github.com/cdcchen/yii2plus
 * @copyright Copyright (c) 2014 24beta.com
 * @license https://github.com/cdcchen/yii2plus/LICENSE.md
 */

/**
 * Returns the app object.
 * @return \yii\console\Application|\yii\web\Application the application instance
 */
function app()
{
    return Yii::$app;
}

/**
 * Returns the request object.
 * @return \yii\web\Request the application instance
 */
function request()
{
    return app()->getRequest();
}

/**
 * Returns the response object.
 * @return \yii\web\Response the application instance
 */
function response()
{
    return app()->getResponse();
}

/**
 * Returns the session object.
 * @return \yii\web\Session the application instance
 */
function session()
{
    return app()->getSession();
}

/**
 * Returns the user object.
 * @return \yii\web\User the application instance
 */
function user()
{
    return app()->getUser();
}

/**
 * Returns the view object.
 * @return View|\yii\web\View the view application component that is used to render various view files.
 */
public function view()
{
    return app()->getView();
}