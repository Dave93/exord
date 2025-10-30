<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Orders;

/**
 * OrderSearch represents the model behind the search form of `app\models\Orders`.
 */
class OrderSearch extends Orders
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'userId', 'state'], 'integer'],
            [['date', 'defaultStoreId', 'storeId', 'supplierId', 'addDate'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params, $active = true, $start = null, $end = null, $state=null)
    {
        $query = Orders::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['date' => SORT_DESC]],
            'pagination' => [
                'pageSize' => 100
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'userId' => $this->userId,
            'date' => $this->date,
            'addDate' => $this->addDate,
            'is_market' => $this->is_market,
        ]);

        if ($active) {
            $query->andFilterWhere(['<>', 'state', '2']);
        } else {
            $query->andFilterWhere(['state' => $this->state]);
        }

        if (!empty($start)) {
            $query->andWhere('date>=:z', [':z' => date('Y-m-d', strtotime($start))." 00:00:00"]);
        }
        if (!empty($end)) {
            $query->andWhere('date<=:e', [':e' => date('Y-m-d', strtotime($end))." 23:59:59"]);
        }

        if (!empty($state)) {
            $query->andWhere('state=:s', [':s' => $state]);
        }

        if (Yii::$app->user->identity->role != User::ROLE_ADMIN) {
            $query->andWhere('deleted_at is null');

            // Для не-админов ограничиваем выборку последними 50 днями
            if (empty($start) && empty($end)) {
                $fiftyDaysAgo = date('Y-m-d', strtotime('-50 days'));
                $query->andWhere(['>=', 'date', $fiftyDaysAgo]);
            }
        }

        $query->andFilterWhere(['like', 'defaultStoreId', $this->defaultStoreId])
            ->andFilterWhere(['like', 'storeId', $this->storeId])
            ->andFilterWhere(['like', 'supplierId', $this->supplierId]);

        return $dataProvider;
    }
}
