<?php


use app\models\UserAccount;
use app\models\UserReservations;

class AccountTestCest
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
     * Проверяем пополнение счета
     * @param ApiTester $I
     * @param \Codeception\Example $example
     */
    public function addBalance(ApiTester $I)
    {
        $I->wantTo('add money to account');

        $postData = [
            'userId' => $I->getConfig('sourceUserId'),
            'amount' => 100.12
        ];

        $I->sendPOST('/api/v1/account/add', $postData);
        $I->seeResponseCodeIs(202);
        usleep($I->getConfig('sleepTimeout'));

        $userAccount = UserAccount::findOne(['uid' => $I->getConfig('sourceUserId')]);
        $I->assertNotEmpty($userAccount);
        $I->assertEquals(100.12 * $I->getConfig('multiplier'), $userAccount->balance);
    }

    /**
     * Проверяем списание со счета
     * @param ApiTester $I
     * @param \Codeception\Example $example
     */
    public function substractBalance(ApiTester $I)
    {
        $I->wantTo('remove money from account');

        $postData = [
            'userId' => $I->getConfig('sourceUserId'),
            'amount' => 45.41
        ];

        $I->sendPOST('/api/v1/account/substract', $postData);
        $I->seeResponseCodeIs(202);
        usleep($I->getConfig('sleepTimeout'));

        $userAccount = UserAccount::findOne(['uid' => $I->getConfig('sourceUserId')]);
        $I->assertEquals(54.71 * $I->getConfig('multiplier'), $userAccount->balance);
    }

    /**
     * Проверяем списание со счета больше чем сам счет
     * @param ApiTester $I
     * @param \Codeception\Example $example
     */
    public function substractLargeAmount(ApiTester $I)
    {
        $I->wantTo('remove large amount of money from account');

        $postData = [
            'userId' => $I->getConfig('sourceUserId'),
            'amount' => 422225.41
        ];

        $I->sendPOST('/api/v1/account/substract', $postData);
        $I->seeResponseCodeIs(409);
        usleep($I->getConfig('sleepTimeout'));

        $userAccount = UserAccount::findOne(['uid' => $I->getConfig('sourceUserId')]);
        $I->assertEquals(54.71 * $I->getConfig('multiplier'), $userAccount->balance);
    }

    /**
     * Проверемя перевод от одного пользователя к другому
     * @param ApiTester $I
     * @param \Codeception\Example $example
     */
    public function transferBalance(ApiTester $I)
    {
        $I->wantTo('transfer money from one account to another');

        $postData = [
            'sourceUserId' => $I->getConfig('sourceUserId'),
            'targetUserId' => $I->getConfig('targetUserId'),
            'amount' => 0.01
        ];

        $I->sendPOST('/api/v1/account/transfer', $postData);
        $I->seeResponseCodeIs(202);
        usleep($I->getConfig('sleepTimeout'));

        $userAccount = UserAccount::findOne(['uid' => $I->getConfig('sourceUserId')]);
        $I->assertEquals(54.7 * $I->getConfig('multiplier'), $userAccount->balance);

        $userAccount = UserAccount::findOne(['uid' => $I->getConfig('targetUserId')]);
        $I->assertEquals(0.01 * $I->getConfig('multiplier'), $userAccount->balance);
    }


    /**
     * Проверемя перевод от одного пользователя к другому слишком большой суммы
     * @param ApiTester $I
     * @param \Codeception\Example $example
     */
    public function transferTooLargeBalance(ApiTester $I)
    {
        $I->wantTo('transfer money from one account to another too large money');

        $postData = [
            'sourceUserId' => $I->getConfig('sourceUserId'),
            'targetUserId' => $I->getConfig('targetUserId'),
            'amount' => 999999999999
        ];

        $I->sendPOST('/api/v1/account/transfer', $postData);
        $I->seeResponseCodeIs(409);
        usleep($I->getConfig('sleepTimeout'));

        $userAccount = UserAccount::findOne(['uid' => $I->getConfig('sourceUserId')]);
        $I->assertEquals(54.7 * $I->getConfig('multiplier'), $userAccount->balance);

        $userAccount = UserAccount::findOne(['uid' => $I->getConfig('targetUserId')]);
        $I->assertEquals(0.01 * $I->getConfig('multiplier'), $userAccount->balance);
    }
}
