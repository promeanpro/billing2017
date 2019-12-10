<?php

namespace app\commands;

use yii\console\Controller;

/**
 *  Упровление демонами
 *
 *  Предоставляет интерфейс для управления демонами, их запуском, остановкой,
 *  получением текущего состостояния
 */
class DaemonsController extends Controller
{
    /**
     * @var string Дефолтный метод для запуска
     */
    public $defaultAction = "start";

    /**
     *  Запускает все сконфигурированные демоны в системе
     * @return void
     */
    public function actionStart()
    {
        \Yii::$app->daemonManager->start();
        echo "Starting daemons... ";
        if (\Yii::$app->daemonManager->waitForStart()) {
            echo "OK";
        } else {
            echo "ERROR";
        }
        echo "\n";
    }

    /**
     *  Останавливает все запущенные демоны
     * @return void
     */
    public function actionStop()
    {
        \Yii::$app->daemonManager->stop();
        echo "Stopping daemons... ";
        sleep(1);
        if (\Yii::$app->daemonManager->waitForStop()) {
            echo "OK";
        } else {
            echo "ERROR";
        }
        echo "\n";
    }

    /**
     *  Перезапускает всех демонов
     * @return void
     */
    public function actionRestart()
    {
        $this->actionStop();
        $this->actionStart();
    }

    /**
     *  Выводит информацию о запущенных и сконфигурированных демонах
     * @return void
     */
    public function actionStatus()
    {
        $daemons = \Yii::$app->daemonManager->status();
        ksort($daemons);
        $maxNameLength = 0;
        $out = [];
        foreach ($daemons as $name => $daemon) {
            if (strlen($name) > $maxNameLength) {
                $maxNameLength = strlen($name);
            }
            $out[] = [
                'name' => $name,
                'status' => $daemon['status'] ? 'working' : 'stopped',
            ];
        }
        foreach ($out as $row) {
            echo str_pad($row['name'], $maxNameLength + 3) . $row['status'] . "\n";
        }
    }
}
