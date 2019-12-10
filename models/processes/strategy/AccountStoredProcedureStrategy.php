<?php
namespace app\models\processes\strategy;
use app\models\processes\AccountInterface;

/**
 * Класс отвечает за имплементацию процессов по сущности Account
 * Class Account
 * @package app\models\processes
 */
class AccountStoredProcedureStrategy implements AccountInterface
{
    /**
     * Пополнение счета
     * @param int $userId идентификатор пользователя
     * @param int $amount сумма к оплате
     * @return boolean
     */
    public static function add($userId, $amount)
    {
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        $command = $connection->createCommand(
            "SELECT balanceAdd(:userId,:amount)",
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
     * Списать деньги со счета
     * @param int $userId идентификатор пользователя
     * @param int $amount сумма списания
     * @return boolean
     */
    public static function substract($userId, $amount)
    {
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        $command = $connection->createCommand(
            "SELECT balanceSubstract(:userId,:amount)",
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
     * Перевести деньги от одного пользователя к другому
     * @param $sourceUserId
     * @param $targetUserId
     * @param $amount
     * @return array
     */
    public static function transfer($sourceUserId, $targetUserId, $amount)
    {
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        $command = $connection->createCommand(
            "SELECT transfer(:sourceUserId, :targetUserId,:amount)",
            [':sourceUserId' => $sourceUserId,
                ':targetUserId' => $targetUserId,
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

}