<?php

namespace app\controllers;

use app\components\AccessRule;
use app\models\User;
use Yii;
use app\models\Products;
use app\models\ProductSearch;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\mssql\PDO;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * ProductsController implements the CRUD actions for Products model.
 */
class ProductsController extends Controller
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
                        'actions' => ['index', 'subs', 'links'],
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
     * Lists all Products models.
     * @return mixed
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionIndex1()
    {
        $sql = 'select * from (select p.*,(select count(*) from products where parentId=p.id) as count from products p where p.parentId=\'0\') q1 where q1.count>0 order by q1.name';
        $data = Yii::$app->db->createCommand($sql)->queryAll();

        $dataProvider = new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => ['name'],
            ],
            'pagination' => false,
        ]);

        return $this->render('index1', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Lists all Products models.
     * @return mixed
     */
    public function actionSubs($id)
    {
        $model = $this->findModel($id);

        $sql = 'select p.* from products p where p.parentId=:p order by p.name';
        $data = Yii::$app->db->createCommand($sql)
            ->bindValue(':p', $model->id, PDO::PARAM_STR)
            ->queryAll();

        $dataProvider = new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => ['name'],
            ],
            'pagination' => false,
        ]);

        return $this->render('subs', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Lists all Products models.
     * @return mixed
     */
    public function actionList()
    {
        $searchModel = new ProductSearch();
//        $searchModel->parentId = '933aedd9-6cd9-44bb-9d72-00008e9b3cb5';
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }


    public function actionLinks()
    {


        if (Yii::$app->request->post()) {
            $data = Yii::$app->request->post();

            if (isset($data['user']) && isset($data['product'])) {
                $userCategory = (new Query())
                    ->select(['user_id'])
                    ->from('user_categories')
                    ->where(['user_id' => $data['user'], 'category_id' => $data['product']])
                    ->one();

                if ($data['checked'] == 1) {
                    if (empty($userCategory)) {
                        Yii::$app->db->createCommand()->insert('user_categories', [
                            'user_id' => $data['user'],
                            'category_id' => $data['product'],
                        ])->execute();
                    }
                } else {
                    if (!empty($userCategory)) {
                        Yii::$app->db->createCommand()->delete('user_categories', [
                            'user_id' => $data['user'],
                            'category_id' => $data['product'],
                        ])->execute();
                    }
                }
            }
        }

        /**
         * get all products where productType is not null and orderBy name
         * white active record query
         */
        $products = Products::find()
            ->where(['<>','productType' , ''])
            ->orderBy('name')
            ->asArray()
            ->all();

        $arCategoryIds = [];
        foreach ($products as $product) {
            $arCategoryIds[$product['parentId']] = $product['parentId'];
        }

        if (!empty($arCategoryIds)) {
            $categories = Products::find()
                ->where(['in', 'id', array_values($arCategoryIds)])
                ->orderBy('name')
                ->asArray()
                ->all();

            foreach ($categories as $category) {
                foreach ($products as $key => $product) {
                    if ($product['parentId'] == $category['id']) {
                        $products[$key]['category'] = $category['name'];
                    }
                }
            }

            // please sort $products by name and category
            usort($products, function($a, $b) {
                if ($a['category'] == $b['category']) {
                    return strcmp($a['name'], $b['name']);
                }
                return strcmp($a['category'], $b['category']);
            });


            /**
             * get all users where role is equal to 6
             */

            $users = User::find()
                ->where(['role' => 6])
                ->andWhere(['state' => 1])
                ->orderBy('fullname')
                ->asArray()
                ->all();

            $arUserIds = [];

            foreach ($users as $user) {
                $arUserIds[$user['id']] = $user['id'];
            }

            $userCategories = (new Query())
                ->select(['user_id', 'category_id'])
                ->from('user_categories')
                ->where(['in', 'user_id', array_values($arUserIds)])
                ->all();

            $userByProduct = [];

            foreach ($userCategories as $userCategory) {
                $userByProduct[$userCategory['category_id']][] = $userCategory['user_id'];
            }

            foreach ($products as $key => $product) {
                if (empty($products[$key]['users'])) {
                    $products[$key]['users'] = [];
                }

                foreach ($users as $user) {
                    $products[$key]['users'][$user['id']] = (in_array($user['id'], $userByProduct[$product['id']]) ? 1 : 0);
                }
            }


            return $this->render('links', [
                'products' => $products,
                'users' => $users,
            ]);
        }

    }

    /**
     * Finds the Products model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Products the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Products::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
