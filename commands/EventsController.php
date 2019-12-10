<?php

namespace app\commands;

use app\models\processes\CommandInvoker;
use yii\console\Controller;

/**
 *  Контроллер для обработки событий
 */
class EventsController extends Controller
{
    /**
    * @var string Дефолтный метод для запуска
    */
    public $defaultAction = "handle";
    /**
     * Обработка событий
     * @param $data
     * @return array|bool
     */
    public function actionHandle($data)
    {
        $data = json_decode(base64_decode($data));

        $values = $data->data;

        $result = CommandInvoker::invoke($data->operation, $values);

        return $result;
    }
}
