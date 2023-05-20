<?php
namespace app\controllers;

use app\models\DailyStoreBalance;
use Yii;
use yii\data\ArrayDataProvider;
use yii\db\mssql\PDO;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class DailyStoreBalanceController extends Controller
{
    public function actionIndex()
    {
        $dailyStoreBalnce = DailyStoreBalance::find()->all();
        $dataProvider = new ArrayDataProvider([
            'allModels' => $dailyStoreBalnce,
            'sort' => [
                'attributes' => ['store_name', 'quantity'],
            ],
            'pagination' => false,
        ]);
//        echo '<pre>'; print_r($dailyStoreBalnce); echo '</pre>';
        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
//        $model->updateBalance();

        $sql = "select dp.*,p.name,p.mainUnit as unit from daily_store_product dp 
                left join products p on p.id=dp.product_id
                where dp.daily_store_balance_id=:s
                order by p.name";
        $data = Yii::$app->db->createCommand($sql)
            ->bindParam(":s", $model->id, PDO::PARAM_STR)
            ->queryAll();

        $dataProvider = new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => ['name', 'quantity', 'sum', 'unit'],
            ],
            'pagination' => false,
        ]);

        return $this->render('view', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    protected function findModel($id)
    {
        if (($model = DailyStoreBalance::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}