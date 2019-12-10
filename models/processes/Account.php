<?php
namespace app\models\processes;
use app\models\processes\strategy\AccountStoredProcedureStrategy;

/**
 * Класс отвечает за имплементацию процессов по сущности Account
 * Class Account
 * @package app\models\processes
 */
class Account implements AccountInterface
{
    /** @var  AccountInterface delegate */
    private static $delegate = AccountStoredProcedureStrategy::class;

    /**
     * @param AccountInterface $delegate
     */
    public function setDelegate($delegate)
    {
        static::$delegate = $delegate;
    }

    /**
     * Пополнение счета
     * @param int $userId идентификатор пользователя
     * @param int $amount сумма к оплате
     * @return boolean
     */
    public static function add($userId, $amount)
    {
        $delegate = self::$delegate;
        return $delegate::add($userId,$amount);
    }

    /**
     * Списать деньги со счета
     * @param int $userId идентификатор пользователя
     * @param int $amount сумма списания
     * @return boolean
     */
    public static function substract($userId, $amount)
    {
        $delegate = self::$delegate;
        return $delegate::substract($userId,$amount);
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
        $delegate = self::$delegate;
        return $delegate::transfer($sourceUserId, $targetUserId, $amount);
    }

}