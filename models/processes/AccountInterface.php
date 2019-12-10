<?php


namespace app\models\processes;


interface AccountInterface
{
    /**
     * Пополнение счета
     * @param int $userId идентификатор пользователя
     * @param int $amount сумма к оплате
     * @return boolean
     */
    public static function add($userId, $amount);

    /**
     * Списать деньги со счета
     * @param int $userId идентификатор пользователя
     * @param int $amount сумма списания
     * @return boolean
     */
    public static function substract($userId, $amount);


    /**
     * Перевести деньги от одного пользователя к другому
     * @param $sourceUserId
     * @param $targetUserId
     * @param $amount
     * @return array
     */
    public static function transfer($sourceUserId, $targetUserId, $amount);
}