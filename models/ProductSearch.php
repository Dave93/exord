<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Products;

/**
 * ProductSearch represents the model behind the search form of `app\models\Products`.
 */
class ProductSearch extends Products
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'parentId', 'name', 'mainUnit', 'cookingPlaceType', 'productType', 'syncDate', 'zone'], 'safe'],
            [['code', 'num', 'minBalance'], 'integer'],
            [['price_start', 'price_end', 'delta', 'inStock'], 'number'],
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
    public function search($params, $isCategory = false)
    {
        $query = Products::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['name' => SORT_ASC]],
            'pagination' => [
                'pageSize' => 50
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        if ($isCategory)
            $query->where('productType=""');

        // grid filtering conditions
        $query->andFilterWhere([
            'code' => $this->code,
            'parentId' => $this->parentId,
            'num' => $this->num,
            'price_start' => $this->price_start,
            'price_end' => $this->price_end,
            'syncDate' => $this->syncDate,
            'delta' => $this->delta,
            'inStock' => $this->inStock,
            'minBalance' => $this->minBalance,
            'zone' => $this->zone,
        ]);

        $query->andFilterWhere(['like', 'id', $this->id])
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'mainUnit', $this->mainUnit])
            ->andFilterWhere(['like', 'cookingPlaceType', $this->cookingPlaceType]);

        return $dataProvider;
    }
}
