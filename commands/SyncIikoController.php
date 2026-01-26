<?php

namespace app\commands;

use app\models\Departments;
use app\models\Groups;
use app\models\Iiko;
use app\models\Products;
use app\models\Stores;
use app\models\Suppliers;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Синхронизация данных с iiko
 */
class SyncIikoController extends Controller
{
    /**
     * @var int количество дней для синхронизации цен (по умолчанию 1)
     */
    public $days = 1;

    /**
     * @var bool подробный вывод
     */
    public $verbose = false;

    /**
     * {@inheritdoc}
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'days',
            'verbose',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'v' => 'verbose',
            'd' => 'days',
        ]);
    }

    /**
     * Полная синхронизация данных с iiko
     *
     * Синхронизирует: подразделения, поставщиков, склады, продукты, группы и входящие цены.
     *
     * Использование:
     *   php yii sync-iiko/index
     *   php yii sync-iiko/index --days=7
     *   php yii sync-iiko/index -v (подробный вывод)
     *
     * @return int код завершения
     */
    public function actionIndex()
    {
        set_time_limit(0);
        error_reporting(E_ALL);
        ini_set('memory_limit', -1);
        ini_set('display_errors', 1);

        $this->stdout("\n");
        $this->stdout("╔══════════════════════════════════════════════════════════════╗\n", Console::FG_CYAN);
        $this->stdout("║           СИНХРОНИЗАЦИЯ ДАННЫХ С IIKO                        ║\n", Console::FG_CYAN);
        $this->stdout("╚══════════════════════════════════════════════════════════════╝\n", Console::FG_CYAN);
        $this->stdout("\n");

        $startTime = microtime(true);

        // Авторизация
        $this->stdout("[1/7] Авторизация в iiko... ", Console::FG_YELLOW);
        $iiko = new Iiko();
        if (!$iiko->auth()) {
            $this->stdout("ОШИБКА\n", Console::FG_RED, Console::BOLD);
            $this->stderr("Не удалось авторизоваться в iiko\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        $this->stdout("OK\n", Console::FG_GREEN, Console::BOLD);

        // Синхронизация подразделений
        $this->stdout("[2/7] Синхронизация подразделений (departments)... ", Console::FG_YELLOW);
        $countBefore = Departments::find()->count();
        $result = $iiko->departments();
        $countAfter = Departments::find()->count();
        $this->printSyncResult($result, $countBefore, $countAfter);

        // Синхронизация поставщиков
        $this->stdout("[3/7] Синхронизация поставщиков (suppliers)... ", Console::FG_YELLOW);
        $countBefore = Suppliers::find()->count();
        $result = $iiko->suppliers();
        $countAfter = Suppliers::find()->count();
        $this->printSyncResult($result, $countBefore, $countAfter);

        // Синхронизация складов
        $this->stdout("[4/7] Синхронизация складов (stores)... ", Console::FG_YELLOW);
        $countBefore = Stores::find()->count();
        $result = $iiko->stores();
        $countAfter = Stores::find()->count();
        $this->printSyncResult($result, $countBefore, $countAfter);

        // Синхронизация продуктов
        $this->stdout("[5/7] Синхронизация продуктов (products)... ", Console::FG_YELLOW);
        $countBefore = Products::find()->count();
        $result = $iiko->products();
        $countAfter = Products::find()->count();
        $this->printSyncResult($result, $countBefore, $countAfter);

        // Синхронизация групп
        $this->stdout("[6/7] Синхронизация групп (groups)... ", Console::FG_YELLOW);
        $countBefore = Groups::find()->count();
        $result = $iiko->groups();
        $countAfter = Groups::find()->count();
        $this->printSyncResult($result, $countBefore, $countAfter);

        // Синхронизация входящих цен
        $now = date('Y-m-d');
        $start = date('Y-m-d', strtotime($now . " -{$this->days} days"));
        $this->stdout("[7/7] Синхронизация входящих цен ({$start} - {$now})... ", Console::FG_YELLOW);
        $result = $iiko->incomingPrices($start, $now);
        if ($result === true) {
            $this->stdout("OK\n", Console::FG_GREEN, Console::BOLD);
        } else {
            $this->stdout("ОШИБКА\n", Console::FG_RED, Console::BOLD);
            $this->printErrors($result);
        }

        // Итоговая статистика
        $this->stdout("\n");
        $this->stdout("Статистика таблиц:\n", Console::FG_CYAN, Console::BOLD);
        $this->printTableStats('departments', Departments::find()->count());
        $this->printTableStats('suppliers', Suppliers::find()->count());
        $this->printTableStats('stores', Stores::find()->count());
        $this->printTableStats('products', Products::find()->count());
        $this->printTableStats('groups', Groups::find()->count());

        // Итоги
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $this->stdout("\n");
        $this->stdout("══════════════════════════════════════════════════════════════\n", Console::FG_CYAN);
        $this->stdout("Синхронизация завершена за {$duration} сек.\n", Console::FG_GREEN, Console::BOLD);
        $this->stdout("══════════════════════════════════════════════════════════════\n", Console::FG_CYAN);
        $this->stdout("\n");

        return ExitCode::OK;
    }

    /**
     * Вывод результата синхронизации с количеством записей
     * @param mixed $result
     * @param int $countBefore
     * @param int $countAfter
     */
    private function printSyncResult($result, $countBefore, $countAfter)
    {
        if ($result === true) {
            if ($countAfter == 0) {
                $this->stdout("ПУСТО", Console::FG_RED, Console::BOLD);
                $this->stdout(" (было: {$countBefore}, стало: 0)\n", Console::FG_RED);
            } else {
                $this->stdout("OK", Console::FG_GREEN, Console::BOLD);
                $diff = $countAfter - $countBefore;
                $diffStr = $diff >= 0 ? "+{$diff}" : "{$diff}";
                $this->stdout(" ({$countAfter} записей, {$diffStr})\n");
            }
        } else {
            $this->stdout("ОШИБКА\n", Console::FG_RED, Console::BOLD);
            $this->printErrors($result);
        }
    }

    /**
     * Вывод статистики таблицы
     * @param string $table
     * @param int $count
     */
    private function printTableStats($table, $count)
    {
        $paddedTable = str_pad($table, 15);
        if ($count == 0) {
            $this->stdout("  {$paddedTable}: ", Console::FG_WHITE);
            $this->stdout("{$count} (ПУСТО!)\n", Console::FG_RED, Console::BOLD);
        } else {
            $this->stdout("  {$paddedTable}: {$count}\n", Console::FG_WHITE);
        }
    }

    /**
     * Вывод ошибок
     * @param mixed $errors
     */
    private function printErrors($errors)
    {
        if (is_array($errors)) {
            foreach ($errors as $field => $error) {
                $this->stderr("  - {$field}: {$error}\n", Console::FG_RED);
            }
        } elseif (is_string($errors)) {
            $this->stderr("  - {$errors}\n", Console::FG_RED);
        }
    }
}
