<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\OrderItems;

/**
 * OrderItemSearch represents the model behind the search form of `app\models\OrderItems`.
 */
class OrderItemSearch extends OrderItems
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['orderId', 'userId'], 'integer'],
            [['productId', 'storeId', 'storeSupplierId', 'supplierId', 'supplierDescription'], 'safe'],
            [['quantity', 'storeQuantity', 'factStoreQuantity', 'supplierQuantity', 'purchaseQuantity', 'price', 'factSupplierQuantity'], 'number'],
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
     * @param int $showDeleted Показывать удалённые записи (0 - не показывать, 1 - показывать)
     *
     * @return ActiveDataProvider
     */
    public function search($params, $showDeleted = 0)
    {
        // Используем findWithDeleted() если нужно показать удалённые записи
        if ($showDeleted) {
            $query = OrderItems::findWithDeleted();
        } else {
            $query = OrderItems::find();
        }

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => false
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'orderId' => $this->orderId,
            'quantity' => $this->quantity,
            'storeQuantity' => $this->storeQuantity,
            'factStoreQuantity' => $this->factStoreQuantity,
            'supplierQuantity' => $this->supplierQuantity,
            'purchaseQuantity' => $this->purchaseQuantity,
            'price' => $this->price,
            'factSupplierQuantity' => $this->factSupplierQuantity,
            'userId' => $this->userId,
        ]);

        $query->andFilterWhere(['like', 'productId', $this->productId])
            ->andFilterWhere(['like', 'storeId', $this->storeId])
            ->andFilterWhere(['like', 'storeSupplierId', $this->storeSupplierId])
            ->andFilterWhere(['like', 'supplierId', $this->supplierId])
            ->andFilterWhere(['like', 'supplierDescription', $this->supplierDescription]);

        return $dataProvider;
    }
}
