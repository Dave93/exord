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
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $hash = sha1($this->password);
        $token = file_get_contents("{$this->baseUrl}auth?login={$this->login}&pass={$hash}", false, stream_context_create($arrContextOptions));
        if (!$token) {
            return false;
        }
        $this->token = $token;
        Settings::setValue("iiko-token", $this->token);
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
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $data = file_get_contents("{$this->baseUrl}{$url}?key={$this->token}", false, stream_context_create($arrContextOptions));
        if (empty($data)) {
//            $this->auth();
            $data = file_get_contents("{$this->baseUrl}{$url}?key={$this->token}", false, stream_context_create($arrContextOptions));
        }
        return $data = $this->xmlToArray($data);
    }

    public function departments()
    {
        $data = $this->getData("corporation/departments");

        Departments::deleteAll();
        foreach ($data['corporateItemDto'] as $row) {
            $model = new Departments();
            $model->id = (string)$row['id'];
            $model->parentId = (string)$row['parentId'];
            $model->name = (string)$row['name'];
            $model->type = (string)$row['type'];
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
        Stores::deleteAll();
        foreach ($data['corporateItemDto'] as $row) {
            $model = new Stores();
            $model->id = (string)$row['id'];
            $model->parentId = (string)$row['parentId'];
            $model->name = (string)$row['name'];
            $model->type = (string)$row['type'];
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
        foreach ($data['groupDto'] as $row) {
            $id = (string)$row['id'];
            $gList[] = $id;
            $model = Groups::findOne($id);
            if ($model == null) {
                $model = new Groups();
                $model->id = (string)$row['id'];
            }
            $model->name = (string)$row['name'];
            $model->departmentId = (string)$row['departmentId'];
            $model->syncDate = date("Y-m-d H:i:s");
            if (!$model->save()) {
                return $model->firstErrors;
            }
        }
        Groups::deleteAll(['not in', 'id', $gList]);
        return true;
    }

    public function products()
    {
        $list = [];
        $data = $this->getData("products");
        foreach ($data['productDto'] as $row) {
            $id = (string)$row['id'];
            $type = (string)$row['productType'];
            if (!in_array($type, ['GOODS', 'PREPARED', '']))
                continue;
            $model = Products::findOne($id);
            $list[] = $id;

            if ($model == null) {
                $model = new Products();
                $model->id = $id;
            }
            if (empty($row['parentId']))
                $row['parentId'] = 0;
            $model->parentId = (string)$row['parentId'];
            $model->name = trim((string)$row['name']);
            $model->num = $row['num'];
            $model->code = (int)$row['code'];
            $model->productType = $type;
            $model->cookingPlaceType = (string)$row['cookingPlaceType'];
            $model->mainUnit = (string)$row['mainUnit'];
            $model->syncDate = date("Y-m-d H:i:s");
            if (!$model->save()) {
                return $model->firstErrors;
            }
        }
        Products::deleteAll(['not in', 'id', $list]);
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
        foreach ($data['employee'] as $row) {
            $id = (string)$row['id'];
            $list[] = $id;
            $model = Suppliers::findOne($id);
            if ($model == null) {
                $model = new Suppliers();
                $model->id = $id;
            }
            $model->name = (string)$row['name'];
            $model->deleted = ((string)$row['deleted'] == "true") ? 1 : 0;
            $model->supplier = ((string)$row['supplier'] == "true") ? 1 : 0;
            $model->employee = ((string)$row['employee'] == "true") ? 1 : 0;
//            $model->client = ((string)$row->client == "true") ? 1 : 0;
            $model->syncDate = date("Y-m-d H:i:s");
            if (!$model->save()) {
                return $model->firstErrors;
            }
        }
        Suppliers::deleteAll(['not in', 'id', $list]);
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
        $sql = "select * from order_items oi where oi.orderId=:id and oi.storeQuantity>0";
        $items = Yii::$app->db->createCommand($sql)
            ->bindParam(":id", $model->id, PDO::PARAM_INT)
            ->queryAll();
        if (empty($items))
            return false;

        $defaultStoreId = "";
        $itemsXml = "";
        foreach ($items as $item) {
            $product = Products::findOne($item['productId']);
            if (empty($item['storeId']) || empty($item['productId']) || empty($item['storeQuantity']) || $product == null) {
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
        }

        if (empty($defaultStoreId) || empty($itemsXml))
            return false;

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
        if ($this->auth()) {
            $url = "{$this->baseUrl}documents/import/outgoingInvoice?key={$this->token}";
            $result = $this->post($url, $doc);
            try {
                $xml = new SimpleXMLElement($result);
                if ((string)$xml->valid == "true" && (string)$xml->warning == "false") {
                    $model->outgoingDocumentId = (string)$xml->documentNumber;
                    $model->save();
                    return true;
                }
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        }
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
     * Расходной накладной
     * @param Orders $model
     * @return bool
     * @throws \yii\db\Exception
     */
    public function supplierInStockDoc($model)
    {
        $sql = "select supplierId from order_items oi where oi.orderId=:id and oi.supplierQuantity>0 and oi.supplierId!='' group by oi.supplierId";
        $suppliers = Yii::$app->db->createCommand($sql)
            ->bindParam(":id", $model->id, PDO::PARAM_INT)
            ->queryColumn();
        $k = 0;
        foreach ($suppliers as $supplier) {
            if (empty($supplier))
                continue;
            $k++;
            $sql = "select * from order_items oi where oi.orderId=:id and oi.supplierId=:s and oi.supplierQuantity>0";
            $items = Yii::$app->db->createCommand($sql)
                ->bindParam(":id", $model->id, PDO::PARAM_INT)
                ->bindParam(":s", $supplier, PDO::PARAM_INT)
                ->queryAll();

            if (empty($items))
                return false;

            $i = 0;
            $itemsXml = "";
            $storeId = "";
            $supplierId = "";
//            $storeId = Settings::getValue('stock-id');
            foreach ($items as $item) {
                $i++;
                if (empty($item['price']) || empty($item['supplierId']) || empty($item['supplierQuantity'])) {
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
            }
            if (empty($storeId) || empty($supplierId) || empty($itemsXml))
                return false;

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
            if ($this->auth()) {
                $url = "{$this->baseUrl}documents/import/incomingInvoice?key={$this->token}";
                $result = $this->post($url, $doc);
                try {
                    $xml = new SimpleXMLElement($result);
                    if ((string)$xml->valid == "true" && (string)$xml->warning == "false") {
                        return $this->supplierOutStockDoc($model);
                    }
                } catch (\Exception $e) {
                    return print_r($e->getMessage());
                }
            }
        }

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
        if ($model->status !== ProductWriteoff::STATUS_APPROVED) {
            return 'Списание должно быть утверждено перед отправкой в iiko';
        }

        // Получаем позиции списания с утвержденными количествами
        $items = $model->items;
        @file_put_contents(__DIR__ . '/createWriteoffDoc.txt', "items: " . print_r($items, true) . "\n\n", FILE_APPEND);
        if (empty($items)) {
            return 'Нет позиций для списания';
        }

        $itemsArray = [];
        foreach ($items as $item) {
            if (empty($item->approved_count) || $item->approved_count <= 0) {
                continue;
            }

            $product = Products::findOne($item->product_id);
            if ($product == null) {
                continue;
            }

            $itemsArray[] = [
                'productId' => $item->product_id,
                'amount' => (float)$item->approved_count
            ];
        }
        @file_put_contents(__DIR__ . '/createWriteoffDoc.txt', "itemsArray: " . print_r($itemsArray, true) . "\n\n", FILE_APPEND);
        if (empty($itemsArray)) {
            return 'Нет утвержденных позиций для списания';
        }

        // Формируем данные для запроса
        $data = [
            'dateIncoming' => $model->approved_at ? date('Y-m-d\TH:i', strtotime($model->approved_at)) : date('Y-m-d\TH:i'),
            'status' => 'NEW',
            'comment' => $model->comment ?? 'Списание из системы учета',
            'storeId' => $model->store_id,
            'accountId' => "f3e1733a-8b67-44f9-941d-5cfa5340970e",
            'items' => $itemsArray
        ];

        @file_put_contents(__DIR__ . '/createWriteoffDoc.txt', "data: " . print_r($data, true) . "\n\n", FILE_APPEND);

        // Добавляем accountId если указан
        // if (!empty($accountId)) {
        //     $data['accountId'] = $accountId;
        // }

        // Авторизуемся и отправляем документ
        if ($this->auth()) {
            $url = "{$this->baseUrl}v2/documents/writeoff?key={$this->token}";

            $result = $this->post($url, json_encode($data), [
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json'
                ]
            ]);

            // Проверяем ответ
            if (!empty($result)) {
                @file_put_contents(__DIR__ . '/createWriteoffDoc.txt', "result: " . print_r($result, true) . "\n\n", FILE_APPEND);
                $response = json_decode($result, true);
                @file_put_contents(__DIR__ . '/createWriteoffDoc.txt', "response json_decode: " . print_r($response, true) . "\n\n", FILE_APPEND);
                // Если получили корректный JSON ответ
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Проверяем успешность создания документа
                    if (isset($response['id']) || (isset($response['success']) && $response['success'] === true)) {
                        return true;
                    } else {
                        // Возвращаем описание ошибки если есть
                        $errorMessage = $response['message'] ?? $response['error'] ?? 'Неизвестная ошибка при создании акта списания';
                        return $errorMessage;
                    }
                } else {
                    return 'Ошибка парсинга ответа iiko: ' . $result;
                }
            }

            return 'Пустой ответ от сервера iiko';
        }

        return 'Ошибка авторизации в iiko';
    }

    /**
     * Расходной накладной
     * @param Orders $model
     * @return bool
     * @throws \yii\db\Exception
     */
    public function supplierOutStockDoc($model, $debug = false)
    {
//        echo '<pre>';
//        print_r($model->id);
//        echo '</pre>';
        $orderId = $model->id;
        // $sql = "select supplierId from order_items oi where oi.orderId=:id and oi.supplierQuantity>0 and oi.supplierId!='' group by oi.supplierId";
        // $suppliers = Yii::$app->db->createCommand($sql)
        //     ->bindParam(":id", $orderId, PDO::PARAM_INT)
        //     ->queryColumn();
        // $k = 0;
        $storeId = Settings::getValue('stock-id');
        if ($debug) {
            echo '<pre>';
            print_r($model->supplierId);
            echo '</pre>';
        }
        // foreach ($suppliers as $supplier) {
        //     if (empty($supplier))
        //         continue;
        //     $k++;
            $sql = "select * from order_items oi where oi.orderId=:id and oi.supplierQuantity>0";
            $items = Yii::$app->db->createCommand($sql)
                ->bindParam(":id", $orderId, PDO::PARAM_INT)
                ->queryAll();
//            echo '<pre>'; var_dump(empty($items)); echo '</pre>';
            if (empty($items))
                return false;

            $itemsXml = "";
//            $defaultStoreId = Settings::getValue('stock-id');
            $defaultStoreId = '';
            $defaultSupplierFromId = $model->supplierId;
            foreach ($items as $item) {
                if (empty($item['price']) || empty($item['productId']) || empty($item['shipped_from_warehouse'])) {
                    continue;
                }
                $price = $item['price'] * (100 + $model->user->percentage) / 100;
                $sum = $price * $item['shipped_from_warehouse'];

                $defaultStoreId = $item['storeId'];
                // if (empty($defaultSupplierFromId)) {
                //     $defaultSupplierFromId = $item['supplierId'];
                // }
                $itemsXml .= "<item>
                            <productId>{$item['productId']}</productId>
                            <price>{$price}</price>
                            <amount>{$item['shipped_from_warehouse']}</amount>
                            <sum>{$sum}</sum>
                            <discountSum>0.00</discountSum>
                            <vatPercent>0.000000000</vatPercent>
                            <vatSum>0.00</vatSum>
                         </item>";
            }

            if (empty($itemsXml))
                return false;

            $number = "sup-out-1-{$orderId}";
            $date = date("Y-m-d\TH:i:s");
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
            if ($this->auth()) {
                $url = "{$this->baseUrl}documents/import/outgoingInvoice?key={$this->token}";
                $result = $this->post($url, $doc);
                try {
                    $xml = new SimpleXMLElement($result);
                    if ((string)$xml->valid == "true" && (string)$xml->warning == "false") {
                        $model->outgoingDocumentId = (string)$xml->documentNumber;
                        $model->save();
                        return true;
                    }
                } catch (\Exception $e) {
                    return $e->getMessage();
                }
            }
        // }
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