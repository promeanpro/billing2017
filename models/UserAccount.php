<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Class UserAccount
 * @package app\models
 * @property integer $uid
 * @property integer $balance
 */
class UserAccount extends ActiveRecord
{
    public static function tableName()
    {
        return 'UserAccount';
    }


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['uid', 'balance'], 'safe'],
        ];
    }

}