<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Main application asset bundle.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/bootstrap.vertical-tabs.css',
        'css/fullcalendar.min.css',
        'css/bootstrap-select.min.css',
        'css/site.css?v=1.2',
    ];
    public $js = [
        'js/moment.min.js',
        'js/fullcalendar.min.js',
        'js/fullcalendar.ru.js',
        'js/bootstrap-select.min.js',
        'js/jquery.min.js',
        'js/jquery.fancybox.min.js',
        'vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js',
        'js/scripts.js?v=2',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
