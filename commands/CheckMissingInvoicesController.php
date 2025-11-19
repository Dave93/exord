<?php

namespace app\commands;

use app\models\Orders;
use app\models\Iiko;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Команда для проверки отсутствующих расходных накладных в iiko
 */
class CheckMissingInvoicesController extends Controller
{
    /**
     * @var string Дата начала периода (формат: Y-m-d)
     */
    public $from;

    /**
     * @var string Дата окончания периода (формат: Y-m-d)
     */
    public $to;

    /**
     * @var bool Отправить отсутствующие накладные в iiko
     */
    public $send = false;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'from',
            'to',
            'send',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'f' => 'from',
            't' => 'to',
            's' => 'send',
        ]);
    }

    /**
     * Проверяет заказы без расходных накладных в iiko
     *
     * Использование:
     * php yii check-missing-invoices/index --from=2024-01-01 --to=2024-01-31
     * php yii check-missing-invoices/index -f 2024-01-01 -t 2024-01-31
     * php yii check-missing-invoices/index --from=2024-10-01 --to=2024-10-31 --send
     *
     * @return int Exit code
     */
    public function actionIndex()
    {
        if (empty($this->from) || empty($this->to)) {
            $this->stderr("Ошибка: необходимо указать параметры --from и --to\n");
            $this->stderr("Пример: php yii check-missing-invoices/index --from=2024-01-01 --to=2024-01-31\n");
            return ExitCode::USAGE;
        }

        $fromDate = date('Y-m-d', strtotime($this->from));
        $toDate = date('Y-m-d', strtotime($this->to));

        if ($fromDate > $toDate) {
            $this->stderr("Ошибка: дата начала не может быть больше даты окончания\n");
            return ExitCode::USAGE;
        }

        $this->stdout("Проверка заказов за период: {$fromDate} - {$toDate}\n\n");

        // Получаем расходные накладные из iiko
        $iiko = new Iiko();
        if (!$iiko->auth()) {
            $this->stderr("Ошибка: не удалось авторизоваться в iiko\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("Получение расходных накладных из iiko...\n");
        $outgoingDocsData = $iiko->getOutgoingDocs($fromDate, $toDate);

        // Извлекаем ID заказов из накладных
        $invoiceOrderIds = [];
        if (!empty($outgoingDocsData['document'])) {
            $documents = $outgoingDocsData['document'];

            // Если один документ, оборачиваем в массив
            if (isset($documents['documentNumber'])) {
                $documents = [$documents];
            }

            foreach ($documents as $doc) {
                $docNumber = $doc['documentNumber'];
                // Формат: sup-out-1-{orderId}
                $parts = explode('-', $docNumber);
                $orderId = end($parts);
                if (is_numeric($orderId)) {
                    $invoiceOrderIds[$orderId] = $docNumber;
                }
            }
        }

        $this->stdout("Найдено накладных в iiko: " . count($invoiceOrderIds) . "\n");

        // Получаем заказы из базы данных
        $this->stdout("Получение заказов из базы данных...\n");
        $orders = Orders::find()
            ->where(['>=', 'addDate', $fromDate . ' 00:00:00'])
            ->andWhere(['<=', 'addDate', $toDate . ' 23:59:59'])
            ->andWhere(['state' => 1])
            ->all();

        $this->stdout("Найдено заказов в базе: " . count($orders) . "\n\n");

        // Находим заказы без накладных
        $missingInvoices = [];
        foreach ($orders as $order) {
            if (!isset($invoiceOrderIds[$order->id])) {
                $missingInvoices[] = $order;
            }
        }

        if (empty($missingInvoices)) {
            $this->stdout("Все заказы имеют соответствующие расходные накладные.\n");
            return ExitCode::OK;
        }

        // Выводим результат
        $this->stdout("Заказы без расходных накладных (" . count($missingInvoices) . "):\n");
        $this->stdout(str_repeat('-', 80) . "\n");
        $this->stdout(sprintf("%-10s | %-20s | %-15s | %-20s\n",
            "ID", "Дата", "Статус", "Магазин"));
        $this->stdout(str_repeat('-', 80) . "\n");

        foreach ($missingInvoices as $order) {
            $storeName = $order->store ? $order->store->name : 'N/A';
            $state = $this->getOrderStateName($order->state);

            $this->stdout(sprintf("%-10s | %-20s | %-15s | %-20s\n",
                $order->id,
                $order->addDate,
                $state,
                mb_substr($storeName, 0, 20)
            ));
        }

        $this->stdout(str_repeat('-', 80) . "\n");
        $this->stdout("Итого: " . count($missingInvoices) . " заказов без накладных\n");

        // Отправка накладных в iiko если указан параметр --send
        if ($this->send) {
            $this->stdout("\nОтправка расходных накладных в iiko...\n");
            $this->stdout(str_repeat('-', 80) . "\n");

            $successCount = 0;
            $errorCount = 0;

            foreach ($missingInvoices as $order) {
                // Определяем дату для накладной
                // Октябрьские накладные должны сесть как 1 ноября
                $orderMonth = date('m', strtotime($order->addDate));
                $orderYear = date('Y', strtotime($order->addDate));

                if ($orderMonth == '10') {
                    $customDate = $orderYear . '-11-01';
                } else {
                    $customDate = null; // Используем текущую дату
                }

                $result = $iiko->supplierOutStockDoc($order, false, $customDate);

                if ($result === true) {
                    $this->stdout("✓ Заказ #{$order->id} - накладная создана успешно\n");
                    $successCount++;
                } else {
                    $errorMessage = is_string($result) ? $result : 'Неизвестная ошибка';
                    $this->stdout("✗ Заказ #{$order->id} - ошибка: {$errorMessage}\n");
                    $errorCount++;
                }
            }

            $this->stdout(str_repeat('-', 80) . "\n");
            $this->stdout("Результат отправки: успешно - {$successCount}, ошибок - {$errorCount}\n");
        }

        return ExitCode::OK;
    }

    /**
     * Возвращает название состояния заказа
     *
     * @param int $state
     * @return string
     */
    private function getOrderStateName($state)
    {
        $states = [
            0 => 'Новый',
            1 => 'Отправлен',
            2 => 'Завершен',
            3 => 'На проверке',
        ];

        return isset($states[$state]) ? $states[$state] : 'Неизвестен';
    }
}
