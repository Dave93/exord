<?php

namespace app\commands;

use app\models\Iiko;
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
     * {@inheritdoc}
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'days',
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
     *
     * @return int код завершения
     */
    public function actionIndex()
    {
        set_time_limit(0);
        error_reporting(E_ERROR);
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
        $result = $iiko->departments();
        if ($result === true) {
            $this->stdout("OK\n", Console::FG_GREEN, Console::BOLD);
        } else {
            $this->stdout("ОШИБКА\n", Console::FG_RED, Console::BOLD);
            $this->printErrors($result);
        }

        // Синхронизация поставщиков
        $this->stdout("[3/7] Синхронизация поставщиков (suppliers)... ", Console::FG_YELLOW);
        $result = $iiko->suppliers();
        if ($result === true) {
            $this->stdout("OK\n", Console::FG_GREEN, Console::BOLD);
        } else {
            $this->stdout("ОШИБКА\n", Console::FG_RED, Console::BOLD);
            $this->printErrors($result);
        }

        // Синхронизация складов
        $this->stdout("[4/7] Синхронизация складов (stores)... ", Console::FG_YELLOW);
        $result = $iiko->stores();
        if ($result === true) {
            $this->stdout("OK\n", Console::FG_GREEN, Console::BOLD);
        } else {
            $this->stdout("ОШИБКА\n", Console::FG_RED, Console::BOLD);
            $this->printErrors($result);
        }

        // Синхронизация продуктов
        $this->stdout("[5/7] Синхронизация продуктов (products)... ", Console::FG_YELLOW);
        $result = $iiko->products();
        if ($result === true) {
            $this->stdout("OK\n", Console::FG_GREEN, Console::BOLD);
        } else {
            $this->stdout("ОШИБКА\n", Console::FG_RED, Console::BOLD);
            $this->printErrors($result);
        }

        // Синхронизация групп
        $this->stdout("[6/7] Синхронизация групп (groups)... ", Console::FG_YELLOW);
        $result = $iiko->groups();
        if ($result === true) {
            $this->stdout("OK\n", Console::FG_GREEN, Console::BOLD);
        } else {
            $this->stdout("ОШИБКА\n", Console::FG_RED, Console::BOLD);
            $this->printErrors($result);
        }

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
