<?php
namespace app\models\commands;
use app\models\processes\Reservations;
use app\models\processes\ReservationsInterface;


/**
 * Модель запроса для отклонения резервирования
 * Class AddBalance
 * @property integer $transactionId
 */
class Decline extends AbstractCommand
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
        $result = $delegate::decline($this->transactionId);
        return $result;
    }
}