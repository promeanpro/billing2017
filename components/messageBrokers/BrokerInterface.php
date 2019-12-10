<?php


namespace app\components\messageBrokers;

/**
 * Interface BrokerInterface
 * @package app\components\messageBrokers
 */
interface BrokerInterface
{
    /**
     * Опубликовать сообщение
     * @param $exchange
     * @param $message
     * @param array $params
     * @return mixed
     */
    public function publish($exchange, $message, $params = []);

    /**
     *  Запустить ожидание сообщений из очередей
     *
     *  Этот метод ожидает любого события, на который была осуществлена подписка черед self::subscribe
     *  @return void
     */
    public function waitForMessages();

}