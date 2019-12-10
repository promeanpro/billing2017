<?php
namespace app\components\messageBrokers;


use OutOfBoundsException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

use yii\base\Component;

/**
 * Компонент для работы с рэбитом
 * Class RabbitMQ
 * @package app\components
 */
class RabbitMQ extends Component implements BrokerInterface
{
    const QUEUE = "msgs";

    /**
     * @var string хост для подключения
     */
    public $host = "127.0.0.1";
    /**
     * @var string порт для подключения
     */
    public $port = "5672";
    /**
     * @var string логин для подключения
     */
    public $login = 'guest';
    /**
     * @var string пароль для подключения
     */
    public $password = 'guest';

    /**
     * @var string префикс для точек обмена
     */
    public $exchangePrefix = '';
    /**
     * @var string префикс для очередей
     */
    public $queuePrefix = '';

    public $timeout;

    /***
     * @var AMQPConnection Класс соединения с менеджером очередей
     */
    protected $connect;

    /***
     * @var AMQPChannel Класс соединения с менеджером очередей
     */
    protected $channel;

    public function getConnection()
    {
        if (empty($this->connect)) {
            $this->channel = $this->channel();
        }

        return $this->connect;
    }

    private function sendCallback($message, $replyTo, $params)
    {
        $ch = $this->channel();

        $msg = new AMQPMessage(json_encode($message), $params);
        $ch->basic_publish($msg, '', $replyTo);
    }

    /**
     * Добавить сообщение в очередь
     * @param $message
     * @param $params
     */
    public function publish($exchange, $message, $params = [])
    {
        $this->prepare($exchange);
        $ch = $this->channel();

        $params['delivery_mode'] = AMQPMessage::DELIVERY_MODE_PERSISTENT;

        $msg = new AMQPMessage(json_encode($message), $params);
        $ch->basic_publish($msg, $exchange);
    }

    /**
     *  Подписаться на сообщения из очереди
     *
     * @param string $queue название очереди, из которой ожидается сообщение
     * @param callable $action функция, которую нужно вызвать для обработки сообщения
     * @return void
     */
    public function subscribe($exchange = null, $queue, callable $action)
    {
        \Yii::trace("\nDO SUBSCRIBE!\n", 'eventHandler');

        if ($exchange != null) {
            $this->prepare($exchange, $queue);
        }

        $ch = $this->channel();
        $ch->basic_consume(
            $queue, // queue name
            '',     // consumer tag
            false,  // no_local
            false,  // no_ack
            false,  // exclusive
            false,  // nowait
            function ($msg) use ($action) { // callback
                return $this->processQueueMessage($msg, $action);
            }
        );
    }

    /**
     *  Запустить ожидание сообщений из очередей
     *
     *  Этот метод ожидает любого события, на который была осуществлена подписка черед self::subscribe
     * @return void
     */
    public function waitForMessages()
    {
        $ch = $this->channel();
        try {
            $ch->wait(null, true, $this->timeout);
        } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
            return; // Все хорошо, дождались таймаута
        } catch (\yii\base\ErrorException $e) {
            if (strpos($e->getMessage(), 'Interrupted system call') !== false) {
                /// @bug AMQPT не умеет корректно обрабатывать сигналы прерываний
                return;
            }
            throw $e;
        }
        return;
    }

    /**
     *  Обработчик сообщения из очереди
     *
     *  Устанавливается в методе self::subscribe
     *  Запускается в методе self::waitForMessages
     *
     * @param
     */
    protected function processQueueMessage(\PhpAmqpLib\Message\AMQPMessage $msg, callable $action)
    {
        $ch = $this->channel();

        $result = null;
        if (isset($msg->delivery_info)) {

            $result = $action(json_decode($msg->body, true));


            if (!empty($result)) {
                $ch->basic_ack($msg->delivery_info['delivery_tag']);
            } else {
                $ch->basic_nack($msg->delivery_info['delivery_tag'], false, false);
            }


            try {
                $this->sendCallback(
                    $result,
                    $msg->get('reply_to'),
                    [
                        'correlation_id' => $msg->get('correlation_id')
                    ]
                );
            } catch (OutOfBoundsException $e) {
                //there is no correlation_id or reply_to
                //just skip
            }
        }

        return $result;
    }

    /**
     * @return AMQPConnection
     */
    protected function connect()
    {
        if (!$this->connect) {
            $this->connect = new AMQPConnection(
                $this->host,
                $this->port,
                $this->login,
                $this->password
            );
        }
        return $this->connect;
    }

    /**
     * @return AMQPChannel
     */
    protected function channel()
    {
        if (!$this->channel) {
            $this->channel = $this->connect()->channel();
        }
        return $this->channel;
    }

    /**
     *  Создает на сервере точки обмена и очереди
     *
     *  Так же добавляет префикс к их именам, если он еще не был добавлен ранее
     *
     * @param string $exchangeName название точки обмена
     * @param string $queueName название очереди
     */
    protected function prepare(&$exchangeName, &$queueName = null)
    {
        if ($this->exchangePrefix && strpos($exchangeName, $this->exchangePrefix) !== 0) {
            $exchangeName = $this->exchangePrefix . $exchangeName;
        }
        $ch = $this->channel();
        /*
            name: $exchange
            type: direct
            passive: false
            durable: true // the exchange will survive server restarts
            auto_delete: false //the exchange won't be deleted once the channel is closed.
        */
        $ch->exchange_declare($exchangeName, 'direct', false, true, false);
        if ($queueName) {
            if ($this->queuePrefix && strpos($queueName, $this->queuePrefix) !== 0) {
                $queueName = $this->queuePrefix . $queueName;
            }
            /*
                name: $queue
                passive: false
                durable: true // the queue will survive server restarts
                exclusive: false // the queue can be accessed in other channels
                auto_delete: false //the queue won't be deleted once the channel is closed.
            */
            $ch->queue_declare($queueName, false, true, false, false);
            $ch->queue_bind($queueName, $exchangeName);
        }
    }
}
