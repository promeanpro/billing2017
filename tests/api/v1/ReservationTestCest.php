<?php


use app\models\UserAccount;
use app\models\UserReservations;

class ReservationTestCest
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
     * Проверим выставление резервирования
     * @param ApiTester $I
     * @param \Codeception\Example $example
     */
    public function addBalanceAndReserve(ApiTester $I)
    {
        $I->wantTo('add money to account');

        $postData = [
            'userId' => $I->getConfig('sourceUserId'),
            'amount' => 215.44552
        ];

        $I->sendPOST('/api/v1/account/add', $postData);
        $I->seeResponseCodeIs(202);
        usleep($I->getConfig('sleepTimeout'));

        $userAccount = UserAccount::findOne(['uid' => $I->getConfig('sourceUserId')]);
        $I->assertNotEmpty($userAccount);
        $I->assertEquals(215.44552 * $I->getConfig('multiplier'), $userAccount->balance);

        $postData = [
            'userId' => $I->getConfig('sourceUserId'),
            'amount' => 10.1
        ];

        //зарезервируем сразу 2 суммы, чтобы потом отклонить и списать
        $I->sendPOST('/api/v1/reservation/reserve', $postData);
        $I->seeResponseCodeIs(202);
        usleep($I->getConfig('sleepTimeout'));

        $I->sendPOST('/api/v1/reservation/reserve', $postData);
        $I->seeResponseCodeIs(202);
        usleep($I->getConfig('sleepTimeout'));

        //проврем что баланс уменьшился
        $userAccount = UserAccount::findOne(['uid' => $I->getConfig('sourceUserId')]);
        $I->assertNotEmpty($userAccount);
        $I->assertEquals(195.24552 * $I->getConfig('multiplier'), $userAccount->balance);

        $userReservations = UserReservations::findAll(['uid' => $I->getConfig('sourceUserId')]);
        $I->assertNotEmpty($userReservations);

        //проверим что есть 2 резерва и внутри они корректные по сумме
        $I->assertCount(2, $userReservations);

        foreach ($userReservations as $userReservation) {
            $I->assertEquals(10.1 * $I->getConfig('multiplier'), $userReservation->amount);
        }
    }

    /**
     * Зарезервировать денег больше чем есть
     * @param ApiTester $I
     * @param \Codeception\Example $example
     */
    public function reserveTooMuchMoney(ApiTester $I)
    {
        $I->wantTo('Reserve too much money');

        $postData = [
            'userId' => $I->getConfig('sourceUserId'),
            'amount' => 99999999999.1
        ];

        //зарезервируем сразу 2 суммы, чтобы потом отклонить и списать
        $I->sendPOST('/api/v1/reservation/reserve', $postData);
        $I->seeResponseCodeIs(409);
        usleep($I->getConfig('sleepTimeout'));
    }


    /**
     * Проверим механизм подтверждения
     * @param ApiTester $I
     * @param \Codeception\Example $example
     */
    public function appove(ApiTester $I)
    {
        $userReservations = UserReservations::findAll(['uid' => $I->getConfig('sourceUserId')]);
        $I->assertNotEmpty($userReservations);

        $postData = [
            'transactionId' => $userReservations[0]->id
        ];

        $I->sendPOST('/api/v1/reservation/approve', $postData);
        $I->seeResponseCodeIs(202);
        usleep($I->getConfig('sleepTimeout'));

        //После подтвеждения, резервирования быть не должно
        $userReservations = UserReservations::findOne(['id' => $userReservations[0]->id]);
        $I->assertEmpty($userReservations);

        //проверим что баланс не должен был измениться
        $userAccount = UserAccount::findOne(['uid' => $I->getConfig('sourceUserId')]);
        $I->assertNotEmpty($userAccount);
        $I->assertEquals(195.24552 * $I->getConfig('multiplier'), $userAccount->balance);
    }

    /**
     * Заапрувить несуществующую транзакцию
     * @param ApiTester $I
     * @param \Codeception\Example $example
     */
    public function appoveMissingTransaction(ApiTester $I)
    {
        $I->wantTo('approve missing transaction');

        $postData = [
            'transactionId' => 99999999
        ];

        //зарезервируем сразу 2 суммы, чтобы потом отклонить и списать
        $I->sendPOST('/api/v1/reservation/approve', $postData);
        $I->seeResponseCodeIs(404);
        usleep($I->getConfig('sleepTimeout'));
    }

    /**
     * Проверим механизм отказа от резервирования
     * @param ApiTester $I
     * @param \Codeception\Example $example
     */
    public function decline(ApiTester $I)
    {
        $userReservations = UserReservations::findAll(['uid' => $I->getConfig('sourceUserId')]);
        $I->assertNotEmpty($userReservations);

        $postData = [
            'transactionId' => $userReservations[0]->id
        ];


        $I->sendPOST('/api/v1/reservation/decline', $postData);
        $I->seeResponseCodeIs(202);
        usleep($I->getConfig('sleepTimeout'));

        //После подтвеждения, резервирования быть не должно
        $userReservations = UserReservations::findOne(['id' => $userReservations[0]->id]);
        $I->assertEmpty($userReservations);

        //проверим что мы вернули деньги пользователю
        $userAccount = UserAccount::findOne(['uid' => $I->getConfig('sourceUserId')]);
        $I->assertNotEmpty($userAccount);
        $I->assertEquals(205.34552 * $I->getConfig('multiplier'), $userAccount->balance);

    }

    /**
     * Отменить несуществующую транзакцию
     * @param ApiTester $I
     * @param \Codeception\Example $example
     */
    public function declineMissingTransaction(ApiTester $I)
    {
        $I->wantTo('decline missing transaction');

        $postData = [
            'transactionId' => 99999999
        ];

        //зарезервируем сразу 2 суммы, чтобы потом отклонить и списать
        $I->sendPOST('/api/v1/reservation/decline', $postData);
        $I->seeResponseCodeIs(404);
        usleep($I->getConfig('sleepTimeout'));
    }
}
