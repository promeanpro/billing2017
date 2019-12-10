<?php
namespace app\models\commands;
use app\models\processes\Account;


/**
 * Модель запроса для удаления
 * Class AddBalance
 * @property integer $userId
 * @property float $amount
 */
class SubstractBalance extends AbstractCommand
{
    public $userId;
    public $amount;

    /** @var  AccountInterface */
    public $delegate = Account::class;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['userId', 'amount'], 'required'],
        ];
    }

    public function run()
    {
        $delegate = $this->delegate;
        $result = $delegate::substract($this->userId, $this->amount);
        return $result;
    }
}