<?php


use app\models\UserAccount;
use app\models\UserReservations;

class RaceConditionTestCest
{
    public function _before(ApiTester $I)
    {
    }

    public function _after(ApiTester $I)
    {
    }

    /**
     * Удалим из дев базы упоминания о тех пользователях с которыми проводим тесты
     * @param ApiTester $I
     * @param \Codeception\Example $example
     */
    public function flushUsers(ApiTester $I)
    {
        UserAccount::deleteAll(['uid' => [$I->getConfig('sourceUserId'), $I->getConfig('targetUserId')]]);
        UserReservations::deleteAll(['uid' => [$I->getConfig('sourceUserId'), $I->getConfig('targetUserId')]]);
    }

    /**
     * Проверим быстрое пополнение и списание
     * @param ApiTester $I
     * @param \Codeception\Example $example
     */
    public function fastAddAndSubstract(ApiTester $I)
    {
        $I->wantTo('check race condtion on fast add and substract');

        $postData = [
            'userId' => $I->getConfig('sourceUserId'),
            'amount' => 100.1
        ];

        for ($i = 0; $i < 5 ; $i++) {
            $I->sendPOST('/api/v1/account/add', $postData);
            $I->seeResponseCodeIs(202);
        }

        $postData = [
            'userId' => $I->getConfig('sourceUserId'),
            'amount' => 1.05
        ];

        $I->sendPOST('/api/v1/account/substract', $postData);
        $I->seeResponseCodeIs(202);

        usleep(10 * $I->getConfig('sleepTimeout'));

        $userAccount = UserAccount::findOne(['uid' => $I->getConfig('sourceUserId')]);
        $I->assertNotEmpty($userAccount);
        $I->assertEquals(499.45 * $I->getConfig('multiplier'), $userAccount->balance);
    }
}
