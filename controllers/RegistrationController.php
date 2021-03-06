<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\models\User;
use app\models\City;
use LevNevinitsin\Business\Service\UserService;

class RegistrationController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'allow' => false,
                        'roles' => ['@'],
                        'denyCallback' => function ($rule, $action) {
                            return $this->redirect('/tasks');
                        }
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $user = new User();
        $cities = City::find()->select(['name', 'id'])->indexBy('id')->column();

        if (Yii::$app->request->getIsPost()) {
            $user->load(Yii::$app->request->post());

            if ($user->validate()) {
                $user->password = Yii::$app->security->generatePasswordHash($user->password);
                $user->save(false);
                $user->refresh();
                UserService::assignRbacRole($user);
                return $this->goHome();
            }
        }

        return $this->render('registration', [
            'userModel' => $user,
            'cities' => $cities,
        ]);
    }
}
