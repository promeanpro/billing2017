<?php


namespace app\controllers\api\v1;

use app\models\commands\AddBalance;
use app\models\commands\SubstractBalance;
use app\models\commands\TransferBalance;
use app\models\processes\CommandInvoker;
use Yii;
use yii\filters\VerbFilter;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Account - контроллер для прямого изменения баланса пользователя
 * Class AccountController
 * @package app\controllers\api\v1
 */
class AccountController extends Controller
{
    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'add' => ['post'],
                    'substract' => ['post'],
                    'transfer' => ['post'],
                ],
            ]
        ];
    }

    /**
     * Пополнение счета
     */
    public function actionAdd()
    {
        $userId = Yii::$app->request->getBodyParam('userId');
        $amount = Yii::$app->request->getBodyParam('amount');

        if (empty($userId) || empty($amount)) {
            throw new BadRequestHttpException("userId or amount is empty");
        }

        $addBalance = new AddBalance();

        $addBalance->amount = $amount;
        $addBalance->userId = $userId;

        $cm = new CommandInvoker();
        $result = $cm->create($addBalance);

        if ($result) {
            Yii::$app->response->setStatusCode(202);
        } else {
            throw new ServerErrorHttpException();
        }

    }

    /**
     * Списание со счета
     */
    public function actionSubstract()
    {
        $userId = Yii::$app->request->getBodyParam('userId');
        $amount = Yii::$app->request->getBodyParam('amount');

        if (empty($userId) || empty($amount)) {
            throw new BadRequestHttpException("userId or amount is empty");
        }

        $substractBalance = new SubstractBalance();

        $substractBalance->amount = $amount;
        $substractBalance->userId = $userId;

        $cm = new CommandInvoker();
        $result = $cm->create($substractBalance);

        if ($result) {
            Yii::$app->response->setStatusCode(202);
        } else {
            throw new ConflictHttpException("please check balance and try again later");
        }
    }

    /**
     * Перевод денег
     */
    public function actionTransfer()
    {
        $sourceUserId = Yii::$app->request->getBodyParam('sourceUserId');
        $targetUserId = Yii::$app->request->getBodyParam('targetUserId');
        $amount = Yii::$app->request->getBodyParam('amount');


        if (empty($sourceUserId) || empty($targetUserId) || empty($amount)) {
            throw new BadRequestHttpException("one of userIds or amount is empty");
        }


        $transferBalance = new TransferBalance();

        $transferBalance->amount = $amount;
        $transferBalance->sourceUserId = $sourceUserId;
        $transferBalance->targetUserId = $targetUserId;

        $cm = new CommandInvoker();
        $result = $cm->create($transferBalance);

        if ($result) {
            Yii::$app->response->setStatusCode(202);
        } else {
            throw new ConflictHttpException("please check balance and try again later");
        }
    }
}