<?php

namespace app\controllers;

use app\components\AccessRule;
use app\models\Products;
use app\models\UserCategories;
use Yii;
use app\models\User;
use app\models\UserSearch;
use yii\db\mssql\PDO;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'ruleConfig' => [
                    'class' => AccessRule::className(),
                ],
                'only' => ['*'],
                'rules' => [
                    [
                        'actions' => ['index', 'create', 'delete', 'update', 'view'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_ADMIN
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all User models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single User model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new User();
        $model->scenario = 'create';
        if ($model->load(Yii::$app->request->post())) {
            $model->password = $model->generatePassword($model->password);
            $model->authKey = $model->generateAuthKey();
            $model->accessToken = $model->generateAccessToken();
            $model->regDate = date("Y-m-d H:i:s");
            if ($model->save()) {
                foreach ($model->category as $cat) {
                    $p = Products::findOne($cat);
                    if (empty($p->productType))
                        continue;
                    $cm = new UserCategories();
                    $cm->user_id = $model->id;
                    $cm->category_id = $cat;
                    $cm->save();
                }
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->scenario = 'update';

        $checked = Yii::$app->db->createCommand("select category_id from user_categories where user_id=:id")
            ->bindValue(":id", $model->id, PDO::PARAM_INT)
            ->queryColumn();

        if ($model->load(Yii::$app->request->post())) {
            if (!empty($model->newPassword))
                $model->password = $model->generatePassword($model->newPassword);
            $model->authKey = $model->generateAuthKey();
            $model->accessToken = $model->generateAccessToken();
            if ($model->save()) {
                UserCategories::deleteAll(['user_id' => $model->id]);
                foreach ($model->category as $cat) {
                    $cm = new UserCategories();
                    $cm->user_id = $model->id;
                    $cm->category_id = $cat;
                    $cm->save();
                }
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', [
            'model' => $model,
            'checked' => $checked,
        ]);
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
