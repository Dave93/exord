<?php

namespace app\commands;

use app\models\OrdersInitQueue;
use app\models\Terminals;
use yii\console\Controller;

class OrderInitController extends Controller
{

    private $rmsToken;
    private $tokens = [];

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


    public function rmsAuth($login = '9bc50b70-7b35-493b-a9a1-c151ce35e28c')
    {
        // Chopar  "d4cf3d80-317|dbce4a9c-32a3-44c5-90a9-a4ca7168f8ac"
        // Les "946888c6-87d|9bc50b70-7b35-493b-a9a1-c151ce35e28c"
        $orgId = [
            "946888c6-87d|9bc50b70-7b35-493b-a9a1-c151ce35e28c",
            "d4cf3d80-317|dbce4a9c-32a3-44c5-90a9-a4ca7168f8ac"
        ];

        foreach ($orgId as $item) {
            if (strpos($item, $login) !== false) {
                $login = explode('|', $item)[0];
            }
        }

        if (!empty($this->tokens[$login])) {
            $this->rmsToken = $this->tokens[$login];
            return;
        }

        $tokenData = $this->post('https://api-ru.iiko.services/api/1/access_token', json_encode([
            'apiLogin' => $login
        ]), [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ]
        ]);

        $this->rmsToken = json_decode($tokenData, true)['token'];
        $this->tokens[$login] = $this->rmsToken;
    }

    public function actionStart()
    {
        $terminals = Terminals::find()->asArray()->all();
        $grouped = ["9bc50b70-7b35-493b-a9a1-c151ce35e28c", "dbce4a9c-32a3-44c5-90a9-a4ca7168f8ac"];
        $terminalsByOrg = [];
        foreach ($terminals as $terminal) {
            $terminalsByOrg[$terminal['organization_id']][] = $terminal['external_id'];
        }


        foreach ($grouped as $key => $value) {
            $this->rmsAuth($value);
            $terminalId = $terminalsByOrg[$value][0];
            $latestTerminal = OrdersInitQueue::find()->where(['org_id' => $value])->orderBy([
                'id' => SORT_DESC
            ])->one();
            if ($latestTerminal) {

                $corellationId = $latestTerminal->corellation_id;

                $commandStatus = $this->post('https://api-ru.iiko.services/api/1/commands/status', json_encode([
                    "organizationId" => $value,
                    "correlationId" => $corellationId,

                ]), [
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $this->rmsToken
                    ]
                ]);

                $commandStatusResponse = json_decode($commandStatus, true);
                echo '<pre>';
                print_r($commandStatusResponse);
                echo '</pre>';
                if (isset($commandStatusResponse['error'])) {
                    continue;
                }
                if (isset($commandStatusResponse['state']) && in_array($commandStatusResponse['state'], ['InProgress', 'Error'])) {
                    continue;
                }

                $latestTerminalId = $latestTerminal->t_id;
                $latestTerminalKey = array_search($latestTerminalId, $terminalsByOrg[$value]);
                if ($latestTerminalKey !== false) {
                    if (isset($terminalsByOrg[$value][$latestTerminalKey + 1])) {
                        $terminalId = $terminalsByOrg[$value][$latestTerminalKey + 1];
                    } else {
                        $terminalId = $terminalsByOrg[$value][0];
                    }
                } else {
                    $terminalId = $terminalsByOrg[$value][0];
                }
            }

            $newOrderQueue = new OrdersInitQueue();
            $newOrderQueue->org_id = $value;
            $newOrderQueue->t_id = $terminalId;
//            $newOrderQueue->save();

            $data = $this->post('https://api-ru.iiko.services/api/1/reserve/available_restaurant_sections', json_encode([
                "terminalGroupIds" => [
                    $terminalId
                ],
                "returnSchema" => true,
                "revision" => 0

            ]), [
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->rmsToken
                ]
            ]);

            $tablesResponse = json_decode($data, true);
//echo '<pre>'; print_r($tablesResponse); echo '</pre>';
            $tableIds = [];
            foreach ($tablesResponse['restaurantSections'][0]['tables'] as $table) {
                $tableIds[] = $table['id'];
            }

            $init = $this->post('https://api-ru.iiko.services/api/1/order/init_by_table', json_encode([
                "organizationId" => $value,
                "terminalGroupId" => $terminalId,
                "tableIds" => $tableIds

            ]), [
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->rmsToken
                ]
            ]);
            $initResponse = json_decode($init, true);
//            echo '<pre>'; print_r($initResponse); echo '</pre>';
            $newOrderQueue->corellation_id = $initResponse['correlationId'];

            if (!$newOrderQueue->save()) {
                echo '<pre>';
                print_r($newOrderQueue->firstErrors);
                echo '</pre>';
            }

        }


    }
}