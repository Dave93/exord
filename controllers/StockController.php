<?php

namespace app\controllers;

use app\components\AccessRule;
use app\models\Categories;
use app\models\User;
use Yii;
use app\models\Stock;
use app\models\StockSearch;
use yii\data\ArrayDataProvider;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * StockController implements the CRUD actions for Stock model.
 */
class StockController extends Controller
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
                            User::ROLE_STOCK
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all Stock models.
     * @return mixed
     */
    public function actionIndex()
    {
        $query = new Query();
        $query->select("*")
            ->from("stock")
            ->groupBy("date")
            ->orderBy("date desc");
        $dataProvider = new ArrayDataProvider([
            'allModels' => $query->all(),
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Lists all Stock models.
     * @return mixed
     */
    public function actionList($date)
    {
        $sql = "select c.id,c.name from stock s 
                left join products p on p.id=s.product_id
                left join categories c on c.id=p.category_id
                where s.date=:d
                group by category_id";
        $categories = Yii::$app->db->createCommand($sql)
            ->bindValue(":d", $date, \PDO::PARAM_STR)
            ->queryAll();

        return $this->render('list', [
            'date' => $date,
            'categories' => $categories,
        ]);
    }

    /**
     * Displays a single Stock model.
     * @param integer $product_id
     * @param string $date
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($product_id, $date)
    {
        return $this->render('view', [
            'model' => $this->findModel($product_id, $date),
        ]);
    }

    /**
     * Creates a new Stock model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($date = null)
    {
        $categories = Categories::getUserCategories();
        if (Yii::$app->request->isPost) {
            $items = Yii::$app->request->post("Items");
            foreach ($items as $item) {
                if (empty($item['amount']))
                    continue;
                $sql = "insert into stock(product_id,date,amount,add_date,author_id) values(:p,:d,:a,:ad,:ar) on duplicate key update amount=values(amount), add_date=values(add_date)";
                Yii::$app->db->createCommand($sql)
                    ->bindValue(":p", $item['id'], \PDO::PARAM_INT)
                    ->bindValue(":d", date("Y-m-d"), \PDO::PARAM_STR)
                    ->bindValue(":a", $item['amount'], \PDO::PARAM_STR)
                    ->bindValue(":ad", date("Y-m-d H:i:s"), \PDO::PARAM_STR)
                    ->bindValue(":ar", Yii::$app->user->id, \PDO::PARAM_INT)
                    ->execute();
            }
            return $this->redirect(['create', 'date' => date("Y-m-d")]);
        }
        return $this->render('create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Updates an existing Stock model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $product_id
     * @param string $date
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($product_id, $date)
    {
        $model = $this->findModel($product_id, $date);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'product_id' => $model->product_id, 'date' => $model->date]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Stock model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $product_id
     * @param string $date
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($product_id, $date)
    {
        $this->findModel($product_id, $date)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Stock model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $product_id
     * @param string $date
     * @return Stock the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($product_id, $date)
    {
        if (($model = Stock::findOne(['product_id' => $product_id, 'date' => $date])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
