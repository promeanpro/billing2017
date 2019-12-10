<?php

namespace app\components\daemons;

/**
 * Этот демон разибирает запросы с компонентна messageQueue
 * Class DequeueDaemon
 * @package app\components\daemons
 */
class EventDaemon extends BaseDaemon
{
    /**
     * @var array Список задач к выполнению
     *  Формируется в формате хэш-массива, где ключ - имя точки обмена,
     *  значение ( или значения в массиве ) - названия методов для выполнения.
     *  Названия методов передаются в формате класс::метод, где
     *  класс - название контроллера команды,
     *  метод - названия действия.
     *  К примеру:
     *  ```
     *  $tasks = [
     *    'notify' => [
     *      'notify::remember',
     *      'notify::show'
     *    ],
     *    'dropPasswords' => 'tasks::dropPassword'
     *  ]
     *  ```
     */
    public $tasks = [];

    /**
     *  Подписаться на сообщения из очереди перед началом выполнения
     */
    protected function start()
    {
        parent::start();
        foreach ($this->tasks as $exchangeName => $actions) {
            if (!is_array($actions)) {
                $actions = [$actions];
            }
            foreach ($actions as $action) {
                \Yii::$app->messageQueue->subscribe(
                    $exchangeName,
                    $action,
                    function ($data) use ($action) {
                        return $this->processMessage($action, $data);
                    }
                );
            }
        }
    }

    /**
     *  Ожидание новых сообщений в очереди
     *
     *  Запускается каждую итерацию основного цикла
     */
    protected function process()
    {
        \Yii::$app->messageQueue->waitForMessages(10);
    }

    /**
     *  Обработка сообщения из очереди
     * @param string $action действие, которое требуется выполнить
     * @param string $data полученное сообщение из кролика
     * @return bool успешность выполнения действия
     */
    protected function processMessage($action, $data)
    {
        $action = explode("::", $action);

        $data['system'] = $this->daemonName . ':' . $this->daemonNumber;
        $command = 'php ' . \Yii::getAlias('@app/yii') . " "
            . implode("/", $action) . " "
            . base64_encode(json_encode($data));
        exec($command, $output, $return);

        $this->log('execute: ' . $command . ' result= ' . print_r($return, true));

        return $return;
    }
}
