<?php


namespace app\models\processes;


interface ReservationsInterface
{
    /**
     * Получить список зарезерированных транзакций
     * @param $userId
     * @return static[]
     */
    public static function get($userId);

    /**
     * Зарезервировать деньги
     * @param $userId
     * @param $amount
     * @return boolean
     */
    public static function reserve($userId, $amount);

    /**
     * Отклонить существующее резервирование
     * @param $transactionId
     * @return bool
     */
    public static function decline($transactionId);

    /**
     * Подтвердить существующее резервирование
     * @param $transactionId
     * @param $amount
     * @return boolean
     */
    public static function approve($transactionId);

}