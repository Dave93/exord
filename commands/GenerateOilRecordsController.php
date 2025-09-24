<?php

namespace app\commands;

use app\models\DailyStoreBalance;
use app\models\DailyStoreProduct;
use app\models\Orders;
use Yii;
use app\models\Iiko;
use app\models\Stores;
use app\models\User;
use yii\console\Controller;
use yii\helpers\ArrayHelper;
use app\models\OilInventory;


class GenerateOilRecordsController extends Controller
{
    public $orderId;

    private $tgBotToken = '2015516888:AAHcuE2OK2mVMKgnMCaI5M-jHfKybc_GY-Y';

    public function options($actionID)
    {
        return [];
    }

    public function actionCheck() {
        $users = User::find()
            ->where(['not', ['oil_tg_id' => null]])
            ->andWhere(['!=', 'oil_tg_id', ''])
            ->all();
        $arStoreIds = [];
        $arTgIdByStoreId = [];
        foreach ($users as $user) {
            $oil_tg_id = $user->oil_tg_id;
            $arStoreIds[] = $user->store_id;
            $arTgIdByStoreId[$user->store_id] = $user->oil_tg_id;
        }
        $arStoreIds = array_unique($arStoreIds);
        $arStores = Stores::find()->where(['id' => $arStoreIds])->all();
        echo '<pre>'; print_r($arStores); echo '</pre>'; exit;
        foreach ($arStores as $store) {
            $oil_tg_id = $arTgIdByStoreId[$store->id];
            $store_id = $store->id;
            $startDate = date('Y-m-d').' 10:00:00';
            $endDate = date('Y-m-d', strtotime('+1 day')).' 04:00:00';
            $currentHour = date('H');
            if ($currentHour <= 10) {
                $startDate = date('Y-m-d', strtotime('-1 day')).' 10:00:00';
                $endDate = date('Y-m-d').' 04:00:00';
            }

            $oilRecords = OilInventory::find()
                ->where(['store_id' => $store_id])
                ->andWhere(['>=', 'created_at', $startDate])
                ->andWhere(['<=', 'created_at', $endDate])
                ->limit(1)
                ->orderBy(['created_at' => SORT_DESC])
                ->one();
            if (!$oilRecords) {
                $lastOilRecord = OilInventory::find()
                    ->where(['store_id' => $store_id])
                    ->orderBy(['created_at' => SORT_DESC])
                    ->one();
                $prevClosingBalance = 0;
                if ($lastOilRecord) {
                    $prevClosingBalance = $lastOilRecord->closing_balance;
                }
                $oilRecords = new OilInventory();
                $oilRecords->store_id = $store_id;
                $oilRecords->created_at = date('Y-m-d H:i:s');
                $oilRecords->opening_balance = $prevClosingBalance;
                $oilRecords->income = 0;
                $oilRecords->return_amount = 0;
                $oilRecords->return_amount_kg = 0;
                $oilRecords->apparatus = 0;
                $oilRecords->new_oil = 0;
                $oilRecords->evaporation = 0;
                $oilRecords->closing_balance = $prevClosingBalance;
                $oilRecords->status = OilInventory::STATUS_NEW;
                $oilRecords->save();

                // Отправляем в ТГ сообщение о том, что нужно заполнить данные о масле с инлайн клавиатурой "Заполнить" на страницу
                $message = '<b>Нужно заполнить данные о масле в EXORD</b>';
                $this->sendMessageWithKeyboard($oil_tg_id, $message, $this->getKeyboard($oilRecords->id));
            } else if ($oilRecords->apparatus == 0) {
                $message = '<b>Дополните данные о масле в EXORD</b>';
                $this->sendMessageWithKeyboard($oil_tg_id, $message, $this->getKeyboard($oilRecords->id));
            }
        }
    }

    private function sendMessage($tg_id, $message) {
        $url = "https://api.telegram.org/bot{$this->tgBotToken}/sendMessage";
        $data = [
            'chat_id' => $tg_id,
            'text' => $message,
            'parse_mode' => 'HTML',
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_exec($ch);
        curl_close($ch);
        return true;
    }

    private function sendMessageWithKeyboard($tg_id, $message, $keyboard) {
        $url = "https://api.telegram.org/bot{$this->tgBotToken}/sendMessage";
        $data = [
            'chat_id' => $tg_id,
            'text' => $message,
            'parse_mode' => 'HTML',
        ];
        $data['reply_markup'] = json_encode([
            'inline_keyboard' => $keyboard,
        ]);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_exec($ch);
        curl_close($ch);
        return true;
    }

    private function getKeyboard($id) {
        return [
            [['text' => 'Заполнить', 'url' => 'https://les.iiko.uz/oil-inventory/update?id=' . $id]],
        ];
    }
}