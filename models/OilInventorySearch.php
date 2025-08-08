<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * OilInventorySearch represents the model behind the search form of `app\models\OilInventory`.
 */
class OilInventorySearch extends OilInventory
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['store_id', 'status', 'created_at', 'updated_at'], 'safe'],
            [['opening_balance', 'income', 'return_amount', 'return_amount_kg', 'apparatus', 'new_oil', 'evaporation', 'closing_balance'], 'number'],
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
        $query = OilInventory::find();

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

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // Фильтрация по store_id (точное совпадение для безопасности)
        if (!empty($this->store_id)) {
            $query->andWhere(['store_id' => $this->store_id]);
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'opening_balance' => $this->opening_balance,
            'income' => $this->income,
            'return_amount' => $this->return_amount,
            'return_amount_kg' => $this->return_amount_kg,
            'apparatus' => $this->apparatus,
            'new_oil' => $this->new_oil,
            'evaporation' => $this->evaporation,
            'closing_balance' => $this->closing_balance,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'status', $this->status]);

        return $dataProvider;
    }
} 