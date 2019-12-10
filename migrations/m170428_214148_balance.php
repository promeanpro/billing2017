
<?php

use yii\db\Migration;

class m170428_214148_balance extends Migration
{
    private $balanceTable = 'UserAccount';
    private $userTransactionsTable = 'UserReservations';

    public function up()
    {
        $options = null;
        if ($this->db->driverName === 'mysql') {
            $options = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable($this->balanceTable, array(
            'uid' => 'int NOT NULL PRIMARY KEY',
            'balance' => 'bigint NOT NULL',
        ), $options);

        $this->createTable($this->userTransactionsTable, array(
            'id' => 'int UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT',
            'uid' => 'int NOT NULL',
            'amount' => 'bigint NOT NULL',
        ), $options);

        $this->createIndex($this->userTransactionsTable.'uid',$this->userTransactionsTable,'uid');
    }

    public function down()
    {
        $this->dropTable($this->balanceTable);
        $this->dropTable($this->userTransactionsTable);

        return true;
    }


}
