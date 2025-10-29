<?php

namespace app\controllers;

use Yii;
use app\models\OilInventory;
use app\models\User;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\db\Query;
use app\components\AccessRule;

/**
 * OilInventoryDashboardController implements analytics dashboard for OilInventory model.
 */
class OilInventoryDashboardController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            "access" => [
                "class" => AccessControl::class,
                "only" => ["*"],
                "ruleConfig" => [
                    "class" => AccessRule::class,
                ],
                "rules" => [
                    [
                        "allow" => true,
                        "roles" => [User::ROLE_ADMIN, User::ROLE_OFFICE_MANAGER],
                    ],
                ],
            ],
        ];
    }

    /**
     * Dashboard index page
     * @return mixed
     */
    public function actionIndex()
    {
        $currentUser = User::findOne(Yii::$app->user->id);
        $isManager =
            $currentUser &&
            ($currentUser->getIsAdmin() || $currentUser->getIsManager());

        // Получаем параметры фильтров из запроса
        $storeFilter = Yii::$app->request->get("store_id", null);
        $dateFrom = Yii::$app->request->get("date_from", date("Y-m-01")); // Начало текущего месяца по умолчанию
        $dateTo = Yii::$app->request->get("date_to", date("Y-m-d")); // Сегодня по умолчанию

        // Для обычных пользователей ограничиваем по их storeId
        if (!$isManager) {
            $currentUserStoreId = User::getStoreId();
            if (empty($currentUserStoreId)) {
                Yii::$app->session->setFlash(
                    "error",
                    "У вас не назначен магазин. Обратитесь к администратору.",
                );
                return $this->redirect(["/oil-inventory/index"]);
            }
            $storeFilter = $currentUserStoreId;
        }

        // Получаем список всех магазинов для фильтра (только для менеджеров)
        $storesList = [];
        if ($isManager) {
            $storesList = $this->getStoresList();
        }

        // Проверяем, есть ли записи с учетом фильтров
        $totalRecords = $this->getTotalRecords(
            $storeFilter,
            $dateFrom,
            $dateTo,
        );

        // Получаем данные для виджетов
        $data = [
            "isManager" => $isManager,
            "storeFilter" => $storeFilter,
            "dateFrom" => $dateFrom,
            "dateTo" => $dateTo,
            "storesList" => $storesList,
            "totalRecords" => $totalRecords,
            "statusStats" =>
                $totalRecords > 0
                    ? $this->getStatusStats($storeFilter, $dateFrom, $dateTo)
                    : [],
            "monthlyStats" =>
                $totalRecords > 0
                    ? $this->getMonthlyStats($storeFilter, $dateFrom, $dateTo)
                    : [],
            "averageConsumption" =>
                $totalRecords > 0
                    ? $this->getAverageConsumption(
                        $storeFilter,
                        $dateFrom,
                        $dateTo,
                    )
                    : [
                        "avg_apparatus" => 0,
                        "avg_new_oil" => 0,
                        "avg_evaporation" => 0,
                        "avg_return" => 0,
                        "avg_total_consumption" => 0,
                    ],
            "weeklyTrend" =>
                $totalRecords > 0
                    ? $this->getWeeklyTrend($storeFilter, $dateFrom, $dateTo)
                    : [],
            "topConsumptionDays" =>
                $totalRecords > 0
                    ? $this->getTopConsumptionDays(
                        $storeFilter,
                        $dateFrom,
                        $dateTo,
                    )
                    : [],
            "recentRecords" =>
                $totalRecords > 0
                    ? $this->getRecentRecords($storeFilter, $dateFrom, $dateTo)
                    : [],
            "wastageAnalysis" =>
                $totalRecords > 0
                    ? $this->getWastageAnalysis(
                        $storeFilter,
                        $dateFrom,
                        $dateTo,
                    )
                    : [
                        "avg_evaporation" => 0,
                        "max_evaporation" => 0,
                        "min_evaporation" => 0,
                        "total_evaporation" => 0,
                        "records_count" => 0,
                    ],
            "efficiencyMetrics" =>
                $totalRecords > 0
                    ? $this->getEfficiencyMetrics(
                        $storeFilter,
                        $dateFrom,
                        $dateTo,
                    )
                    : [
                        "evaporation_percentage" => 0,
                        "apparatus_percentage" => 0,
                        "new_oil_percentage" => 0,
                        "return_percentage" => 0,
                        "income_efficiency" => 0,
                    ],
            "unfilledRecords" =>
                $totalRecords > 0
                    ? $this->getUnfilledRecords(
                        $storeFilter,
                        $dateFrom,
                        $dateTo,
                    )
                    : [],
        ];

        return $this->render("index", $data);
    }

    /**
     * Получить список магазинов, привязанных к пользователям с oil_tg_id
     */
    private function getStoresList()
    {
        // Сначала получаем уникальные store_id пользователей с заполненным oil_tg_id
        $storeIds = (new Query())
            ->select(["store_id"])
            ->from("user")
            ->where(["not", ["oil_tg_id" => null]])
            ->andWhere(["not", ["oil_tg_id" => ""]])
            ->andWhere(["not", ["store_id" => null]])
            ->andWhere(["not", ["store_id" => ""]])
            ->distinct()
            ->column();

        // Если нет магазинов, возвращаем пустой массив
        if (empty($storeIds)) {
            return [];
        }

        // Теперь получаем информацию о магазинах
        return (new Query())
            ->select(["id", "name"])
            ->from("stores")
            ->where(["in", "id", $storeIds])
            ->orderBy(["name" => SORT_ASC])
            ->all();
    }

    /**
     * Получить общее количество записей
     */
    private function getTotalRecords(
        $storeFilter = null,
        $dateFrom = null,
        $dateTo = null
    ) {
        $query = OilInventory::find();

        if ($storeFilter) {
            $query->where(["store_id" => $storeFilter]);
        }

        if ($dateFrom) {
            $query->andWhere([">=", "DATE(created_at)", $dateFrom]);
        }

        if ($dateTo) {
            $query->andWhere(["<=", "DATE(created_at)", $dateTo]);
        }

        return $query->count();
    }

    /**
     * Получить статистику по статусам
     */
    private function getStatusStats(
        $storeFilter = null,
        $dateFrom = null,
        $dateTo = null
    ) {
        $query = OilInventory::find()
            ->select(["status", "COUNT(*) as count"])
            ->groupBy("status");

        if ($storeFilter) {
            $query->andWhere(["store_id" => $storeFilter]);
        }

        if ($dateFrom) {
            $query->andWhere([">=", "DATE(created_at)", $dateFrom]);
        }

        if ($dateTo) {
            $query->andWhere(["<=", "DATE(created_at)", $dateTo]);
        }

        return $query->asArray()->all();
    }

    /**
     * Получить статистику по месяцам
     */
    private function getMonthlyStats(
        $storeFilter = null,
        $dateFrom = null,
        $dateTo = null
    ) {
        $query = (new Query())
            ->select([
                "YEAR(created_at) as year",
                "MONTH(created_at) as month",
                "COUNT(*) as records_count",
                "AVG(closing_balance) as avg_closing",
                "SUM(income) as total_income",
                "SUM(apparatus + new_oil + evaporation + return_amount) as total_consumption",
            ])
            ->from("oil_inventory")
            ->groupBy(["YEAR(created_at)", "MONTH(created_at)"])
            ->orderBy(["year" => SORT_DESC, "month" => SORT_DESC])
            ->limit(12);

        if ($storeFilter) {
            $query->andWhere(["store_id" => $storeFilter]);
        }

        if ($dateFrom) {
            $query->andWhere([">=", "DATE(created_at)", $dateFrom]);
        }

        if ($dateTo) {
            $query->andWhere(["<=", "DATE(created_at)", $dateTo]);
        }

        return $query->all();
    }

    /**
     * Получить средний расход
     */
    private function getAverageConsumption(
        $storeFilter = null,
        $dateFrom = null,
        $dateTo = null
    ) {
        $query = (new Query())
            ->select([
                "AVG(apparatus) as avg_apparatus",
                "AVG(new_oil) as avg_new_oil",
                "AVG(evaporation) as avg_evaporation",
                "AVG(return_amount) as avg_return",
                "AVG(apparatus + new_oil + evaporation + return_amount) as avg_total_consumption",
            ])
            ->from("oil_inventory")
            ->andWhere(["!=", "status", OilInventory::STATUS_NEW]);

        if ($storeFilter) {
            $query->andWhere(["store_id" => $storeFilter]);
        }

        if ($dateFrom) {
            $query->andWhere([">=", "DATE(created_at)", $dateFrom]);
        }

        if ($dateTo) {
            $query->andWhere(["<=", "DATE(created_at)", $dateTo]);
        }

        $result = $query->one();

        return $result ?: [
                "avg_apparatus" => 0,
                "avg_new_oil" => 0,
                "avg_evaporation" => 0,
                "avg_return" => 0,
                "avg_total_consumption" => 0,
            ];
    }

    /**
     * Получить тренд за период
     */
    private function getWeeklyTrend(
        $storeFilter = null,
        $dateFrom = null,
        $dateTo = null
    ) {
        $query = (new Query())
            ->select([
                "DATE(created_at) as date",
                "closing_balance",
                "(apparatus + new_oil + evaporation + return_amount) as total_consumption",
                "income as total_income",
            ])
            ->from("oil_inventory")
            ->orderBy(["created_at" => SORT_ASC]);

        if ($storeFilter) {
            $query->andWhere(["store_id" => $storeFilter]);
        }

        // Если не указаны даты, берем последние 7 дней
        if (!$dateFrom && !$dateTo) {
            $query->andWhere([
                ">=",
                "DATE(created_at)",
                date("Y-m-d", strtotime("-7 days")),
            ]);
        } else {
            if ($dateFrom) {
                $query->andWhere([">=", "DATE(created_at)", $dateFrom]);
            }

            if ($dateTo) {
                $query->andWhere(["<=", "DATE(created_at)", $dateTo]);
            }
        }

        return $query->all();
    }

    /**
     * Получить дни с наибольшим расходом
     */
    private function getTopConsumptionDays(
        $storeFilter = null,
        $dateFrom = null,
        $dateTo = null
    ) {
        $query = (new Query())
            ->select([
                "DATE(created_at) as date",
                "(apparatus + new_oil + evaporation + return_amount) as total_consumption",
                "apparatus",
                "new_oil",
                "evaporation",
                "return_amount",
            ])
            ->from("oil_inventory")
            ->andWhere(["!=", "status", OilInventory::STATUS_NEW])
            ->orderBy([
                "(apparatus + new_oil + evaporation + return_amount)" => SORT_DESC,
            ])
            ->limit(10);

        if ($storeFilter) {
            $query->andWhere(["store_id" => $storeFilter]);
        }

        if ($dateFrom) {
            $query->andWhere([">=", "DATE(created_at)", $dateFrom]);
        }

        if ($dateTo) {
            $query->andWhere(["<=", "DATE(created_at)", $dateTo]);
        }

        return $query->all();
    }

    /**
     * Получить последние записи
     */
    private function getRecentRecords(
        $storeFilter = null,
        $dateFrom = null,
        $dateTo = null
    ) {
        $query = OilInventory::find()
            ->orderBy(["created_at" => SORT_DESC])
            ->limit(10);

        if ($storeFilter) {
            $query->andWhere(["store_id" => $storeFilter]);
        }

        if ($dateFrom) {
            $query->andWhere([">=", "DATE(created_at)", $dateFrom]);
        }

        if ($dateTo) {
            $query->andWhere(["<=", "DATE(created_at)", $dateTo]);
        }

        return $query->all();
    }

    /**
     * Анализ потерь (испарение)
     */
    private function getWastageAnalysis(
        $storeFilter = null,
        $dateFrom = null,
        $dateTo = null
    ) {
        $query = (new Query())
            ->select([
                "AVG(evaporation) as avg_evaporation",
                "MAX(evaporation) as max_evaporation",
                "MIN(evaporation) as min_evaporation",
                "SUM(evaporation) as total_evaporation",
                "COUNT(*) as records_count",
            ])
            ->from("oil_inventory")
            ->andWhere(["!=", "status", OilInventory::STATUS_NEW]);

        if ($storeFilter) {
            $query->andWhere(["store_id" => $storeFilter]);
        }

        if ($dateFrom) {
            $query->andWhere([">=", "DATE(created_at)", $dateFrom]);
        }

        if ($dateTo) {
            $query->andWhere(["<=", "DATE(created_at)", $dateTo]);
        }

        return $query->one();
    }

    /**
     * Метрики эффективности
     */
    private function getEfficiencyMetrics(
        $storeFilter = null,
        $dateFrom = null,
        $dateTo = null
    ) {
        $query = (new Query())
            ->select([
                "AVG(CASE WHEN (apparatus + new_oil + evaporation + return_amount) > 0 THEN evaporation / (apparatus + new_oil + evaporation + return_amount) * 100 ELSE 0 END) as evaporation_percentage",
                "AVG(CASE WHEN (apparatus + new_oil + evaporation + return_amount) > 0 THEN apparatus / (apparatus + new_oil + evaporation + return_amount) * 100 ELSE 0 END) as apparatus_percentage",
                "AVG(CASE WHEN (apparatus + new_oil + evaporation + return_amount) > 0 THEN new_oil / (apparatus + new_oil + evaporation + return_amount) * 100 ELSE 0 END) as new_oil_percentage",
                "AVG(CASE WHEN (apparatus + new_oil + evaporation + return_amount) > 0 THEN return_amount / (apparatus + new_oil + evaporation + return_amount) * 100 ELSE 0 END) as return_percentage",
                "AVG(CASE WHEN (apparatus + new_oil + evaporation + return_amount) > 0 THEN income / (apparatus + new_oil + evaporation + return_amount) * 100 ELSE 0 END) as income_efficiency",
            ])
            ->from("oil_inventory")
            ->andWhere(["!=", "status", OilInventory::STATUS_NEW]);

        if ($storeFilter) {
            $query->andWhere(["store_id" => $storeFilter]);
        }

        if ($dateFrom) {
            $query->andWhere([">=", "DATE(created_at)", $dateFrom]);
        }

        if ($dateTo) {
            $query->andWhere(["<=", "DATE(created_at)", $dateTo]);
        }

        $result = $query->one();

        return $result ?: [
                "evaporation_percentage" => 0,
                "apparatus_percentage" => 0,
                "new_oil_percentage" => 0,
                "return_percentage" => 0,
                "income_efficiency" => 0,
            ];
    }

    /**
     * Получить незаполненные записи (где apparatus = 0)
     */
    private function getUnfilledRecords(
        $storeFilter = null,
        $dateFrom = null,
        $dateTo = null
    ) {
        $query = OilInventory::find()
            ->where(["apparatus" => 0])
            ->orderBy(["created_at" => SORT_DESC])
            ->limit(20);

        if ($storeFilter) {
            $query->andWhere(["store_id" => $storeFilter]);
        }

        if ($dateFrom) {
            $query->andWhere([">=", "DATE(created_at)", $dateFrom]);
        }

        if ($dateTo) {
            $query->andWhere(["<=", "DATE(created_at)", $dateTo]);
        }

        return $query->all();
    }
}
