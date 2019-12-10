<?php


namespace app\models\processes;


use app\components\messageBrokers\BrokerInterface;

use app\components\RPCRequest;
use app\models\commands\AbstractCommand;
use PhpAmqpLib\Channel\AMQPChannel;
use Yii;

/**
 * Этот класс формирует результирующую задачу для commands/EventHandler
 * Class CommandInvoker
 */
class CommandInvoker
{
    /**
     * Создает команду
     * @param AbstractCommand $transaction
     */
    public function create($transaction)
    {
        /** @var RPCRequest $rpcRequest */
        $rpcRequest = Yii::$app->rpcRequest;
        return $rpcRequest->sendRequest($transaction::getCommandName(),$transaction::className(),$transaction->getAttributes());
    }

    /**
     * Выполняет команду
     * @param $operation
     * @param $commandModelValues
     * @return array|bool
     */
    public static function invoke($operation, $commandModelValues)
    {
        /** @var AbstractCommand $command */
        $command = new $operation;
        $command->setAttributes((array) $commandModelValues);

        return $command->run();
    }
}