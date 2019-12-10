<?php
namespace app\models\commands;
use app\models\processes\Account;
use app\models\processes\AccountInterface;


/**
 * Модель запроса для добавления денег
 * Class AddBalance
 * @property integer $userId
 * @property float $amount
 */
class AddBalance extends AbstractCommand
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
        $result = $delegate::add($this->userId, $this->amount);
        return $result;
    }
}