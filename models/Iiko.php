<?php

namespace app\models;

use app\commands\OrderInitController;
use SimpleXMLElement;
use Yii;
use yii\base\Model;
use yii\db\mssql\PDO;
use yii\helpers\Json;

class Iiko extends Model
{
    private $baseUrl;
    private $token;
    private $login;
    private $password;

    public $rmsToken;

    /**
     * Iiko constructor.
     */
    public function __construct()
    {
        $this->baseUrl = Settings::getValue("iiko-server");
        $this->login = Settings::getValue("iiko-login");
        $this->password = Settings::getValue("iiko-password");
        $this->token = Settings::getValue("iiko-token");
    }

    public function auth()
    {
        Yii::info("Авторизация в iiko: baseUrl={$this->baseUrl}, login={$this->login}", 'iiko');

        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $hash = sha1($this->password);
        $authUrl = "{$this->baseUrl}auth?login={$this->login}&pass={$hash}";

        $token = @file_get_contents($authUrl, false, stream_context_create($arrContextOptions));

        if (!$token) {
            $error = error_get_last();
            Yii::error("Ошибка авторизации в iiko: " . ($error['message'] ?? 'пустой ответ'), 'iiko');
            Yii::error("URL авторизации: {$this->baseUrl}auth?login={$this->login}", 'iiko');
            return false;
        }

        // Проверяем, не вернулась ли ошибка в виде текста
        if (strpos($token, 'error') !== false || strpos($token, 'Error') !== false || strlen($token) > 100) {
            Yii::error("Возможная ошибка авторизации iiko, ответ: " . substr($token, 0, 200), 'iiko');
        }

        $this->token = $token;
        Settings::setValue("iiko-token", $this->token);
        Yii::info("Авторизация в iiko успешна, токен получен (длина: " . strlen($token) . ")", 'iiko');
        return true;
    }


    public function rmsAuth($login = '946888c6-87d')
    {
//        echo '<pre>'; print_r($login); echo '</pre>';
        $tokenData = $this->post('https://api-ru.iiko.services/api/1/access_token', json_encode([
            'apiLogin' => $login
        ]), [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ]
        ]);

        $this->rmsToken = json_decode($tokenData, true)['token'];
    }

    public function getReport($store)
    {
        $data = [];
        $this->auth();
        $date = date("Y-m-d\TH:i:s");
        $url = "{$this->baseUrl}v2/reports/balance/stores?timestamp={$date}&key={$this->token}&store={$store}";
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $response = file_get_contents($url, false, stream_context_create($arrContextOptions));
        if (!empty($response)) {
            $data = Json::decode($response, true);
        }

        return $data;
    }

    private function getData($url)
    {
        $fullUrl = "{$this->baseUrl}{$url}?key={$this->token}";
        Yii::info("Запрос к iiko: {$this->baseUrl}{$url}", 'iiko');

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_TIMEOUT => 1800,         // 30 минут макс
            CURLOPT_CONNECTTIMEOUT => 60,    // 60 сек на подключение
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/xml',
                'Expect:',
            ],
        ]);

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $downloadSize = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        curl_close($ch);

        Yii::info("Ответ iiko: код={$httpCode}, размер={$downloadSize} байт, время={$totalTime} сек", 'iiko');

        if ($errno) {
            Yii::error("cURL ошибка [{$errno}]: {$error}", 'iiko');
            return [];
        }

        if (empty($data)) {
            Yii::warning("Пустой ответ от iiko для {$url}", 'iiko');
            return [];
        }

        return $this->xmlToArray($data);
    }

    public function departments()
    {
        $data = $this->getData("corporation/departments");

        if (empty($data['corporateItemDto'])) {
            Yii::warning("Нет данных departments от iiko", 'iiko');
            return true;
        }

        Departments::deleteAll();
        foreach ($data['corporateItemDto'] as $row) {
            $model = new Departments();
            $model->id = (string)($row['id'] ?? '');
            $model->parentId = (string)($row['parentId'] ?? '');
            $model->name = (string)($row['name'] ?? '');
            $model->type = (string)($row['type'] ?? '');
            $model->syncDate = date("Y-m-d H:i:s");
            if (!$model->save()) {
                return $model->firstErrors;
            }
        }
        return true;
    }

    public function stores()
    {
        $data = $this->getData("corporation/stores");

        if (empty($data['corporateItemDto'])) {
            Yii::warning("Нет данных stores от iiko", 'iiko');
            return true;
        }

        Stores::deleteAll();
        foreach ($data['corporateItemDto'] as $row) {
            $model = new Stores();
            $model->id = (string)($row['id'] ?? '');
            $model->parentId = (string)($row['parentId'] ?? '');
            $model->name = (string)($row['name'] ?? '');
            $model->type = (string)($row['type'] ?? '');
            $model->syncDate = date("Y-m-d H:i:s");
            if (!$model->save()) {
                return $model->firstErrors;
            }
        }
        return true;
    }

    public function groups()
    {
        $gList = [];
        $data = $this->getData("corporation/groups");

        if (empty($data['groupDto'])) {
            Yii::warning("Нет данных groups от iiko", 'iiko');
            return true;
        }

        foreach ($data['groupDto'] as $row) {
            $id = (string)($row['id'] ?? '');
            if (empty($id)) continue;
            $gList[] = $id;
            $model = Groups::findOne($id);
            if ($model == null) {
                $model = new Groups();
                $model->id = $id;
            }
            $model->name = (string)($row['name'] ?? '');
            $model->departmentId = (string)($row['departmentId'] ?? '');
            $model->syncDate = date("Y-m-d H:i:s");
            if (!$model->save()) {
                return $model->firstErrors;
            }
        }
        if (!empty($gList)) {
            Groups::deleteAll(['not in', 'id', $gList]);
        }
        return true;
    }

    public function products()
    {
        $list = [];
        $data = $this->getData("products");

        if (empty($data['productDto'])) {
            Yii::warning("Нет данных products от iiko", 'iiko');
            return true;
        }

        foreach ($data['productDto'] as $row) {
            $id = (string)($row['id'] ?? '');
            if (empty($id)) continue;

            $type = (string)($row['productType'] ?? '');
            if (!in_array($type, ['GOODS', 'PREPARED', '']))
                continue;

            $model = Products::findOne($id);
            $list[] = $id;

            if ($model == null) {
                $model = new Products();
                $model->id = $id;
            }
            $model->parentId = (string)($row['parentId'] ?? '0');
            $model->name = trim((string)($row['name'] ?? ''));
            $model->num = $row['num'] ?? '';
            $model->code = (int)($row['code'] ?? 0);
            $model->productType = $type;
            $model->cookingPlaceType = (string)($row['cookingPlaceType'] ?? '');
            $model->mainUnit = (string)($row['mainUnit'] ?? '');
            $model->syncDate = date("Y-m-d H:i:s");
            if (!$model->save()) {
                return $model->firstErrors;
            }
        }
        if (!empty($list)) {
            Products::deleteAll(['not in', 'id', $list]);
        }
        return true;
    }

    private function clearGroups()
    {

    }

    public function stock()
    {
        $db = Yii::$app->db;
        $start = date("01.m.Y");
        $end = date("t.m.Y");
        $url = "{$this->baseUrl}reports/olap?key={$this->token}&report=TRANSACTIONS&from=01.01.2018&to={$end}&groupRow=Product.Name&groupRow=Product.Type&groupRow=Product.MeasureUnit&groupRow=Product.Id&agr=Amount";
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $response = file_get_contents($url, false, stream_context_create($arrContextOptions));

        $data = simplexml_load_string($response);

        $sql = "update products set inStock=:s where id=:id";
        foreach ($data->r as $row) {
            $r = $this->simpleXmlToArray($row);
            if (empty($r['Product.Id']) || $r['Product.Type'] != "GOODS")
                continue;

            $db->createCommand($sql)
                ->bindValue(":s", round($r['Amount'], 2), PDO::PARAM_STR)
                ->bindValue(":id", $r['Product.Id'], PDO::PARAM_STR)
                ->execute();
        }
    }

    private function simpleXmlToArray($xmlObject)
    {
        $array = [];
        foreach ($xmlObject->children() as $node) {
            $array[$node->getName()] = is_array($node) ? simplexml_to_array($node) : (string)$node;
        }
        return $array;
    }

    public function suppliers()
    {
        $list = [];
        $data = $this->getData("suppliers");

        if (empty($data['employee'])) {
            Yii::warning("Нет данных suppliers от iiko", 'iiko');
            return true;
        }

        foreach ($data['employee'] as $row) {
            $id = (string)($row['id'] ?? '');
            if (empty($id)) continue;
            $list[] = $id;
            $model = Suppliers::findOne($id);
            if ($model == null) {
                $model = new Suppliers();
                $model->id = $id;
            }
            $model->name = (string)($row['name'] ?? '');
            $model->deleted = ((string)($row['deleted'] ?? '') == "true") ? 1 : 0;
            $model->supplier = ((string)($row['supplier'] ?? '') == "true") ? 1 : 0;
            $model->employee = ((string)($row['employee'] ?? '') == "true") ? 1 : 0;
            $model->syncDate = date("Y-m-d H:i:s");
            if (!$model->save()) {
                return $model->firstErrors;
            }
        }
        if (!empty($list)) {
            Suppliers::deleteAll(['not in', 'id', $list]);
        }
        return true;
    }

    public function incoming($start, $end, $supplier)
    {
        $url = $this->baseUrl . 'documents/export/incomingInvoice?key=' . $this->token . '&from=' . $start . '&to=' . $end . '&supplierId=' . $supplier;
        $r = file_get_contents($url);
        $data = $this->xmlToArray($r);
        $sql = "insert into docs(documentId,supplierId,productId,storeId,date,price,amount) values(:d,:s,:p,:st,:dt,:pr,:a) on duplicate key update price=values(price),amount=values(amount)";
        foreach ($data['document'] as $row) {
            $documentId = (string)$row['id'];
            $supplier = (string)$row['supplier'];
            $store = (string)$row['defaultStore'];
            $date = date("Y-m-d H:i:s", strtotime((string)$row['incomingDate']));
            $status = (string)$row['status'];
            if ($status != 'PROCESSED') {
                continue;
            }

            if (isset($row['items']['item'][0])) {
                foreach ($row['items']['item'] as $item) {
                    $amount = (double)$item['actualAmount'];
                    $price = (double)$item['price'];
                    $product = (string)$item['product'];
                    Yii::$app->db->createCommand($sql)
                        ->bindValue(":d", $documentId, PDO::PARAM_STR)
                        ->bindValue(":s", $supplier, PDO::PARAM_STR)
                        ->bindValue(":p", $product, PDO::PARAM_STR)
                        ->bindValue(":st", $store, PDO::PARAM_STR)
                        ->bindValue(":dt", $date, PDO::PARAM_STR)
                        ->bindValue(":pr", $price, PDO::PARAM_STR)
                        ->bindValue(":a", $amount, PDO::PARAM_STR)
                        ->execute();
                }
            } else {
                $item = $row['items']['item'];
                $amount = (double)$item['actualAmount'];
                $price = (double)$item['price'];
                $product = (string)$item['product'];
                Yii::$app->db->createCommand($sql)
                    ->bindValue(":d", $documentId, PDO::PARAM_STR)
                    ->bindValue(":s", $supplier, PDO::PARAM_STR)
                    ->bindValue(":p", $product, PDO::PARAM_STR)
                    ->bindValue(":st", $store, PDO::PARAM_STR)
                    ->bindValue(":dt", $date, PDO::PARAM_STR)
                    ->bindValue(":pr", $price, PDO::PARAM_STR)
                    ->bindValue(":a", $amount, PDO::PARAM_STR)
                    ->execute();
            }
        }
        return true;
    }

    public function outgoing($start, $end)
    {
        $url = $this->baseUrl . 'documents/export/outgoingInvoice?key=' . $this->token . '&from=' . $start . '&to=' . $end;
        $r = file_get_contents($url);
        $data = $this->xmlToArray($r);
        $sql = "insert into outdocs(documentId,supplierId,productId,amount,date) values(:d,:s,:p,:a,:dt) on duplicate key update amount=values(amount)";
        foreach ($data['document'] as $row) {
            $documentId = (string)$row['id'];
            $supplier = (string)$row['counteragentId'];
            $date = date("Y-m-d H:i:s", strtotime((string)$row['dateIncoming']));
            $status = (string)$row['status'];
            if ($status != 'PROCESSED') {
                continue;
            }
            if (isset($row['items']['item'][0])) {
                foreach ($row['items']['item'] as $item) {
                    $amount = (double)$item['amount'];
                    $product = (string)$item['productId'];
                    Yii::$app->db->createCommand($sql)
                        ->bindValue(":d", $documentId, PDO::PARAM_STR)
                        ->bindValue(":s", $supplier, PDO::PARAM_STR)
                        ->bindValue(":p", $product, PDO::PARAM_STR)
                        ->bindValue(":a", $amount, PDO::PARAM_STR)
                        ->bindValue(":dt", $date, PDO::PARAM_STR)
                        ->execute();
                }
            } else {
                $item = $row['items']['item'];
                $amount = (double)$item['amount'];
                $product = (string)$item['productId'];
                Yii::$app->db->createCommand($sql)
                    ->bindValue(":d", $documentId, PDO::PARAM_STR)
                    ->bindValue(":s", $supplier, PDO::PARAM_STR)
                    ->bindValue(":p", $product, PDO::PARAM_STR)
                    ->bindValue(":a", $amount, PDO::PARAM_STR)
                    ->bindValue(":dt", $date, PDO::PARAM_STR)
                    ->execute();
            }
        }
        return true;
    }

    public function incomingPrices($start, $end)
    {
        $suppliers = Yii::$app->db->createCommand('select id from suppliers')->queryColumn();
        foreach ($suppliers as $supplier) {
            $url = $this->baseUrl . 'documents/export/incomingInvoice?key=' . $this->token . '&from=' . $start . '&to=' . $end . '&supplierId=' . $supplier;
            $r = file_get_contents($url);
            $data = $this->xmlToArray($r);
            $sql = "update products set price=:p,priceSyncDate=:d where id=:id and priceSyncDate<:d and productType!='PREPARED'";
            foreach ($data['document'] as $row) {
                $status = (string)$row['status'];
                $date = (string)$row['incomingDate'];
                if ($status != 'PROCESSED') {
                    continue;
                }
                $date = date("Y-m-d H:i:s", strtotime($date));
                if (isset($row['items']['item'][0])) {
                    foreach ($row['items']['item'] as $item) {
                        $price = (double)$item['price'];
                        $product = (string)$item['product'];
                        $store = (string)$item['store'];
                        if ($store != 'aafd23ee-e90f-492d-b187-98a80ea1f568')
                            continue;
                        Yii::$app->db->createCommand($sql)
                            ->bindValue(":id", $product, PDO::PARAM_STR)
                            ->bindValue(":p", $price, PDO::PARAM_STR)
                            ->bindValue(":d", $date, PDO::PARAM_STR)
                            ->execute();
                    }
                } else {
                    $item = $row['items']['item'];
                    $price = (double)$item['price'];
                    $product = (string)$item['product'];
                    $store = (string)$item['store'];
                    if ($store == 'aafd23ee-e90f-492d-b187-98a80ea1f568')
                        Yii::$app->db->createCommand($sql)
                            ->bindValue(":id", $product, PDO::PARAM_STR)
                            ->bindValue(":p", $price, PDO::PARAM_STR)
                            ->bindValue(":d", $date, PDO::PARAM_STR)
                            ->execute();
                }
            }
        }
        return true;
    }

    /**
     * Расходной накладной
     * @param Orders $model
     * @return bool
     * @throws \yii\db\Exception
     */
    public function storeOutDoc($model)
    {
        $orderId = $model->id;
        Yii::info("=== НАЧАЛО storeOutDoc для заказа #{$orderId} ===", 'iiko');
        Yii::info("supplierId: {$model->supplierId}, storeId: {$model->storeId}", 'iiko');

        $sql = "select * from order_items oi where oi.orderId=:id and oi.storeQuantity>0";
        $items = Yii::$app->db->createCommand($sql)
            ->bindParam(":id", $model->id, PDO::PARAM_INT)
            ->queryAll();

        Yii::info("Найдено позиций с storeQuantity>0: " . count($items), 'iiko');

        if (empty($items)) {
            Yii::warning("Нет позиций для storeOutDoc (заказ #{$orderId})", 'iiko');
            return false;
        }

        $defaultStoreId = "";
        $itemsXml = "";
        $addedItemsCount = 0;
        $skippedItems = [];

        foreach ($items as $item) {
            $product = Products::findOne($item['productId']);
            if (empty($item['storeId']) || empty($item['productId']) || empty($item['storeQuantity']) || $product == null) {
                $skippedItems[] = [
                    'productId' => $item['productId'] ?? 'null',
                    'storeId' => $item['storeId'] ?? 'null',
                    'storeQuantity' => $item['storeQuantity'] ?? 'null',
                    'productFound' => $product !== null,
                    'reason' => 'пустое storeId/productId/storeQuantity или продукт не найден'
                ];
                continue;
            }
            $price = $product->price * (100 + $model->user->percentage) / 100;
            $sum = $price * $item['storeQuantity'];
            $defaultStoreId = $item['storeId'];
            $itemsXml .= "<item>
                            <productId>{$item['productId']}</productId>
                            <storeId>{$item['storeId']}</storeId>
                            <price>{$price}</price>
                            <amount>{$item['storeQuantity']}</amount>
                            <sum>{$sum}</sum>
                            <discountSum>0.00</discountSum>
                            <vatPercent>0.000000000</vatPercent>
                            <vatSum>0.00</vatSum>
                         </item>";
            $addedItemsCount++;
            Yii::info("Добавлена позиция: productId={$item['productId']}, storeQuantity={$item['storeQuantity']}, price={$price}", 'iiko');
        }

        Yii::info("Добавлено позиций в XML: {$addedItemsCount}", 'iiko');
        if (!empty($skippedItems)) {
            Yii::warning("Пропущено позиций: " . count($skippedItems) . " - " . json_encode($skippedItems, JSON_UNESCAPED_UNICODE), 'iiko');
        }

        if (empty($defaultStoreId) || empty($itemsXml)) {
            Yii::warning("defaultStoreId или itemsXml пустой - нечего отправлять (заказ #{$orderId})", 'iiko');
            return false;
        }

        $number = "out-{$model->id}";
        $date = date("Y-m-d\TH:i:s");
        $doc = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>
                    <document>
                        <documentNumber>{$number}</documentNumber>
                        <dateIncoming>{$date}</dateIncoming>
                        <useDefaultDocumentTime>true</useDefaultDocumentTime>
                        <defaultStoreId>{$defaultStoreId}</defaultStoreId>
                        <counteragentId>{$model->supplierId}</counteragentId>
                        <items>{$itemsXml}</items>
                    </document>";

        Yii::info("Сформирован XML: documentNumber={$number}, defaultStoreId={$defaultStoreId}, counteragentId={$model->supplierId}", 'iiko');

        if ($this->auth()) {
            $url = "{$this->baseUrl}documents/import/outgoingInvoice?key={$this->token}";
            Yii::info("Отправка в iiko: {$this->baseUrl}documents/import/outgoingInvoice", 'iiko');

            $result = $this->post($url, $doc);
            Yii::info("Ответ от iiko (raw): " . ($result ?: 'ПУСТОЙ'), 'iiko');

            if (empty($result)) {
                Yii::error("Пустой ответ от iiko (заказ #{$orderId})", 'iiko');
                return false;
            }

            try {
                $xml = new SimpleXMLElement($result);
                $valid = (string)$xml->valid;
                $warning = (string)$xml->warning;
                $errorDescription = isset($xml->errorDescription) ? (string)$xml->errorDescription : '';
                $documentNumber = isset($xml->documentNumber) ? (string)$xml->documentNumber : '';

                Yii::info("Ответ iiko: valid={$valid}, warning={$warning}, documentNumber={$documentNumber}", 'iiko');

                if (!empty($errorDescription)) {
                    Yii::error("Ошибка от iiko: {$errorDescription} (заказ #{$orderId})", 'iiko');
                }

                if ($valid == "true" && $warning == "false") {
                    $model->outgoingDocumentId = $documentNumber;
                    $model->save();
                    Yii::info("=== УСПЕХ storeOutDoc: документ создан, documentId={$documentNumber} (заказ #{$orderId}) ===", 'iiko');
                    return true;
                } else {
                    Yii::error("Документ НЕ создан: valid={$valid}, warning={$warning} (заказ #{$orderId})", 'iiko');
                }
            } catch (\Exception $e) {
                Yii::error("Исключение при парсинге ответа iiko: " . $e->getMessage() . " (заказ #{$orderId})", 'iiko');
                return $e->getMessage();
            }
        } else {
            Yii::error("Авторизация в iiko НЕ удалась (заказ #{$orderId})", 'iiko');
        }

        Yii::warning("=== КОНЕЦ storeOutDoc - документ НЕ создан (заказ #{$orderId}) ===", 'iiko');
        return false;
    }

    /**
     * Расходной накладной
     * @param Orders $model
     * @return bool
     * @throws \yii\db\Exception
     */
    public function supplierInDoc($model)
    {
        $sql = "select * from order_items oi where oi.orderId=:id and oi.supplierQuantity>0";
        $items = \Yii::$app->db->createCommand($sql)
            ->bindParam(":id", $model->id, PDO::PARAM_INT)
            ->queryAll();
        if (empty($items))
            return false;

        $i = 0;
        $itemsXml = "";
        $supplierId = "";
        foreach ($items as $item) {
            $i++;
            if (empty($item['price']) || empty($item['supplierId']) || empty($item['factOfficeQuantity'])) {
                continue;
            }
            $supplierId = $item['supplierId'];
            $price = $item['price'];
            $sum = $price * $item['factSupplierQuantity'];
            $itemsXml .= "<item>
                             <num>{$i}</num>
                             <actualAmount>{$item['factOfficeQuantity']}</actualAmount>
                             <product>{$item['productId']}</product>
                             <store>{$model->storeId}</store>
                             <price>{$price}</price>
                             <amount>{$item['factOfficeQuantity']}</amount>
                             <sum>{$sum}</sum>
                          </item>";
        }
        if (empty($supplierId) || empty($itemsXml))
            return false;
        $number = "sup-{$model->id}";
        $date = date("Y-m-d\TH:i:s");
        $doc = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>
                <document>
                    <documentNumber>{$number}</documentNumber>
                    <dateIncoming>{$date}</dateIncoming>
                    <useDefaultDocumentTime>true</useDefaultDocumentTime>
                    <defaultStore>{$model->storeId}</defaultStore>
                    <supplier>{$supplierId}</supplier>
                    <items>{$itemsXml}</items>
                </document>";
        if ($this->auth()) {
            $url = "{$this->baseUrl}documents/import/incomingInvoice?key={$this->token}";
            $result = $this->post($url, $doc);
            try {
                $xml = new SimpleXMLElement($result);
                if ((string)$xml->valid == "true" && (string)$xml->warning == "false") {
                    $model->incomingDocumentId = (string)$xml->documentNumber;
                    $model->save();
                    return true;
                }
            } catch (\Exception $e) {
                return print_r($e->getMessage());
            }
        }
        return false;
    }


    /**
     * Приходная накладная от поставщика
     * @param Orders $model
     * @return bool
     * @throws \yii\db\Exception
     */
    public function supplierInStockDoc($model)
    {
        $orderId = $model->id;
        Yii::info("=== НАЧАЛО supplierInStockDoc для заказа #{$orderId} ===", 'iiko');

        $sql = "select supplierId from order_items oi where oi.orderId=:id and oi.supplierQuantity>0 and oi.supplierId!='' group by oi.supplierId";
        $suppliers = Yii::$app->db->createCommand($sql)
            ->bindParam(":id", $model->id, PDO::PARAM_INT)
            ->queryColumn();

        Yii::info("Найдено поставщиков: " . count($suppliers) . " - " . json_encode($suppliers), 'iiko');

        if (empty($suppliers)) {
            Yii::warning("Нет поставщиков с supplierQuantity>0 (заказ #{$orderId})", 'iiko');
            return false;
        }

        $k = 0;
        foreach ($suppliers as $supplier) {
            if (empty($supplier)) {
                Yii::warning("Пропущен пустой supplierId", 'iiko');
                continue;
            }
            $k++;
            Yii::info("--- Обработка поставщика #{$k}: {$supplier} ---", 'iiko');

            $sql = "select * from order_items oi where oi.orderId=:id and oi.supplierId=:s and oi.supplierQuantity>0";
            $items = Yii::$app->db->createCommand($sql)
                ->bindParam(":id", $model->id, PDO::PARAM_INT)
                ->bindParam(":s", $supplier, PDO::PARAM_INT)
                ->queryAll();

            Yii::info("Найдено позиций для поставщика {$supplier}: " . count($items), 'iiko');

            if (empty($items)) {
                Yii::warning("Нет позиций для поставщика {$supplier} (заказ #{$orderId})", 'iiko');
                return false;
            }

            $i = 0;
            $itemsXml = "";
            $storeId = "";
            $supplierId = "";
            $addedItemsCount = 0;
            $skippedItems = [];

            foreach ($items as $item) {
                $i++;
                if (empty($item['price']) || empty($item['supplierId']) || empty($item['supplierQuantity'])) {
                    $skippedItems[] = [
                        'productId' => $item['productId'] ?? 'null',
                        'price' => $item['price'] ?? 'null',
                        'supplierId' => $item['supplierId'] ?? 'null',
                        'supplierQuantity' => $item['supplierQuantity'] ?? 'null',
                        'reason' => 'пустое price/supplierId/supplierQuantity'
                    ];
                    continue;
                }
                $storeId = $item['storeId'];
                $supplierId = $item['supplierId'];
                $price = $item['price'];
                $sum = $price * $item['supplierQuantity'];
                $itemsXml .= "<item>
                            <num>{$i}</num>
                            <actualAmount>{$item['supplierQuantity']}</actualAmount>
                            <product>{$item['productId']}</product>
                            <store>{$storeId}</store>
                            <price>{$price}</price>
                            <amount>{$item['supplierQuantity']}</amount>
                            <sum>{$sum}</sum>
                          </item>";
                $addedItemsCount++;
                Yii::info("Добавлена позиция: productId={$item['productId']}, supplierQuantity={$item['supplierQuantity']}, price={$price}", 'iiko');
            }

            Yii::info("Добавлено позиций в XML: {$addedItemsCount}", 'iiko');
            if (!empty($skippedItems)) {
                Yii::warning("Пропущено позиций: " . count($skippedItems) . " - " . json_encode($skippedItems, JSON_UNESCAPED_UNICODE), 'iiko');
            }

            if (empty($storeId) || empty($supplierId) || empty($itemsXml)) {
                Yii::warning("storeId/supplierId/itemsXml пустой - нечего отправлять (заказ #{$orderId})", 'iiko');
                return false;
            }

            $number = "sup-{$k}-{$model->id}";
            $date = date("Y-m-d\TH:i:s");
            $doc = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>
                <document>
                    <documentNumber>{$number}</documentNumber>
                    <dateIncoming>{$date}</dateIncoming>
                    <useDefaultDocumentTime>true</useDefaultDocumentTime>
                    <defaultStore>{$storeId}</defaultStore>
                    <supplier>{$supplierId}</supplier>
                    <items>{$itemsXml}</items>
                </document>";

            Yii::info("Сформирован XML: documentNumber={$number}, defaultStore={$storeId}, supplier={$supplierId}", 'iiko');

            if ($this->auth()) {
                $url = "{$this->baseUrl}documents/import/incomingInvoice?key={$this->token}";
                Yii::info("Отправка в iiko: {$this->baseUrl}documents/import/incomingInvoice", 'iiko');

                $result = $this->post($url, $doc);
                Yii::info("Ответ от iiko (raw): " . ($result ?: 'ПУСТОЙ'), 'iiko');

                if (empty($result)) {
                    Yii::error("Пустой ответ от iiko для поставщика {$supplier} (заказ #{$orderId})", 'iiko');
                    continue;
                }

                try {
                    $xml = new SimpleXMLElement($result);
                    $valid = (string)$xml->valid;
                    $warning = (string)$xml->warning;
                    $errorDescription = isset($xml->errorDescription) ? (string)$xml->errorDescription : '';

                    Yii::info("Ответ iiko: valid={$valid}, warning={$warning}", 'iiko');

                    if (!empty($errorDescription)) {
                        Yii::error("Ошибка от iiko: {$errorDescription} (заказ #{$orderId})", 'iiko');
                    }

                    if ($valid == "true" && $warning == "false") {
                        Yii::info("Приходная накладная создана успешно, вызываем supplierOutStockDoc...", 'iiko');
                        return $this->supplierOutStockDoc($model);
                    } else {
                        Yii::error("Документ НЕ создан: valid={$valid}, warning={$warning} (заказ #{$orderId})", 'iiko');
                    }
                } catch (\Exception $e) {
                    Yii::error("Исключение при парсинге ответа iiko: " . $e->getMessage() . " (заказ #{$orderId})", 'iiko');
                    return print_r($e->getMessage());
                }
            } else {
                Yii::error("Авторизация в iiko НЕ удалась (заказ #{$orderId})", 'iiko');
            }
        }

        Yii::warning("=== КОНЕЦ supplierInStockDoc - документ НЕ создан (заказ #{$orderId}) ===", 'iiko');
        return false;
    }

    public function pendingDeliveries($start, $end, $terminalIds = [], $orgKey)
    {
        $startDate = date('Y-m-d', strtotime($start)) . " 00:00:00.123";
        $endDate = date('Y-m-d', strtotime($end)) . " 23:59:59.123";
//        echo '<pre>'; print_r($startDate); echo '</pre>';

        $data = $this->post('https://api-ru.iiko.services/api/1/deliveries/by_delivery_date_and_source_key_and_filter', json_encode([
            "organizationIds" => [
                $orgKey
            ],
            "terminalGroupIds" => $terminalIds,
            "deliveryDateFrom" => $startDate,
            "deliveryDateTo" => $endDate,
            "rowsCount" => 200,
            "statuses" => [
                "Unconfirmed",
                "WaitCooking",
                "ReadyForCooking",
                "CookingStarted",
                "CookingCompleted",
                "Waiting",
                "OnWay",
                "Delivered",
//                "Closed",
//                "Cancelled"
            ],
            "hasProblem" => true
        ]), [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->rmsToken
            ]
        ]);
//echo '<pre>'; print_r(json_encode([
//        "organizationIds" => [
//            $orgKey
//        ],
//        "terminalGroupIds" => $terminalIds,
//        "deliveryDateFrom" => $startDate,
//        "deliveryDateTo" => $endDate,
//        "rowsCount" => 200,
//        "statuses" => [
//            "Unconfirmed",
//            "WaitCooking",
//            "ReadyForCooking",
//            "CookingStarted",
//            "CookingCompleted",
////            "Waiting",
////            "OnWay",
//            "Delivered",
////            "Closed",
////            "Cancelled"
//        ],
//    ])); echo '</pre>';
//         echo '<pre>'; print_r(json_decode($data, true)); echo '</pre>';
        $orders = [];
        $orderByOrganization = json_decode($data, true)['ordersByOrganizations'];
        if (!empty($orderByOrganization)) {
            $orders = $orderByOrganization[0]['orders'];
        }

        return array_filter($orders, function ($order) {
            return !$order['order']['isDeleted'];
        });
    }

    public function cancelDeliveryOrder($orderId = null, $organizationId = null, $removalTypeId = null)
    {

        $data = $this->post('https://api-ru.iiko.services/api/1/deliveries/cancel', json_encode([
            "organizationId" => $organizationId,
            "orderId" => $orderId,
//            "movedOrderId" => "28af7243-7f48-4210-86f7-7f7d9464a036",
            "removalTypeId" => $removalTypeId,
//            "userIdForWriteoff" => "a6034758-9dec-455e-9b47-12cb676b45d8"
        ]), [CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->rmsToken
        ]]);
        //Get status of command.
        sleep(2);
        $statusMessage = $this->post('https://api-ru.iiko.services/api/1/commands/status', json_encode([
            "organizationId" => $organizationId,
            "correlationId" => json_decode($data, true)['correlationId']
        ]), [CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->rmsToken
        ]]);

        file_put_contents(__DIR__ . '/data.txt', json_encode([
                "organizationId" => $organizationId,
                "orderId" => $orderId,
//            "movedOrderId" => "28af7243-7f48-4210-86f7-7f7d9464a036",
                "removalTypeId" => $removalTypeId,
//            "userIdForWriteoff" => "a6034758-9dec-455e-9b47-12cb676b45d8"
            ]) . "\n\n", FILE_APPEND);
        file_put_contents(__DIR__ . '/data.txt', $data . "\n\n" . $statusMessage . "\n\n", FILE_APPEND);
    }

    /**
     * Создание акта списания в iiko
     * @param ProductWriteoff $model
     * @param string|null $accountId ID статьи списания (опционально)
     * @return bool|string true при успехе, строка с ошибкой при неудаче
     */
    public function createWriteoffDoc($model)
    {
        Yii::info("=== НАЧАЛО createWriteoffDoc для списания #{$model->id} ===", 'iiko');
        Yii::info("store_id: {$model->store_id}, status: {$model->status}, comment: " . ($model->comment ?? 'нет'), 'iiko');

        if ($model->status !== ProductWriteoff::STATUS_APPROVED) {
            Yii::warning("Списание #{$model->id} не утверждено (status={$model->status})", 'iiko');
            return 'Списание должно быть утверждено перед отправкой в iiko';
        }

        // Получаем позиции списания с утвержденными количествами
        $items = $model->items;
        Yii::info("Найдено позиций в списании: " . count($items), 'iiko');

        if (empty($items)) {
            Yii::warning("Нет позиций для списания #{$model->id}", 'iiko');
            return 'Нет позиций для списания';
        }

        $itemsArray = [];
        $skippedItems = [];
        foreach ($items as $item) {
            if (empty($item->approved_count) || $item->approved_count <= 0) {
                $skippedItems[] = [
                    'product_id' => $item->product_id,
                    'approved_count' => $item->approved_count ?? 'null',
                    'reason' => 'approved_count пустой или <= 0'
                ];
                continue;
            }

            $product = Products::findOne($item->product_id);
            if ($product == null) {
                $skippedItems[] = [
                    'product_id' => $item->product_id,
                    'reason' => 'продукт не найден в БД'
                ];
                continue;
            }

            $itemsArray[] = [
                'productId' => $item->product_id,
                'amount' => (float)$item->approved_count
            ];
            Yii::info("Добавлена позиция: productId={$item->product_id}, amount={$item->approved_count}, name={$product->name}", 'iiko');
        }

        if (!empty($skippedItems)) {
            Yii::warning("Пропущено позиций: " . count($skippedItems) . " - " . json_encode($skippedItems, JSON_UNESCAPED_UNICODE), 'iiko');
        }

        if (empty($itemsArray)) {
            Yii::warning("Нет утвержденных позиций для списания #{$model->id} после фильтрации", 'iiko');
            return 'Нет утвержденных позиций для списания';
        }

        Yii::info("Итого позиций для отправки в iiko: " . count($itemsArray), 'iiko');

        // Формируем данные для запроса
        $data = [
            'dateIncoming' => $model->created_at ? date('Y-m-d\TH:i', strtotime($model->created_at)) : date('Y-m-d\TH:i'),
            'status' => 'PROCESSED',
            'comment' => $model->comment ?? 'Списание из системы учета',
            'storeId' => $model->store_id,
            'accountId' => "f3e1733a-8b67-44f9-941d-5cfa5340970e",
            'items' => $itemsArray
        ];

        Yii::info("Данные для отправки в iiko: " . json_encode($data, JSON_UNESCAPED_UNICODE), 'iiko');

        // Авторизуемся и отправляем документ
        Yii::info("Попытка авторизации в iiko...", 'iiko');
        if ($this->auth()) {
            Yii::info("Авторизация успешна, отправляем документ списания...", 'iiko');
            $url = "{$this->baseUrl}v2/documents/writeoff?key={$this->token}";
            Yii::info("URL запроса: {$this->baseUrl}v2/documents/writeoff", 'iiko');

            $jsonData = json_encode($data);
            Yii::info("JSON тело запроса: {$jsonData}", 'iiko');

            $result = $this->post($url, $jsonData, [
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json'
                ]
            ]);

            Yii::info("Ответ от iiko (raw): " . ($result ?: 'ПУСТОЙ'), 'iiko');

            // Проверяем ответ
            if (!empty($result)) {
                $response = json_decode($result, true);
                $jsonError = json_last_error();

                Yii::info("JSON decode error code: {$jsonError}", 'iiko');
                Yii::info("Ответ от iiko (decoded): " . json_encode($response, JSON_UNESCAPED_UNICODE), 'iiko');

                // Если получили корректный JSON ответ
                if ($jsonError === JSON_ERROR_NONE) {
                    // Проверяем успешность создания документа
                    if (isset($response['id']) || (isset($response['success']) && $response['success'] === true)) {
                        $docId = $response['id'] ?? 'unknown';
                        Yii::info("=== УСПЕХ: акт списания создан в iiko, documentId: {$docId} (списание #{$model->id}) ===", 'iiko');
                        return true;
                    } else {
                        // Возвращаем описание ошибки если есть
                        $errorMessage = $response['message'] ?? $response['error'] ?? $response['errorDescription'] ?? 'Неизвестная ошибка при создании акта списания';
                        Yii::error("Ошибка создания акта списания в iiko: {$errorMessage} (списание #{$model->id})", 'iiko');
                        Yii::error("Полный ответ: " . json_encode($response, JSON_UNESCAPED_UNICODE), 'iiko');
                        return $errorMessage;
                    }
                } else {
                    Yii::error("Ошибка парсинга ответа iiko (json_error={$jsonError}): {$result} (списание #{$model->id})", 'iiko');
                    return 'Ошибка парсинга ответа iiko: ' . $result;
                }
            }

            Yii::error("Пустой ответ от сервера iiko (списание #{$model->id})", 'iiko');
            return 'Пустой ответ от сервера iiko';
        }

        Yii::error("Ошибка авторизации в iiko (списание #{$model->id})", 'iiko');
        return 'Ошибка авторизации в iiko';
    }

    /**
     * Создание документа внутреннего перемещения в iiko
     * @param StoreTransfer $model
     * @return array Массив результатов для каждой пары магазинов ['success' => bool, 'message' => string]
     */
    public function createInternalTransferDoc($model)
    {
        Yii::info("=== НАЧАЛО createInternalTransferDoc для перемещения #{$model->id} ===", 'iiko');
        Yii::info("request_store_id: {$model->request_store_id}, status: {$model->status}, comment: " . ($model->comment ?? 'нет'), 'iiko');

        if ($model->status !== StoreTransfer::STATUS_COMPLETED) {
            Yii::warning("Перемещение #{$model->id} не завершено (status={$model->status})", 'iiko');
            return ['success' => false, 'message' => 'Заявка должна быть завершена перед отправкой в iiko'];
        }

        // Получаем все утвержденные позиции
        $approvedItems = [];
        $skippedItems = [];
        foreach ($model->items as $item) {
            if ($item->item_status === StoreTransferItem::STATUS_APPROVED && $item->approved_quantity > 0) {
                $approvedItems[] = $item;
                Yii::info("Утвержденная позиция: product_id={$item->product_id}, source_store_id={$item->source_store_id}, approved_quantity={$item->approved_quantity}", 'iiko');
            } else {
                $skippedItems[] = [
                    'product_id' => $item->product_id,
                    'item_status' => $item->item_status,
                    'approved_quantity' => $item->approved_quantity ?? 'null',
                    'reason' => 'не утверждена или approved_quantity <= 0'
                ];
            }
        }

        Yii::info("Всего позиций: " . count($model->items) . ", утвержденных: " . count($approvedItems), 'iiko');
        if (!empty($skippedItems)) {
            Yii::warning("Пропущено позиций: " . count($skippedItems) . " - " . json_encode($skippedItems, JSON_UNESCAPED_UNICODE), 'iiko');
        }

        if (empty($approvedItems)) {
            Yii::warning("Нет утвержденных позиций для перемещения #{$model->id}", 'iiko');
            return ['success' => false, 'message' => 'Нет утвержденных позиций для перемещения'];
        }

        // Группируем позиции по филиалу-источнику
        $itemsBySource = [];
        foreach ($approvedItems as $item) {
            $sourceId = $item->source_store_id;
            if (!isset($itemsBySource[$sourceId])) {
                $itemsBySource[$sourceId] = [];
            }
            $itemsBySource[$sourceId][] = $item;
        }

        Yii::info("Группировка по источникам: " . count($itemsBySource) . " филиалов-источников", 'iiko');

        // Создаем документ для каждой пары магазинов
        $results = [];
        $allSuccess = true;

        foreach ($itemsBySource as $sourceStoreId => $items) {
            $sourceStore = Stores::findOne($sourceStoreId);
            $sourceStoreName = $sourceStore ? $sourceStore->name : $sourceStoreId;

            Yii::info("--- Обработка источника: {$sourceStoreName} ({$sourceStoreId}), позиций: " . count($items) . " ---", 'iiko');

            $itemsArray = [];
            $skippedProducts = [];
            foreach ($items as $item) {
                $product = Products::findOne($item->product_id);
                if ($product == null) {
                    $skippedProducts[] = ['product_id' => $item->product_id, 'reason' => 'продукт не найден в БД'];
                    continue;
                }

                $itemsArray[] = [
                    'productId' => $item->product_id,
                    'amount' => (float)$item->approved_quantity
                ];
                Yii::info("Добавлена позиция: productId={$item->product_id}, amount={$item->approved_quantity}, name={$product->name}", 'iiko');
            }

            if (!empty($skippedProducts)) {
                Yii::warning("Пропущено продуктов для источника {$sourceStoreName}: " . json_encode($skippedProducts, JSON_UNESCAPED_UNICODE), 'iiko');
            }

            if (empty($itemsArray)) {
                Yii::warning("Нет позиций для источника {$sourceStoreName} после фильтрации", 'iiko');
                continue;
            }

            // Формируем данные для запроса
            $data = [
                'dateIncoming' => $model->created_at ? date('Y-m-d\TH:i', strtotime($model->created_at)) : date('Y-m-d\TH:i'),
                'status' => 'PROCESSED',
                'comment' => $model->comment ?? "Внутреннее перемещение #{$model->id}",
                'storeFromId' => $sourceStoreId,
                'storeToId' => $model->request_store_id,
                'items' => $itemsArray
            ];

            Yii::info("Данные для отправки в iiko (источник {$sourceStoreName}): " . json_encode($data, JSON_UNESCAPED_UNICODE), 'iiko');

            // Авторизуемся и отправляем документ
            Yii::info("Попытка авторизации в iiko...", 'iiko');
            if ($this->auth()) {
                Yii::info("Авторизация успешна, отправляем документ перемещения...", 'iiko');
                $url = "{$this->baseUrl}v2/documents/internalTransfer?key={$this->token}";
                Yii::info("URL запроса: {$this->baseUrl}v2/documents/internalTransfer", 'iiko');

                $jsonData = json_encode($data);
                Yii::info("JSON тело запроса: {$jsonData}", 'iiko');

                $result = $this->post($url, $jsonData, [
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json'
                    ]
                ]);

                Yii::info("Ответ от iiko (raw): " . ($result ?: 'ПУСТОЙ'), 'iiko');

                // Проверяем ответ
                if (!empty($result)) {
                    $response = json_decode($result, true);
                    $jsonError = json_last_error();

                    Yii::info("JSON decode error code: {$jsonError}", 'iiko');
                    Yii::info("Ответ от iiko (decoded): " . json_encode($response, JSON_UNESCAPED_UNICODE), 'iiko');

                    // Если получили корректный JSON ответ
                    if ($jsonError === JSON_ERROR_NONE) {
                        // Проверяем успешность создания документа
                        if (isset($response['id']) || (isset($response['success']) && $response['success'] === true)) {
                            $docId = $response['id'] ?? 'unknown';
                            Yii::info("УСПЕХ: документ перемещения создан в iiko из {$sourceStoreName}, documentId: {$docId}", 'iiko');
                            $results[] = ['success' => true, 'message' => "Создан документ перемещения из {$sourceStoreName}"];
                        } else {
                            // Возвращаем описание ошибки если есть
                            $errorMessage = $response['message'] ?? $response['error'] ?? $response['errorDescription'] ?? 'Неизвестная ошибка';
                            Yii::error("Ошибка создания документа перемещения в iiko из {$sourceStoreName}: {$errorMessage}", 'iiko');
                            Yii::error("Полный ответ: " . json_encode($response, JSON_UNESCAPED_UNICODE), 'iiko');
                            $results[] = ['success' => false, 'message' => "Ошибка для {$sourceStoreName}: {$errorMessage}"];
                            $allSuccess = false;
                        }
                    } else {
                        Yii::error("Ошибка парсинга ответа iiko (json_error={$jsonError}): {$result}", 'iiko');
                        $results[] = ['success' => false, 'message' => "Ошибка парсинга ответа iiko: {$result}"];
                        $allSuccess = false;
                    }
                } else {
                    Yii::error("Пустой ответ от сервера iiko для источника {$sourceStoreName}", 'iiko');
                    $results[] = ['success' => false, 'message' => 'Пустой ответ от сервера iiko'];
                    $allSuccess = false;
                }
            } else {
                Yii::error("Ошибка авторизации в iiko (перемещение #{$model->id})", 'iiko');
                return ['success' => false, 'message' => 'Ошибка авторизации в iiko'];
            }
        }

        // Формируем итоговый результат
        if ($allSuccess) {
            $message = 'Все документы внутреннего перемещения успешно созданы в iiko';
            if (count($results) > 1) {
                $message .= ' (' . count($results) . ' документов)';
            }
            Yii::info("=== УСПЕХ createInternalTransferDoc: {$message} (перемещение #{$model->id}) ===", 'iiko');
            return ['success' => true, 'message' => $message, 'details' => $results];
        } else {
            $successCount = count(array_filter($results, function($r) { return $r['success']; }));
            $totalCount = count($results);
            $message = "Создано {$successCount} из {$totalCount} документов. Проверьте детали.";
            Yii::warning("=== ЧАСТИЧНЫЙ УСПЕХ/ОШИБКА createInternalTransferDoc: {$message} (перемещение #{$model->id}) ===", 'iiko');
            return [
                'success' => false,
                'message' => $message,
                'details' => $results
            ];
        }
    }

    /**
     * Расходной накладной
     * @param Orders $model
     * @return bool
     * @throws \yii\db\Exception
     */
    public function supplierOutStockDoc($model, $debug = false, $customDate = null)
    {
        $orderId = $model->id;

        Yii::info("=== НАЧАЛО supplierOutStockDoc для заказа #{$orderId} ===", 'iiko');
        Yii::info("supplierId: {$model->supplierId}, storeId: {$model->storeId}", 'iiko');

        $storeId = Settings::getValue('stock-id');
        Yii::info("stock-id из настроек: {$storeId}", 'iiko');

        if ($debug) {
            echo '<pre>';
            print_r($model->supplierId);
            echo '</pre>';
        }

        $sql = "select * from order_items oi where oi.orderId=:id and oi.supplierQuantity>0";
        $items = Yii::$app->db->createCommand($sql)
            ->bindParam(":id", $orderId, PDO::PARAM_INT)
            ->queryAll();

        Yii::info("Найдено позиций с supplierQuantity>0: " . count($items), 'iiko');

        if (empty($items)) {
            Yii::warning("Нет позиций для отправки в iiko (заказ #{$orderId})", 'iiko');
            return false;
        }

            $itemsXml = "";
            $defaultStoreId = '';
            $defaultSupplierFromId = $model->supplierId;
            $addedItemsCount = 0;
            $skippedItems = [];

            foreach ($items as $item) {
                if (empty($item['price']) || empty($item['productId']) || empty($item['shipped_from_warehouse'])) {
                    $skippedItems[] = [
                        'productId' => $item['productId'] ?? 'null',
                        'price' => $item['price'] ?? 'null',
                        'shipped_from_warehouse' => $item['shipped_from_warehouse'] ?? 'null',
                        'reason' => 'пустое price/productId/shipped_from_warehouse'
                    ];
                    continue;
                }
                $price = $item['price'] * (100 + $model->user->percentage) / 100;
                $sum = $price * $item['shipped_from_warehouse'];

                $defaultStoreId = $item['storeId'];
                $itemsXml .= "<item>
                            <productId>{$item['productId']}</productId>
                            <price>{$price}</price>
                            <amount>{$item['shipped_from_warehouse']}</amount>
                            <sum>{$sum}</sum>
                            <discountSum>0.00</discountSum>
                            <vatPercent>0.000000000</vatPercent>
                            <vatSum>0.00</vatSum>
                         </item>";
                $addedItemsCount++;
            }

            Yii::info("Добавлено позиций в XML: {$addedItemsCount}", 'iiko');
            if (!empty($skippedItems)) {
                Yii::warning("Пропущено позиций: " . count($skippedItems) . " - " . json_encode($skippedItems, JSON_UNESCAPED_UNICODE), 'iiko');
            }

            if (empty($itemsXml)) {
                Yii::warning("itemsXml пустой - нет позиций с ценой и количеством (заказ #{$orderId})", 'iiko');
                return false;
            }

            $number = "sup-out-1-{$orderId}";
            $date = $customDate ? date("Y-m-d\TH:i:s", strtotime($customDate)) : date("Y-m-d\TH:i:s");
            $doc = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>
                    <document>
                        <documentNumber>{$number}</documentNumber>
                        <dateIncoming>{$date}</dateIncoming>
                        <useDefaultDocumentTime>true</useDefaultDocumentTime>
                        <defaultStoreId>{$storeId}</defaultStoreId>
                        <counteragentId>{$defaultSupplierFromId}</counteragentId>
                        <status>PROCESSED</status>
                        <items>{$itemsXml}</items>
                    </document>";

            Yii::info("Сформирован XML документ для iiko:\n" .
                "documentNumber: {$number}\n" .
                "dateIncoming: {$date}\n" .
                "defaultStoreId: {$storeId}\n" .
                "counteragentId: {$defaultSupplierFromId}", 'iiko');

            if ($debug) {
                echo '<pre>';
                print_r("<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>
                    <document>
                        <documentNumber>{$number}</documentNumber>
                        <dateIncoming>{$date}</dateIncoming>
                        <useDefaultDocumentTime>true</useDefaultDocumentTime>
                        <defaultStoreId>{$storeId}</defaultStoreId>
                        <counteragentId>{$defaultSupplierFromId}</counteragentId>
                        <status>PROCESSED</status>
                    </document>");
                echo '</pre>';
            }

            Yii::info("Попытка авторизации в iiko...", 'iiko');
            if ($this->auth()) {
                Yii::info("Авторизация успешна, отправляем документ...", 'iiko');
                $url = "{$this->baseUrl}documents/import/outgoingInvoice?key={$this->token}";
                Yii::info("URL запроса: {$this->baseUrl}documents/import/outgoingInvoice", 'iiko');

                $result = $this->post($url, $doc);

                Yii::info("Ответ от iiko (raw): " . substr($result, 0, 1000), 'iiko');

                if (empty($result)) {
                    Yii::error("Пустой ответ от iiko (заказ #{$orderId})", 'iiko');
                    return false;
                }

                try {
                    $xml = new SimpleXMLElement($result);
                    $valid = (string)$xml->valid;
                    $warning = (string)$xml->warning;
                    $errorDescription = isset($xml->errorDescription) ? (string)$xml->errorDescription : '';
                    $documentNumber = isset($xml->documentNumber) ? (string)$xml->documentNumber : '';

                    Yii::info("Ответ iiko: valid={$valid}, warning={$warning}, documentNumber={$documentNumber}", 'iiko');

                    if (!empty($errorDescription)) {
                        Yii::error("Ошибка от iiko: {$errorDescription} (заказ #{$orderId})", 'iiko');
                    }

                    if ($valid == "true" && $warning == "false") {
                        $model->outgoingDocumentId = $documentNumber;
                        $model->save();
                        Yii::info("=== УСПЕХ: документ создан в iiko, documentId: {$documentNumber} (заказ #{$orderId}) ===", 'iiko');
                        return true;
                    } else {
                        Yii::error("Документ НЕ создан в iiko: valid={$valid}, warning={$warning} (заказ #{$orderId})", 'iiko');
                    }
                } catch (\Exception $e) {
                    Yii::error("Исключение при парсинге ответа iiko: " . $e->getMessage() . " (заказ #{$orderId})", 'iiko');
                    Yii::error("Raw ответ: " . $result, 'iiko');
                    return $e->getMessage();
                }
            } else {
                Yii::error("Авторизация в iiko НЕ удалась (заказ #{$orderId})", 'iiko');
            }

        Yii::warning("=== КОНЕЦ supplierOutStockDoc - документ НЕ создан (заказ #{$orderId}) ===", 'iiko');
        return false;
    }


    public function getOutgoingDocs($start = null, $end = null)
    {
        $url = "{$this->baseUrl}documents/export/outgoingInvoice?key={$this->token}";

        if (empty($start)) {
            $start = date('Y-m-d');
        }

        if (empty($end)) {
            $end = date('Y-m-d');
        }

        if (!empty($start) && !empty($end)) {
            $start = date('Y-m-d', strtotime($start));
            $end = date('Y-m-d', strtotime($end));
            $url .= "&from={$start}&to={$end}";
        }
        $result = $this->get($url);
        return $this->xmlToArray($result);
    }

    private function xmlToArray($xml)
    {
        $data = simplexml_load_string($xml);
        $json = Json::encode($data);
        return Json::decode($json, true);
    }


    /**
     * Send a POST request using cURL
     * @param string $url to request
     * @param array|string $post values to send
     * @param array $options for cURL
     * @return string
     * @internal param array $get
     */
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

    /**
     * Send a GET request using cURL
     * @param string $url to request
     * @param array|string $post values to send
     * @param array $options for cURL
     * @return string
     * @internal param array $get
     */
    private function get($url, $post = null, array $options = array())
    {
        $defaults = [
            CURLOPT_HEADER => 0,
            CURLOPT_URL => $url,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_SSL_VERIFYHOST => 0, //unsafe, but the fastest solution for the error " SSL certificate problem, verify that the CA cert is OK"
            CURLOPT_SSL_VERIFYPEER => 0, //unsafe, but the fastest solution for the error " SSL certificate problem, verify that the CA cert is OK"
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/xml',
            ]
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