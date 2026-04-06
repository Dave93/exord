<?php

namespace app\commands;

use app\models\Orders;
use app\models\Iiko;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Команда для экспорта сверки заказов и расходных накладных iiko в CSV
 */
class ExportInvoiceReportController extends Controller
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
     * @var string Путь к выходному CSV файлу
     */
    public $output;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'from',
            'to',
            'output',
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
            'o' => 'output',
        ]);
    }

    /**
     * Экспортирует сверку заказов и накладных iiko в CSV
     *
     * Использование:
     * php yii export-invoice-report/index --from=2026-03-01 --to=2026-03-31
     * php yii export-invoice-report/index --from=2026-03-01 --to=2026-03-31 --output=/path/to/report.csv
     *
     * @return int Exit code
     */
    public function actionIndex()
    {
        if (empty($this->from) || empty($this->to)) {
            $this->stderr("Ошибка: необходимо указать параметры --from и --to\n");
            $this->stderr("Пример: php yii export-invoice-report/index --from=2026-03-01 --to=2026-03-31\n");
            return ExitCode::USAGE;
        }

        $fromDate = date('Y-m-d', strtotime($this->from));
        $toDate = date('Y-m-d', strtotime($this->to));

        if ($fromDate > $toDate) {
            $this->stderr("Ошибка: дата начала не может быть больше даты окончания\n");
            return ExitCode::USAGE;
        }

        // Определяем путь к CSV файлу
        $outputPath = $this->output ?: \Yii::getAlias('@app/runtime/invoice_report_' . $fromDate . '_' . $toDate . '.csv');

        $this->stdout("Сверка заказов за период: {$fromDate} - {$toDate}\n\n");

        // Авторизация в iiko
        $iiko = new Iiko();
        if (!$iiko->auth()) {
            $this->stderr("Ошибка: не удалось авторизоваться в iiko\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Получаем расходные накладные из iiko (с запасом +32 дня)
        $this->stdout("Получение расходных накладных из iiko...\n");
        $extendedToDate = date('Y-m-d', strtotime($toDate . ' +32 days'));
        $outgoingDocsData = $iiko->getOutgoingDocs($fromDate, $extendedToDate);

        // Индексируем накладные по ID заказа
        $invoicesByOrderId = [];
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
                    $invoicesByOrderId[$orderId] = $doc;
                }
            }
        }

        $this->stdout("Найдено накладных в iiko: " . count($invoicesByOrderId) . "\n");

        // Получаем заказы из базы данных
        $this->stdout("Получение заказов из базы данных...\n");
        $orders = Orders::find()
            ->where(['>=', 'addDate', $fromDate . ' 00:00:00'])
            ->andWhere(['<=', 'addDate', $toDate . ' 23:59:59'])
            ->andWhere(['state' => 1])
            ->all();

        $this->stdout("Найдено заказов в базе: " . count($orders) . "\n\n");

        // Формируем CSV
        $fp = fopen($outputPath, 'w');
        if ($fp === false) {
            $this->stderr("Ошибка: не удалось создать файл {$outputPath}\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // BOM для корректного отображения UTF-8 в Excel
        fwrite($fp, "\xEF\xBB\xBF");

        // Заголовки
        fputcsv($fp, [
            'ID заказа',
            'Дата заказа',
            'Дата отправки',
            'Статус заказа',
            'Магазин',
            'Накладная найдена',
            'Номер накладной',
            'Дата накладной',
            'Статус накладной',
        ], ';');

        $missingCount = 0;
        $foundCount = 0;

        foreach ($orders as $order) {
            $storeName = $order->store ? $order->store->name : 'N/A';
            $stateName = isset(Orders::$states[$order->state]) ? Orders::$states[$order->state] : 'Неизвестен';

            $hasInvoice = isset($invoicesByOrderId[$order->id]);

            if ($hasInvoice) {
                $doc = $invoicesByOrderId[$order->id];
                $invoiceNumber = $doc['documentNumber'] ?? '';
                $invoiceDate = $doc['dateIncoming'] ?? '';
                $invoiceStatus = $doc['status'] ?? '';
                $foundCount++;
            } else {
                $invoiceNumber = '';
                $invoiceDate = '';
                $invoiceStatus = '';
                $missingCount++;
            }

            fputcsv($fp, [
                $order->id,
                $order->addDate,
                $order->sent_date,
                $stateName,
                $storeName,
                $hasInvoice ? 'Да' : 'Нет',
                $invoiceNumber,
                $invoiceDate,
                $invoiceStatus,
            ], ';');
        }

        fclose($fp);

        $this->stdout("CSV файл сохранён: {$outputPath}\n\n");
        $this->stdout("Итого:\n");
        $this->stdout("  С накладными: {$foundCount}\n");
        $this->stdout("  Без накладных: {$missingCount}\n");
        $this->stdout("  Всего заказов: " . count($orders) . "\n");

        return ExitCode::OK;
    }
}
