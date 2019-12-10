<?php
namespace app\models\processes\strategy;


use app\models\processes\ReservationsInterface;
use app\models\UserReservations;

/**
 * Класс отвечает за имплементацию процессов по сущности Reservations
 * Class Account
 * @package app\models\processes
 */
class ReservationsStoredProcedureStrategy implements ReservationsInterface
{
    /**
     * Получить список зарезерированных транзакций
     * @param $userId
     * @return static[]
     */
    public static function get($userId)
    {
        return UserReservations::findAll(["uid" => $userId]);
    }

    /**
     * Зарезервировать деньги
     * @param $userId
     * @param $amount
     * @return boolean
     */
    public static function reserve($userId, $amount)
    {
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        $command = $connection->createCommand(
            "SELECT reserve(:userId,:amount)",
            [':userId' => $userId,
                ':amount' => $amount]);

        $result = $command->queryAll();
        $status = current(current($result));

        if ($status) {
            $transaction->commit();
        } else {
            $transaction->rollBack();
        }

        return $status;
    }

    /**
     * Отклонить существующее резервирование
     * @param $transactionId
     * @return bool
     */
    public static function decline($transactionId)
    {
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        $command = $connection->createCommand(
            "SELECT decline(:transactionId)",
            [':transactionId' => $transactionId]);

        $result = $command->queryAll();
        $status = current(current($result));

        if ($status) {
            $transaction->commit();
        } else {
            $transaction->rollBack();
        }

        return $status;
    }

    /**
     * Подтвердить существующее резервирование
     * @param $transactionId
     * @param $amount
     * @return boolean
     */
    public static function approve($transactionId)
    {
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        $command = $connection->createCommand(
            "SELECT approve(:transactionId)",
            [':transactionId' => $transactionId]);

        $result = $command->queryAll();
        $status = current(current($result));

        if ($status) {
            $transaction->commit();
        } else {
            $transaction->rollBack();
        }

        return $status;
    }
}