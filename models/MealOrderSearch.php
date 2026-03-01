<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * MealOrderSearch represents the model behind the search form of `app\models\MealOrders`.
 */
class MealOrderSearch extends MealOrders
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'userId', 'state'], 'integer'],
            [['date', 'storeId', 'addDate'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        return Model::scenarios();
    }

    /**
     * @param array $params
     * @param bool $active
     * @param string $start
     * @param string $end
     * @param int $state
     * @return ActiveDataProvider
     */
    public function search($params, $active = true, $start = null, $end = null, $state = null)
    {
        $query = MealOrders::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['date' => SORT_DESC]],
            'pagination' => [
                'pageSize' => 100
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'userId' => $this->userId,
            'date' => $this->date,
            'addDate' => $this->addDate,
        ]);

        if ($active) {
            $query->andFilterWhere(['<>', 'state', '2']);
        } else {
            $query->andFilterWhere(['state' => $this->state]);
        }

        if (!empty($start)) {
            $query->andWhere('date>=:z', [':z' => date('Y-m-d', strtotime($start)) . " 00:00:00"]);
        }
        if (!empty($end)) {
            $query->andWhere('date<=:e', [':e' => date('Y-m-d', strtotime($end)) . " 23:59:59"]);
        }

        if (!empty($state)) {
            $query->andWhere('state=:s', [':s' => $state]);
        }

        if (Yii::$app->user->identity->role != User::ROLE_ADMIN) {
            $query->andWhere('deleted_at is null');

            if (empty($start) && empty($end)) {
                $fiftyDaysAgo = date('Y-m-d', strtotime('-50 days'));
                $query->andWhere(['>=', 'date', $fiftyDaysAgo]);
            }
        }

        $query->andFilterWhere(['like', 'storeId', $this->storeId]);

        return $dataProvider;
    }
}
