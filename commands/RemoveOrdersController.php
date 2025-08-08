<?php

namespace app\commands;

use app\models\Iiko;
use app\models\Terminals;
use yii\console\Controller;

class RemoveOrdersController extends Controller
{
    public $rmsLogin;
    public $organizationId;

    public $startDate;

    public $endDate;

    public $terminalId;

    public $removeType;

    public function options($actionID)
    {
        return ['organizationId', 'rmsLogin', 'startDate', 'endDate', 'terminalId', 'removeType'];
    }


    public function actionRemove()
    {
//        echo '<pre>'; print_r($this->organizationId); echo '</pre>';
//        echo '<pre>'; print_r($this->rmsLogin); echo '</pre>';
//        echo '<pre>'; print_r($this->startDate); echo '</pre>';
//        echo '<pre>'; print_r($this->terminalId); echo '</pre>';

        if (!$this->organizationId || !$this->rmsLogin) {
            echo 'Organization ID and RMS login are required';
            return;
        }

        if (!$this->startDate) {
            echo 'Start date is required';
            return;
        }

        if (!$this->endDate) {
            echo 'End date is required';
            return;
        }

        if (!$this->terminalId) {
            echo 'Terminal ID is required';
            return;
        }

        if (!$this->removeType) {
            echo 'Remove type is required';
            return;
        }

        $iiko = new Iiko();
        $iiko->rmsAuth($this->rmsLogin);


        $data = $this->post('https://api-ru.iiko.services/api/1/reserve/available_restaurant_sections', json_encode([
            "terminalGroupIds" => [
                $this->terminalId
            ],
            "returnSchema" => true,
            "revision" => 0

        ]), [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $iiko->rmsToken
            ]
        ]);

        $tablesResponse = json_decode($data, true);
//echo '<pre>'; print_r($tablesResponse); echo '</pre>';
        $tableIds = [];
        foreach ($tablesResponse['restaurantSections'][0]['tables'] as $table) {
            $tableIds[] = $table['id'];
        }

        $init = $this->post('https://api-ru.iiko.services/api/1/order/init_by_table', json_encode([
            "organizationId" => $this->organizationId,
            "terminalGroupId" => $this->terminalId,
            "tableIds" => $tableIds

        ]), [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $iiko->rmsToken
            ]
        ]);

        sleep(2);

//        $terminalId = $terminalModel['external_id'];

        $data = $iiko->pendingDeliveries($this->startDate, $this->endDate,
            [$this->terminalId], $this->organizationId);
echo 'removing '.count($data).' orders';
        foreach ($data as $item) {
            $iiko->cancelDeliveryOrder($item['id'], $this->organizationId, $this->removeType);
        }
    }


    private function post($url, $post = null, array $options = array())
    {
        $defaults = [
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_URL => $url,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_SSL_VERIFYHOST => 0, //unsafe, but the fastest solution for the error " SSL certificate problem, verify that the CA cert is OK"
            CURLOPT_SSL_VERIFYPEER => 0, //unsafe, but the fastest solution for the error " SSL certificate problem, verify that the CA cert is OK"
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/xml',
            ],
            CURLOPT_POSTFIELDS => $post
        ];
        $ch = curl_init();
        curl_setopt_array($ch, ($options + $defaults));
        if (!$result = curl_exec($ch)) {
            @trigger_error(curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }
}