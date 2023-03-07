<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class PanelAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'https://fonts.googleapis.com/css?family=Open+Sans:400,600',
        'assets/panel/css/animate.min.css',
        'assets/panel/css/light-bootstrap-dashboard.css?v=1.4.0',
        'font-awesome/css/font-awesome.min.css',
        'assets/panel/css/pe-icon-7-stroke.css',
        'assets/panel/css/lightbox.css',
        'css/bootstrap.vertical-tabs.css',
        'css/fullcalendar.min.css',
        'css/bootstrap-select.min.css',
        'assets/panel/css/demo.css?v=1.0.11',
    ];
    public $js = [
        'js/moment.min.js',
        'js/fullcalendar.min.js',
        'js/fullcalendar.ru.js',
        'js/bootstrap-select.min.js',
        'js/chartjs.min.js',
        'js/push.min.js',
        'assets/panel/js/light-bootstrap-dashboard.js?v=1.4.0',
        'vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js',
        'js/scripts.js?v=1.0.13',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];

    public $jsOptions = [
        'position' => \yii\web\View::POS_END
    ];
}
