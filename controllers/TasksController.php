<?php
namespace app\controllers;

use Yii;
use app\models\Category;
use app\models\Task;
use app\models\User;
use app\models\City;
use app\models\Response as ResponseModel;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\filters\AccessControl;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use GuzzleHttp\Client;
use LevNevinitsin\Business\Service\TaskService;
use LevNevinitsin\Business\Service\LocationService;

class TasksController extends Controller
{
    private const INVALID_FILES_DATA = ['Invalid files'];

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'view'],
                        'roles' => ['@']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['add', 'upload-files'],
                        'roles' => ['customer'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['decline'],
                        'roles' => ['declineTask'],
                        'roleParams' => function() {
                            return ['task' => Task::findOne(['id' => Yii::$app->request->get('id')])];
                        },
                    ],
                    [
                        'allow' => true,
                        'actions' => ['cancel'],
                        'roles' => ['cancelOwnTask'],
                        'roleParams' => function() {
                            return ['task' => Task::findOne(['id' => Yii::$app->request->get('id')])];
                        },
                    ],
                    [
                        'allow' => true,
                        'actions' => ['complete'],
                        'roles' => ['completeOwnTask'],
                        'roleParams' => function() {
                            return ['task' => Task::findOne(['id' => Yii::$app->request->post('Task')['task_id']])];
                        },
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $task = new Task();
        $category = new Category();

        $request = Yii::$app->request;
        $selectedCategories = $request->get('Category')['id'] ?? [];
        $shouldShowWithoutResponses = $request->get('showWithoutResponses') === '1' ? true : false;
        $shouldShowRemoteOnly = $request->get('showRemoteOnly') === '1' ? true : false;
        $selectedPeriod = $request->get('Task')['date_created'] ?? '';

        $tasksQuery = Task::find()->joinWith('category')->joinWith('city')->where(['task_status_id' => 1]);

        $tasksQuery = TaskService::filter(
            $tasksQuery,
            $selectedCategories,
            $shouldShowWithoutResponses,
            $shouldShowRemoteOnly,
            $selectedPeriod
        );

        $newTasks = $tasksQuery->orderBy(['date_created' => SORT_DESC])->all();
        $categories = Category::find()->select(['name'])->orderBy(['id' => SORT_ASC])->indexBy('id')->column();

        return $this->render('tasks', [
            'taskModel'                  => $task,
            'categoryModel'              => $category,
            'newTasks'                   => $newTasks,
            'categories'                 => $categories,
            'selectedCategories'         => $selectedCategories,
            'shouldShowRemoteOnly'       => $shouldShowRemoteOnly,
            'shouldShowWithoutResponses' => $shouldShowWithoutResponses,
            'selectedPeriod'             => $selectedPeriod,
        ]);
    }

    public function actionView($id)
    {
        if (!$id || !$task = Task::findOne($id)) {
            throw new NotFoundHttpException();
        }

        $geocoderApiKey = 'e666f398-c983-4bde-8f14-e3fec900592a';
        $geocoderApiUri = 'https://geocode-maps.yandex.ru/';

        $client = new Client([
            'base_uri' => $geocoderApiUri,
        ]);

        try {
            $response = $client->request('GET', '1.x', [
                'query' => [
                    'geocode' => "$task->longitude, $task->latitude",
                    'apikey' => $geocoderApiKey,
                    'format' => 'json',
                 ],
            ]);

            $content = $response->getBody()->getContents();
            $responseData = json_decode($content, true);
            $geoObject = ArrayHelper::getValue($responseData, 'response.GeoObjectCollection.featureMember.0.GeoObject');
            $cityName = LocationService::getCity($geoObject);
            $address = ArrayHelper::getValue($geoObject, 'name');
        } catch (\Exception $e) {
            $cityName = $task->city->name;
            $address = $task->location;
        }

        $response = new ResponseModel();
        $this->view->params['taskModel'] = $task;
        $this->view->params['responseModel'] = $response;

        return $this->render('view-task', [
            'task' => $task,
            'cityName' => $cityName,
            'address' => $address,
        ]);
    }

    public function actionUploadFiles()
    {
        $task = new Task();
        $task->files = UploadedFile::getInstances($task, 'files');

        $filesData = $task->validate('files')
            ? TaskService::handleUploadedFiles($task->files)
            : self::INVALID_FILES_DATA;

        Yii::$app->session->set('filesData', $filesData);
    }

    public function actionAdd()
    {
        $task = new Task();
        $categories = Category::find()->select(['name'])->orderBy(['id' => SORT_ASC])->indexBy('id')->column();
        $filesData = Yii::$app->session->get('filesData');
        $areFilesValid = $filesData !== self::INVALID_FILES_DATA;
        $userCity = User::findOne(Yii::$app->user->getId())->city;

        if (Yii::$app->request->getIsPost()) {
            $task->load(Yii::$app->request->post());
            $task->task_status_id = 1;
            $task->customer_id = Yii::$app->user->identity->id;

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($task);
            }

            if ($task->validate() && ($filesData === null || $areFilesValid)) {
                if ($taskCityName = $task->cityName) {
                    $task->city_id = City::findOne(['name' => $taskCityName])->id;
                }

                $task->save(false);
                $task->refresh();
                $taskId = $task->id;

                if ($filesData) {
                    TaskService::storeUploadedFiles($filesData, $taskId);
                }

                $this->redirect("/tasks/view/$taskId");
            }
        }

        Yii::$app->session->remove('filesData');

        return $this->render('add-task', [
            'model' => $task,
            'categories' => $categories,
            'areFilesValid' => $areFilesValid,
            'userCity' => $userCity,
        ]);
    }

    public function actionCancel($id)
    {
        $task = Task::findOne($id);
        $task->task_status_id = 2;
        $task->date_updated = date("Y-m-d H:i:s");
        $task->save();
        $this->redirect("/tasks/view/$task->id");
    }

    public function actionDecline($id)
    {
        $task = Task::findOne($id);
        $task->task_status_id = 4;
        $task->date_updated = date("Y-m-d H:i:s");
        $task->save();
        $this->redirect("/tasks/view/$task->id");
    }

    public function actionComplete()
    {
        if (Yii::$app->request->getIsPost()) {
            $task = Task::findOne(Yii::$app->request->post('Task')['task_id']);
            $task->load(Yii::$app->request->post());
            $task->date_updated = date("Y-m-d H:i:s");
            $task->save();
            $this->redirect("/tasks/view/$task->id");
        }
    }
}
