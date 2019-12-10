<?php


namespace app\models\commands;
use app\models\processes\Account;
use app\models\processes\AccountInterface;


/**
 * Модель перевода денег с одного счета на другой
 * Class TransferBalance
 * @property integer $sourceUserId
 * @property integer $targetUserId
 * @property float $amount
 */
class TransferBalance extends AbstractCommand
{
    public $sourceUserId;
    public $targetUserId;
    public $amount;

    /** @var AccountInterface */
    public $delegate = Account::class;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['sourceUserId', 'targetUserId', 'amount'], 'required'],
        ];
    }

    public function run()
    {
        $delegate = $this->delegate;
        $result = $delegate::transfer($this->sourceUserId, $this->targetUserId, $this->amount);
        return $result;
    }
}