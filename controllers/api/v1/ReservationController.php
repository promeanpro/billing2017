<?php


namespace app\controllers\api\v1;


use app\models\commands\Approve;
use app\models\commands\Decline;
use app\models\commands\Reserve;
use app\models\processes\CommandInvoker;
use app\models\processes\Reservations;
use Yii;
use yii\filters\VerbFilter;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\NotFoundHttpException;

/**
 * Отвечает за работу с резервированием средств
 * Class ReservationController
 * @package app\controllers\api\v1
 */
class ReservationController extends Controller
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
                    'reserve' => ['post'],
                    'approve' => ['post'],
                    'decline' => ['post'],
                    'list' => ['get'],
                ],
            ]
        ];
    }


    /**
     * Резервинование денег на счете
     */
    public function actionReserve()
    {
        $userId = Yii::$app->request->getBodyParam('userId');
        $amount = Yii::$app->request->getBodyParam('amount');

        if (empty($userId) || empty($amount)) {
            throw new BadRequestHttpException("userId or amount is empty");
        }

        $reserve = new Reserve();
        $reserve->amount = $amount;
        $reserve->userId = $userId;

        $cm = new CommandInvoker();
        $result = $cm->create($reserve);

        if ($result) {
            Yii::$app->response->setStatusCode(202);
        } else {
            throw new ConflictHttpException("please check balance and try again later");
        }
    }

    /**
     * Резервирование денег на счете
     */
    public function actionApprove()
    {
        $transactionId = Yii::$app->request->getBodyParam('transactionId');

        if (empty($transactionId)) {
            throw new BadRequestHttpException("transactionId is empty");
        }

        $approve = new Approve();
        $approve->transactionId = $transactionId;

        $cm = new CommandInvoker();
        $result = $cm->create($approve);


        if ($result) {
            Yii::$app->response->setStatusCode(202);
        } else {
            throw new NotFoundHttpException("no such transactiion");
        }
    }

    /**
     * Отмена резервирования
     */
    public function actionDecline()
    {
        $transactionId = Yii::$app->request->getBodyParam('transactionId');

        if (empty($transactionId)) {
            throw new BadRequestHttpException("transactionId is empty");
        }

        $decline = new Decline();
        $decline->transactionId = $transactionId;

        $cm = new CommandInvoker();
        $result = $cm->create($decline);

        if ($result) {
            Yii::$app->response->setStatusCode(202);
        } else {
            throw new NotFoundHttpException("no such transactiion");
        }
    }

    /**
     * Получить список резервирования
     */
    public function actionList()
    {
        $userId = Yii::$app->request->getQueryParam('userId');

        if (empty($userId)) {
            throw new BadRequestHttpException("userId is empty");
        }

        $reservation = new Reservations();
        return $reservation->get($userId);
    }

}