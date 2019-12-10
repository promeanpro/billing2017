<?php
namespace app\models\processes;
use app\models\processes\strategy\ReservationsStoredProcedureStrategy;

/**
 * Класс отвечает за имплементацию процессов по сущности Reservations
 * Class Account
 * @package app\models\processes
 */
class Reservations implements ReservationsInterface
{
    /** @var  ReservationsInterface delegate */
    private static $delegate = ReservationsStoredProcedureStrategy::class;

    /**
     * @param ReservationsInterface $delegate
     */
    public function setDelegate($delegate)
    {
        static::$delegate = $delegate;
    }

    /**
     * Получить список зарезерированных транзакций
     * @param $userId
     * @return static[]
     */
    public static function get($userId)
    {
        $delegate = self::$delegate;
        return $delegate::get($userId);
    }

    /**
     * Зарезервировать деньги
     * @param $userId
     * @param $amount
     * @return boolean
     */
    public static function reserve($userId, $amount)
    {
        $delegate = self::$delegate;
        return $delegate::reserve($userId, $amount);
    }

    /**
     * Отклонить существующее резервирование
     * @param $transactionId
     * @return bool
     */
    public static function decline($transactionId)
    {
        $delegate = self::$delegate;
        return $delegate::decline($transactionId);
    }

    /**
     * Подтвердить существующее резервирование
     * @param $transactionId
     * @param $amount
     * @return boolean
     */
    public static function approve($transactionId)
    {
        $delegate = self::$delegate;
        return $delegate::approve($transactionId);
    }
}