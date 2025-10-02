<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * ProductWriteoffSearch represents the model behind the search form of `app\models\ProductWriteoff`.
 */
class ProductWriteoffSearch extends ProductWriteoff
{
    public $store_name;
    public $created_by_name;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'created_by', 'approved_by'], 'integer'],
            [['store_id', 'created_at', 'approved_at', 'status', 'comment', 'store_name', 'created_by_name'], 'safe'],
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
        $query = ProductWriteoff::find()
            ->joinWith(['store', 'createdBy']);

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
        $dataProvider->sort->attributes['store_name'] = [
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
            'product_writeoffs.id' => $this->id,
            'product_writeoffs.store_id' => $this->store_id,
            'product_writeoffs.created_by' => $this->created_by,
            'product_writeoffs.approved_by' => $this->approved_by,
            'product_writeoffs.status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'product_writeoffs.created_at', $this->created_at])
            ->andFilterWhere(['like', 'product_writeoffs.approved_at', $this->approved_at])
            ->andFilterWhere(['like', 'product_writeoffs.comment', $this->comment])
            ->andFilterWhere(['like', 'stores.name', $this->store_name])
            ->andFilterWhere(['like', 'users.fullname', $this->created_by_name]);

        return $dataProvider;
    }
}
