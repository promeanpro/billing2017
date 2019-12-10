<?php
namespace app\models\commands;
use app\models\processes\Reservations;
use app\models\processes\ReservationsInterface;


/**
 * Модель запроса для резервирования денег
 * Class Reserve
 * @property integer $userId
 * @property integer $amount
 */
class Reserve extends AbstractCommand
{
    public $userId;
    public $amount;

    /** @var  ReservationsInterface */
    public $delegate = Reservations::class;

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
        $result = $delegate::reserve($this->userId, $this->amount);
        return $result;
    }
}