<?php
namespace app\models\commands;
use app\models\processes\Reservations;
use app\models\processes\ReservationsInterface;


/**
 * Модель запроса для подтверждения резервирования
 * Class Approve
 * @property integer $transactionId
 */
class Approve extends AbstractCommand
{
    public $transactionId;

    /** @var  ReservationsInterface */
    public $delegate = Reservations::class;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['transactionId'], 'required'],
        ];
    }

    public function run()
    {
        $delegate = $this->delegate;
        $result = $delegate::approve($this->transactionId);
        return $result;
    }
}