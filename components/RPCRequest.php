<?php


namespace app\components;


use app\components\messageBrokers\BrokerInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use Yii;
use yii\base\Component;

class RPCRequest extends Component
{
    private $connection;
    /** @var AMQPChannel channel */
    private $channel;
    private $callbackQueue;
    private $response = null;
    private $correlationId;

    /**
     * Обработчик ответа от сервера для клиента
     * @param $rep
     */
    public function onResponse($rep)
    {
        if ($rep->get('correlation_id') == $this->correlationId) {
            $this->response = $rep->body;
        }
    }

    private function prepare()
    {
        $mq = \Yii::$app->messageQueue;
        $this->connection = $mq->getConnection();
        $this->channel = $this->connection->channel();
        list($this->callbackQueue, ,) = $this->channel->queue_declare(
            "", false, false, true, false);
        $this->channel->basic_consume(
            $this->callbackQueue, '', false, false, false, false,
            [$this, 'onResponse']);

        $this->response = null;
        $this->correlationId = uniqid("", true);
    }

    /**
     * Отправка задачи на сервер
     * @param $exchange
     * @param $operation
     * @param $data
     * @return null
     */
    public function sendRequest($exchange, $operation, $data)
    {
        $this->prepare();

        /** @var BrokerInterface $mq */
        $mq = Yii::$app->messageQueue;
        $mq->publish(
            $exchange,
            [
                'operation' => $operation,
                'data' => $data
            ],
            [
                'correlation_id' => $this->correlationId,
                'reply_to' => $this->callbackQueue
            ]
        );

        while ($this->response === null) {
            $this->channel->wait();
        }

        return $this->response;
    }
}