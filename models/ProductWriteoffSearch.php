<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * ProductWriteoffSearch represents the model behind the search form of `app\models\ProductWriteoff`.
 */
class ProductWriteoffSearch extends ProductWriteoff
{
    public $product_name;
    public $store_name;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'store_id', 'product_id', 'approved_by'], 'integer'],
            [['count', 'approved_count'], 'number'],
            [['created_at', 'approved_at', 'status', 'product_name', 'store_name'], 'safe'],
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
            ->joinWith(['product', 'store']);

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
        $dataProvider->sort->attributes['product_name'] = [
            'asc' => ['products.name' => SORT_ASC],
            'desc' => ['products.name' => SORT_DESC],
        ];

        $dataProvider->sort->attributes['store_name'] = [
            'asc' => ['stores.name' => SORT_ASC],
            'desc' => ['stores.name' => SORT_DESC],
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
            'product_writeoffs.product_id' => $this->product_id,
            'product_writeoffs.count' => $this->count,
            'product_writeoffs.approved_count' => $this->approved_count,
            'product_writeoffs.approved_by' => $this->approved_by,
            'product_writeoffs.status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'product_writeoffs.created_at', $this->created_at])
            ->andFilterWhere(['like', 'product_writeoffs.approved_at', $this->approved_at])
            ->andFilterWhere(['like', 'products.name', $this->product_name])
            ->andFilterWhere(['like', 'stores.name', $this->store_name]);

        return $dataProvider;
    }
}
