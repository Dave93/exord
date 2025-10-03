<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * StoreTransferSearch represents the model behind the search form of `app\models\StoreTransfer`.
 */
class StoreTransferSearch extends StoreTransfer
{
    public $request_store_name;
    public $created_by_name;
    public $date_from;
    public $date_to;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'created_by'], 'integer'],
            [['request_store_id', 'created_at', 'status', 'comment', 'request_store_name', 'created_by_name', 'date_from', 'date_to'], 'safe'],
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
    public function search($params)
    {
        $query = StoreTransfer::find()
            ->joinWith(['requestStore', 'createdBy']);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC],
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        // Добавляем сортировку по связанным таблицам
        $dataProvider->sort->attributes['request_store_name'] = [
            'asc' => ['stores.name' => SORT_ASC],
            'desc' => ['stores.name' => SORT_DESC],
        ];

        $dataProvider->sort->attributes['created_by_name'] = [
            'asc' => ['users.fullname' => SORT_ASC],
            'desc' => ['users.fullname' => SORT_DESC],
        ];

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'store_transfers.id' => $this->id,
            'store_transfers.request_store_id' => $this->request_store_id,
            'store_transfers.created_by' => $this->created_by,
            'store_transfers.status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'store_transfers.created_at', $this->created_at])
            ->andFilterWhere(['like', 'store_transfers.comment', $this->comment])
            ->andFilterWhere(['like', 'stores.name', $this->request_store_name])
            ->andFilterWhere(['like', 'users.fullname', $this->created_by_name]);

        // Фильтрация по диапазону дат
        if ($this->date_from) {
            $query->andFilterWhere(['>=', 'DATE(store_transfers.created_at)', $this->date_from]);
        }
        if ($this->date_to) {
            $query->andFilterWhere(['<=', 'DATE(store_transfers.created_at)', $this->date_to]);
        }

        return $dataProvider;
    }
}
