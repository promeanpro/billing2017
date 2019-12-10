<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Class UserReservations
 * @package app\models
 * @property integer $id
 * @property integer $uid
 * @property integer $amount
 */
class UserReservations extends ActiveRecord
{
    public static function tableName()
    {
        return 'UserReservations';
    }

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['id', 'uid', 'amount'], 'safe'],
        ];
    }

}