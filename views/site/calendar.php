<?php

/* @var $this yii\web\View */

use yii\helpers\Url;

$this->title = 'Панель управления';
$this->params['breadcrumbs'][] = $this->title;

$url = Url::to(['orders/list'], true);
$eventUrl = Url::to(['ajax/events'], true);
$js = <<<JS
    var detailUrl = '{$url}?date=';
    $('#calendar').fullCalendar({
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month'
        },
        lang: "ru",
        navLinks: true, // can click day/week names to navigate views
        editable: false,
        eventLimit: true, // allow "more" link when too many events
        events: {
            url: '{$eventUrl}',
            cache: true
          },
         dayClick: function(date, jsEvent, view) {
            window.location = detailUrl+date.format('YYYY-MM-DD');
            // console.log();
        }
    });
JS;
$this->registerJs($js);
?>
<div class="card">
    <div class="content">

        <div id='calendar'></div>
    </div>
</div>
