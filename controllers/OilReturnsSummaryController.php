<?php

namespace app\controllers;

use Yii;
use app\models\OilInventory;
use app\models\Stores;
use app\models\User;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\components\AccessRule;
use yii\db\Query;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class OilReturnsSummaryController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['*'],
                'ruleConfig' => [
                    'class' => AccessRule::class,
                ],
                'rules' => [
                    [
                        'actions' => ['index', 'export-excel'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_ADMIN,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $dateFrom = Yii::$app->request->get('date_from', date('Y-m-d', strtotime('-7 days')));
        $dateTo = Yii::$app->request->get('date_to', date('Y-m-d'));

        // Check if single day is selected
        $isSingleDay = ($dateFrom === $dateTo);
        $detailedData = [];

        if ($isSingleDay) {
            // If single day, get detailed data with creation dates
            $query = (new Query())
                ->select([
                    's.name as terminal_name',
                    's.id as store_id',
                    'oi.return_amount_kg',
                    'oi.return_amount',
                    'oi.created_at',
                    'oi.id'
                ])
                ->from(['oi' => OilInventory::tableName()])
                ->leftJoin(['s' => Stores::tableName()], 'BINARY s.id = BINARY oi.store_id')
                ->where(['between', 'DATE(oi.created_at)', $dateFrom, $dateTo])
                ->andWhere(['oi.status' => OilInventory::STATUS_ACCEPTED])
                ->andWhere(['>', 'oi.return_amount_kg', 0])
                ->orderBy(['s.name' => SORT_ASC, 'oi.created_at' => SORT_ASC]);

            $detailedData = $query->all();

            // Group by store for summary
            $groupedData = [];
            foreach ($detailedData as $row) {
                $storeId = $row['store_id'];
                if (!isset($groupedData[$storeId])) {
                    $groupedData[$storeId] = [
                        'terminal_name' => $row['terminal_name'],
                        'store_id' => $storeId,
                        'total_return_kg' => 0,
                        'total_return_liters' => 0,
                        'records' => []
                    ];
                }
                $groupedData[$storeId]['total_return_kg'] += $row['return_amount_kg'];
                $groupedData[$storeId]['total_return_liters'] += $row['return_amount'];
                $groupedData[$storeId]['records'][] = [
                    'created_at' => $row['created_at'],
                    'return_amount_kg' => $row['return_amount_kg'],
                    'return_amount' => $row['return_amount']
                ];
            }
            $data = array_values($groupedData);
        } else {
            // Original query for date range
            $query = (new Query())
                ->select([
                    's.name as terminal_name',
                    's.id as store_id',
                    'SUM(oi.return_amount_kg) as total_return_kg',
                    'SUM(oi.return_amount) as total_return_liters'
                ])
                ->from(['oi' => OilInventory::tableName()])
                ->leftJoin(['s' => Stores::tableName()], 'BINARY s.id = BINARY oi.store_id')
                ->where(['between', 'DATE(oi.created_at)', $dateFrom, $dateTo])
                ->andWhere(['oi.status' => OilInventory::STATUS_ACCEPTED])
                ->groupBy(['s.id', 's.name'])
                ->orderBy(['s.name' => SORT_ASC]);

            $data = $query->all();
        }

        $totalReturnKg = array_sum(array_column($data, 'total_return_kg'));
        $totalReturnLiters = array_sum(array_column($data, 'total_return_liters'));

        return $this->render('index', [
            'data' => $data,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'totalReturnKg' => $totalReturnKg,
            'totalReturnLiters' => $totalReturnLiters,
            'isSingleDay' => $isSingleDay,
        ]);
    }

    public function actionExportExcel()
    {
        $dateFrom = Yii::$app->request->get('date_from', date('Y-m-d', strtotime('-7 days')));
        $dateTo = Yii::$app->request->get('date_to', date('Y-m-d'));
        $isSingleDay = ($dateFrom === $dateTo);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title and headers
        $sheet->setCellValue('A1', 'Сводка возврата масла по филиалам');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Период: ' . Yii::$app->formatter->asDate($dateFrom, 'long') . ' - ' . Yii::$app->formatter->asDate($dateTo, 'long'));
        $sheet->mergeCells('A2:D2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Headers
        $row = 4;
        $sheet->setCellValue('A' . $row, 'Филиал');
        $sheet->setCellValue('B' . $row, 'Сумма возврата масла (кг)');
        $sheet->setCellValue('C' . $row, 'Сумма возврата масла (л)');

        if ($isSingleDay) {
            $sheet->setCellValue('D' . $row, 'Дата и время добавления');
        }

        // Style headers
        $headerRange = $isSingleDay ? 'A' . $row . ':D' . $row : 'A' . $row . ':C' . $row;
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Get data
        if ($isSingleDay) {
            $query = (new Query())
                ->select([
                    's.name as terminal_name',
                    's.id as store_id',
                    'oi.return_amount_kg',
                    'oi.return_amount',
                    'oi.created_at',
                    'oi.id'
                ])
                ->from(['oi' => OilInventory::tableName()])
                ->leftJoin(['s' => Stores::tableName()], 'BINARY s.id = BINARY oi.store_id')
                ->where(['between', 'DATE(oi.created_at)', $dateFrom, $dateTo])
                ->andWhere(['oi.status' => OilInventory::STATUS_ACCEPTED])
                ->andWhere(['>', 'oi.return_amount_kg', 0])
                ->orderBy(['s.name' => SORT_ASC, 'oi.created_at' => SORT_ASC]);

            $detailedData = $query->all();

            $groupedData = [];
            foreach ($detailedData as $rowData) {
                $storeId = $rowData['store_id'];
                if (!isset($groupedData[$storeId])) {
                    $groupedData[$storeId] = [
                        'terminal_name' => $rowData['terminal_name'],
                        'store_id' => $storeId,
                        'total_return_kg' => 0,
                        'total_return_liters' => 0,
                        'records' => []
                    ];
                }
                $groupedData[$storeId]['total_return_kg'] += $rowData['return_amount_kg'];
                $groupedData[$storeId]['total_return_liters'] += $rowData['return_amount'];
                $groupedData[$storeId]['records'][] = [
                    'created_at' => $rowData['created_at'],
                    'return_amount_kg' => $rowData['return_amount_kg'],
                    'return_amount' => $rowData['return_amount']
                ];
            }
            $data = array_values($groupedData);
        } else {
            $query = (new Query())
                ->select([
                    's.name as terminal_name',
                    's.id as store_id',
                    'SUM(oi.return_amount_kg) as total_return_kg',
                    'SUM(oi.return_amount) as total_return_liters'
                ])
                ->from(['oi' => OilInventory::tableName()])
                ->leftJoin(['s' => Stores::tableName()], 'BINARY s.id = BINARY oi.store_id')
                ->where(['between', 'DATE(oi.created_at)', $dateFrom, $dateTo])
                ->andWhere(['oi.status' => OilInventory::STATUS_ACCEPTED])
                ->andWhere(['>', 'oi.return_amount_kg', 0])
                ->groupBy(['s.id', 's.name'])
                ->orderBy(['s.name' => SORT_ASC]);

            $data = $query->all();

            // Преобразуем строковые значения в числа для периода
            foreach ($data as &$item) {
                $item['total_return_kg'] = (float)$item['total_return_kg'];
                $item['total_return_liters'] = (float)$item['total_return_liters'];
            }
            unset($item);
        }

        // Fill data
        $row = 5;
        $totalReturnKg = 0;
        $totalReturnLiters = 0;

        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item['terminal_name'] ?: 'Без названия');
            $sheet->setCellValue('B' . $row, round($item['total_return_kg'], 2));
            $sheet->setCellValue('C' . $row, round($item['total_return_liters'], 2));

            if ($isSingleDay && !empty($item['records'])) {
                $dateTimeList = [];
                foreach ($item['records'] as $record) {
                    $dateTimeList[] = Yii::$app->formatter->asDatetime($record['created_at'], 'php:d.m.Y H:i:s') .
                                     ' (' . round($record['return_amount_kg'], 2) . ' кг)';
                }
                $sheet->setCellValue('D' . $row, implode("\n", $dateTimeList));
                $sheet->getStyle('D' . $row)->getAlignment()->setWrapText(true);
            }

            $totalReturnKg += $item['total_return_kg'];
            $totalReturnLiters += $item['total_return_liters'];
            $row++;
        }

        // Total row
        $sheet->setCellValue('A' . $row, 'Итого:');
        $sheet->setCellValue('B' . $row, round($totalReturnKg, 2));
        $sheet->setCellValue('C' . $row, round($totalReturnLiters, 2));

        $totalRange = $isSingleDay ? 'A' . $row . ':D' . $row : 'A' . $row . ':C' . $row;
        $sheet->getStyle($totalRange)->getFont()->setBold(true);
        $sheet->getStyle($totalRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9EAD3');

        // Auto-size columns
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        if ($isSingleDay) {
            $sheet->getColumnDimension('D')->setWidth(40);
        }

        // Set borders
        $lastRow = $row;
        $borderRange = $isSingleDay ? 'A4:D' . $lastRow : 'A4:C' . $lastRow;
        $sheet->getStyle($borderRange)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Export
        $filename = 'Vozvrat_masla_' . $dateFrom . '_' . $dateTo . '.xlsx';

        // Очищаем буфер вывода перед отправкой файла
        if (ob_get_length() > 0) {
            ob_end_clean();
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        Yii::$app->response->headers->set('Cache-Control', 'max-age=0');

        $writer->save('php://output');
        Yii::$app->end();
    }
}