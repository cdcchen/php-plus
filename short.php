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
function view()
{
    return app()->getView();
}

/**
 * Returns the database connection component.
 * @return \yii\db\Connection the database connection.
 */
function db()
{
    return app()->getDb();
}

/**
 * Returns the cache component.
 * @return \yii\caching\Cache the cache application component. Null if the component is not enabled.
 */
function cache()
{
    return app()->getCache();
}

/**
 * Returns the formatter component.
 * @return \yii\base\Formatter the formatter application component.
 */
function formatter()
{
    return app()->getFormatter();
}

/**
 * Returns the URL manager for this application.
 * @return \yii\web\UrlManager the URL manager for this application.
 */
function urlManager()
{
    return app()->getUrlManager();
}

/**
 * Returns the internationalization (i18n) component
 * @return \yii\i18n\I18N the internationalization application component.
 */
function i18n()
{
    return app()->getI18n();
}

/**
 * Returns the mailer component.
 * @return \yii\mail\MailerInterface the mailer application component.
 */
function mailer()
{
    return app()->getMailer();
}

/**
 * Returns the auth manager for this application.
 * @return \yii\rbac\ManagerInterface the auth manager application component.
 * Null is returned if auth manager is not configured.
 */
function authManager()
{
    return app()->getAuthManager();
}

/**
 * Returns the asset manager.
 * @return \yii\web\AssetManager the asset manager application component.
 */
function assetManager()
{
    return app()->getAssetManager();
}

/**
 * Returns the security component.
 * @return \yii\base\Security the security application component.
 */
function security()
{
    return app()->getSecurity();
}

/**
 * Encodes special characters into HTML entities.
 * The [[\yii\base\Application::charset|application charset]] will be used for encoding.
 * @param string $content the content to be encoded
 * @param boolean $doubleEncode whether to encode HTML entities in `$content`. If false,
 * HTML entities in `$content` will not be further encoded.
 * @return string the encoded content
 * @see decode()
 * @see http://www.php.net/manual/en/function.htmlspecialchars.php
 */
function hencode($content, $doubleEncode = true)
{
    return \yii\helpers\Html::encode($content, $doubleEncode);
}

/**
 * Generates an image tag.
 * @param array|string $src the image URL. This parameter will be processed by [[Url::to()]].
 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
 * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
 * If a value is null, the corresponding attribute will not be rendered.
 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
 * @return string the generated image tag
 */
function img($src, $options = [])
{
    return \yii\helpers\Html::img($src, $options);
}

/**
 * Generates a hyperlink tag.
 * @param string $text link body. It will NOT be HTML-encoded. Therefore you can pass in HTML code
 * such as an image tag. If this is coming from end users, you should consider [[encode()]]
 * it to prevent XSS attacks.
 * @param array|string|null $url the URL for the hyperlink tag. This parameter will be processed by [[Url::to()]]
 * and will be used for the "href" attribute of the tag. If this parameter is null, the "href" attribute
 * will not be generated.
 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
 * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
 * If a value is null, the corresponding attribute will not be rendered.
 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
 * @return string the generated hyperlink
 * @see \yii\helpers\Url::to()
 */
function a($text, $url = null, $options = [])
{
    return \yii\helpers\Html::a($text, $url, $options);
}


function param($name, $defaultValue = null)
{
    return isset(app()->params[$name]) ? app()->params[$name] : $defaultValue;
}