# Market Prices Fill Status Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Introduce a new order state "Заполнение цен базара" (state=4). After `actionClose`, orders containing bazar items enter state 4 instead of 2, and a new UI lets authorized users fill per-item market totals before the order completes.

**Architecture:** State-machine extension in `Orders`. New column `order_items.market_total_price`. Changelog extension for price edits in `order_items_changelog`. Two new controller actions on `OrdersController` (list + fill page) with GridView + form views. Menu entries and a `view.php` CTA surface the new status.

**Tech Stack:** Yii2, MySQL, Codeception (unit + functional), Bootstrap widgets (kartik DatePicker, GridView).

**Spec:** `docs/superpowers/specs/2026-04-19-market-prices-fill-status-design.md`

**Commit discipline:** One commit per task. Every commit message must end with `Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>`.

---

## File Structure

**Migrations (create):**
- `migrations/m260419_000000_add_market_total_price_to_order_items.php` — adds `order_items.market_total_price`.
- `migrations/m260419_000001_add_price_fields_to_order_items_changelog.php` — adds `order_items_changelog.old_price` / `new_price`.

**Models (modify):**
- `models/Orders.php` — extend `$states`, add `hasMarketItems()`.
- `models/OrderItemsChangelog.php` — new constant `ACTION_PRICE_UPDATED`, new rule entries, new `logPriceChange()` helper.

**Controllers (modify):**
- `controllers/OrdersController.php` — update `behaviors()`, `actionClose()`, add `actionMarketPrices()`, `actionMarketPricesFill()`.

**Views (create):**
- `views/orders/market-prices.php` — GridView for state=4.
- `views/orders/market-prices-fill.php` — fill form.

**Views (modify):**
- `views/orders/view.php` — CTA when `state = 4`.
- `views/menu/admin.php` — menu entry.
- `views/menu/office.php` — menu entry.

**Tests (create):**
- `tests/unit/models/OrdersHasMarketItemsTest.php` — unit test for `Orders::hasMarketItems()`.
- `tests/functional/MarketPricesFillCest.php` — end-to-end workflow test.

---

## Task 1: Migration — add `market_total_price` to `order_items`

**Files:**
- Create: `migrations/m260419_000000_add_market_total_price_to_order_items.php`

- [ ] **Step 1: Write the migration**

```php
<?php

use yii\db\Migration;

/**
 * Adds market_total_price column to order_items. Holds the total amount
 * paid for a bazar item in a closed order (not per-unit price).
 */
class m260419_000000_add_market_total_price_to_order_items extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%order_items}}',
            'market_total_price',
            $this->decimal(12, 2)->null()->comment('Общая сумма за базарную позицию')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%order_items}}', 'market_total_price');
    }
}
```

- [ ] **Step 2: Apply the migration**

Run: `php yii migrate --interactive=0`
Expected: `*** applied m260419_000000_add_market_total_price_to_order_items`

- [ ] **Step 3: Verify column exists**

Run: `php yii migrate/history --limit=3`
Expected: the new migration listed in history.

- [ ] **Step 4: Commit**

```bash
git add migrations/m260419_000000_add_market_total_price_to_order_items.php
git commit -m "Add market_total_price column to order_items

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

## Task 2: Extend `Orders` model — state 4 and `hasMarketItems()`

**Files:**
- Modify: `models/Orders.php`
- Test: `tests/unit/models/OrdersHasMarketItemsTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/unit/models/OrdersHasMarketItemsTest.php`:

```php
<?php

namespace tests\unit\models;

use app\models\Orders;
use Codeception\Test\Unit;

class OrdersHasMarketItemsTest extends Unit
{
    public function testStateLabelForMarketPricesFillExists()
    {
        $this->assertArrayHasKey(4, Orders::$states);
        $this->assertSame('Заполнение цен базара', Orders::$states[4]);
    }

    public function testHasMarketItemsMethodExists()
    {
        $this->assertTrue(
            method_exists(Orders::class, 'hasMarketItems'),
            'Orders::hasMarketItems() must be defined'
        );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/codecept run unit models/OrdersHasMarketItemsTest`
Expected: FAIL — `Failed asserting that an array has the key 4`.

- [ ] **Step 3: Add state 4 to `Orders::$states`**

In `models/Orders.php` replace the `$states` array:

```php
public static $states = [
    0 => "Новый",
    1 => "Отправлен",
    2 => "Завершен",
    3 => "Проверка офисом",
    4 => "Заполнение цен базара",
];
```

- [ ] **Step 4: Add `hasMarketItems()` method**

In `models/Orders.php`, add after the existing `canClose()` method:

```php
/**
 * Whether the order contains at least one item whose product belongs
 * to a bazar (is_market = 1) product group. Used by actionClose to
 * route the order into state 4 (Заполнение цен базара).
 *
 * @return bool
 */
public function hasMarketItems()
{
    return (new \yii\db\Query())
        ->from('order_items oi')
        ->innerJoin('product_groups_link pgl', 'pgl.productId = oi.productId')
        ->innerJoin('product_groups pg', 'pg.id = pgl.productGroupId')
        ->where([
            'oi.orderId' => $this->id,
            'pg.is_market' => 1,
            'oi.deleted_at' => null,
        ])
        ->exists();
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/codecept run unit models/OrdersHasMarketItemsTest`
Expected: PASS (both tests).

- [ ] **Step 6: Commit**

```bash
git add models/Orders.php tests/unit/models/OrdersHasMarketItemsTest.php
git commit -m "Add market prices fill state and hasMarketItems check

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

## Task 3: Migration — price fields in `order_items_changelog`

**Files:**
- Create: `migrations/m260419_000001_add_price_fields_to_order_items_changelog.php`

- [ ] **Step 1: Write the migration**

```php
<?php

use yii\db\Migration;

/**
 * Adds old_price / new_price columns to order_items_changelog so that
 * market_total_price edits can be logged alongside quantity changes.
 */
class m260419_000001_add_price_fields_to_order_items_changelog extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%order_items_changelog}}',
            'old_price',
            $this->decimal(12, 2)->null()->comment('Старая сумма (для изменений цены)')
        );
        $this->addColumn(
            '{{%order_items_changelog}}',
            'new_price',
            $this->decimal(12, 2)->null()->comment('Новая сумма (для изменений цены)')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%order_items_changelog}}', 'new_price');
        $this->dropColumn('{{%order_items_changelog}}', 'old_price');
    }
}
```

- [ ] **Step 2: Apply the migration**

Run: `php yii migrate --interactive=0`
Expected: `*** applied m260419_000001_add_price_fields_to_order_items_changelog`

- [ ] **Step 3: Commit**

```bash
git add migrations/m260419_000001_add_price_fields_to_order_items_changelog.php
git commit -m "Add price fields to order_items_changelog

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

## Task 4: Extend `OrderItemsChangelog` with price logging

**Files:**
- Modify: `models/OrderItemsChangelog.php`

- [ ] **Step 1: Add the new action constant**

In `models/OrderItemsChangelog.php`, after `ACTION_RESTORED`:

```php
const ACTION_PRICE_UPDATED = 'price_updated';
```

- [ ] **Step 2: Register the new fields in `rules()` and extend action whitelist**

Replace `rules()` with:

```php
public function rules()
{
    return [
        [['orderId', 'productId', 'action', 'userId'], 'required'],
        [['orderId', 'userId'], 'integer'],
        [['old_quantity', 'new_quantity', 'old_price', 'new_price'], 'number'],
        [['created_at'], 'safe'],
        [['productId'], 'string', 'max' => 36],
        [['action'], 'string', 'max' => 20],
        [['action'], 'in', 'range' => [
            self::ACTION_ADDED,
            self::ACTION_DELETED,
            self::ACTION_UPDATED,
            self::ACTION_RESTORED,
            self::ACTION_PRICE_UPDATED,
        ]],
        [['orderId'], 'exist', 'skipOnError' => true, 'targetClass' => Orders::class, 'targetAttribute' => ['orderId' => 'id']],
        [['productId'], 'exist', 'skipOnError' => true, 'targetClass' => Products::class, 'targetAttribute' => ['productId' => 'id']],
        [['userId'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['userId' => 'id']],
    ];
}
```

- [ ] **Step 3: Add labels for new columns**

In `attributeLabels()` add:

```php
'old_price' => 'Старая сумма',
'new_price' => 'Новая сумма',
```

- [ ] **Step 4: Extend `getActionLabel()`**

Replace the `$labels` array inside `getActionLabel()`:

```php
$labels = [
    self::ACTION_ADDED => 'Добавлено',
    self::ACTION_DELETED => 'Удалено',
    self::ACTION_UPDATED => 'Изменено',
    self::ACTION_RESTORED => 'Восстановлено',
    self::ACTION_PRICE_UPDATED => 'Изменена сумма базара',
];
```

- [ ] **Step 5: Add `logPriceChange()` static helper**

Append to the class, after `log()`:

```php
/**
 * Logs a change of market_total_price on an order item.
 *
 * @param int $orderId
 * @param string $productId
 * @param float|null $oldPrice
 * @param float|null $newPrice
 * @param int|null $userId
 * @return bool
 */
public static function logPriceChange($orderId, $productId, $oldPrice, $newPrice, $userId = null)
{
    if ($userId === null) {
        $userId = Yii::$app->user->id;
    }

    $changelog = new self();
    $changelog->orderId = $orderId;
    $changelog->productId = $productId;
    $changelog->action = self::ACTION_PRICE_UPDATED;
    $changelog->old_price = $oldPrice;
    $changelog->new_price = $newPrice;
    $changelog->userId = $userId;

    return $changelog->save();
}
```

- [ ] **Step 6: Commit**

```bash
git add models/OrderItemsChangelog.php
git commit -m "Log market total price changes in order_items_changelog

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

## Task 5: Route `actionClose` into state 4 when order has bazar items

**Files:**
- Modify: `controllers/OrdersController.php:864-875` (the state=2 branch inside `actionClose`).

- [ ] **Step 1: Replace the successful-close block**

Current code (lines 864–875):

```php
if ($in || $out) {
    $model->state = 2;
    $model->save();
    Yii::info("Заказ #{$id} успешно закрыт (state=2)", 'iiko');

    $d = date('d.m.Y H:i');
    $text = "Заказ закрыть: <b>#{$model->id}</b>\nЗаказчик: {$model->user->username}\nДата: {$d}";
    $bot = new TelegramBot();
    $bot->sendMessage(-1001879316029, $text, 'HTML');
} else {
    Yii::warning("Заказ #{$id} НЕ закрыт: in=" . var_export($in, true) . ", out=" . var_export($out, true), 'iiko');
}
```

Replace with:

```php
if ($in || $out) {
    $hasMarket = $model->hasMarketItems();
    $model->state = $hasMarket ? 4 : 2;
    $model->save();
    Yii::info("Заказ #{$id} успешно закрыт (state={$model->state})", 'iiko');

    $d = date('d.m.Y H:i');
    if ($hasMarket) {
        $text = "Заказ ожидает заполнения цен базара: <b>#{$model->id}</b>\nЗаказчик: {$model->user->username}\nДата: {$d}";
    } else {
        $text = "Заказ закрыть: <b>#{$model->id}</b>\nЗаказчик: {$model->user->username}\nДата: {$d}";
    }
    $bot = new TelegramBot();
    $bot->sendMessage(-1001879316029, $text, 'HTML');
} else {
    Yii::warning("Заказ #{$id} НЕ закрыт: in=" . var_export($in, true) . ", out=" . var_export($out, true), 'iiko');
}
```

- [ ] **Step 2: Manual smoke test**

Using a dev DB with an existing order containing at least one item whose product belongs to a group with `is_market=1`:

1. `php yii migrate` (ensure Task 1 + Task 3 migrations applied).
2. Log in as a user who can close orders.
3. Open an order in state 1 with bazar items, trigger Close.
4. Verify DB: `SELECT state FROM orders WHERE id = <id>;` → `4`.
5. Repeat with an order that has no bazar items → `state = 2` (existing behavior).

Document the check result in the commit message body if anything unexpected surfaces.

- [ ] **Step 3: Commit**

```bash
git add controllers/OrdersController.php
git commit -m "Route closed orders with bazar items into state 4

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

## Task 6: Allow `market-prices` / `market-prices-fill` in `OrdersController::behaviors()`

**Files:**
- Modify: `controllers/OrdersController.php:44-104` (the `behaviors()` method).

- [ ] **Step 1: Extend the admin/office rule**

Find the first rule entry (currently lines 53–61):

```php
[
    'actions' => ['index', 'return', 'list', 'view', 'delete', 'close', 'try-again', 'return-back', 'return-to-new', 'restore-item', 'get-changelog'],
    'allow' => true,
    'roles' => [
        User::ROLE_ADMIN,
        User::ROLE_OFFICE
    ],
],
```

Replace with:

```php
[
    'actions' => ['index', 'return', 'list', 'view', 'delete', 'close', 'try-again', 'return-back', 'return-to-new', 'restore-item', 'get-changelog', 'market-prices', 'market-prices-fill'],
    'allow' => true,
    'roles' => [
        User::ROLE_ADMIN,
        User::ROLE_OFFICE,
        User::ROLE_OFFICE_MANAGER,
    ],
],
```

- [ ] **Step 2: Add POST verb constraint for `market-prices-fill`**

Replace the `'verbs'` block (lines 97–102):

```php
'verbs' => [
    'class' => VerbFilter::className(),
    'actions' => [
        'close' => ['post'],
    ],
],
```

with:

```php
'verbs' => [
    'class' => VerbFilter::className(),
    'actions' => [
        'close' => ['post'],
        'market-prices-fill' => ['get', 'post'],
    ],
],
```

- [ ] **Step 3: Commit**

```bash
git add controllers/OrdersController.php
git commit -m "Allow market-prices actions for admin/office/office-manager

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

## Task 7: `actionMarketPrices` — list of orders in state 4

**Files:**
- Modify: `controllers/OrdersController.php` (append before `actionMarketPricesFill`).

- [ ] **Step 1: Add the action**

Append to `OrdersController` (after `actionClose`, before `actionSend`):

```php
/**
 * List of orders waiting for bazar price fill (state = 4).
 */
public function actionMarketPrices()
{
    $searchModel = new OrderSearch();
    $params = Yii::$app->request->queryParams;
    $params['OrderSearch']['state'] = 4;
    $dataProvider = $searchModel->search($params, false);

    return $this->render('market-prices', [
        'searchModel' => $searchModel,
        'dataProvider' => $dataProvider,
    ]);
}
```

Note: `OrderSearch::search($params, $active = true, ...)` branches on `$active`. Passing `false` activates the `state` filter path (see `models/OrderSearch.php:73-77`).

- [ ] **Step 2: Commit (after view is created in Task 8, but commit controller change now to keep tasks atomic)**

Only commit if view exists or controller route is not yet wired in a menu — in this plan the menu is added later (Task 11), so the dangling route is harmless. Commit now:

```bash
git add controllers/OrdersController.php
git commit -m "Add actionMarketPrices list for state 4 orders

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

## Task 8: Create `views/orders/market-prices.php` list view

**Files:**
- Create: `views/orders/market-prices.php`

- [ ] **Step 1: Write the view**

```php
<?php

use app\models\Orders;
use app\models\OrderItems;
use kartik\date\DatePicker;
use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\OrderSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Заполнение цен базара';
$this->params['breadcrumbs'][] = $this->title;

$bazarCount = function ($orderId) {
    return (int)(new \yii\db\Query())
        ->from('order_items oi')
        ->innerJoin('product_groups_link pgl', 'pgl.productId = oi.productId')
        ->innerJoin('product_groups pg', 'pg.id = pgl.productGroupId')
        ->where([
            'oi.orderId' => $orderId,
            'pg.is_market' => 1,
            'oi.deleted_at' => null,
        ])
        ->count();
};

$bazarFilledCount = function ($orderId) {
    return (int)(new \yii\db\Query())
        ->from('order_items oi')
        ->innerJoin('product_groups_link pgl', 'pgl.productId = oi.productId')
        ->innerJoin('product_groups pg', 'pg.id = pgl.productGroupId')
        ->where([
            'oi.orderId' => $orderId,
            'pg.is_market' => 1,
            'oi.deleted_at' => null,
        ])
        ->andWhere(['is not', 'oi.market_total_price', null])
        ->andWhere(['>', 'oi.market_total_price', 0])
        ->count();
};
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
    </div>
    <hr>
    <div class="content table-responsive">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'summary' => false,
            'tableOptions' => ['class' => 'table table-hover'],
            'columns' => [
                ['class' => 'yii\grid\SerialColumn', 'contentOptions' => ['width' => 40, 'class' => 'text-center']],
                [
                    'attribute' => 'id',
                    'format' => 'html',
                    'value' => function ($model) {
                        return Html::a($model->id, ['orders/market-prices-fill', 'id' => $model->id]);
                    },
                ],
                [
                    'label' => 'Филиал',
                    'value' => function ($model) {
                        return $model->store ? $model->store->name : '-';
                    },
                ],
                [
                    'attribute' => 'date',
                    'value' => function ($model) {
                        return date('d.m.Y', strtotime($model->date));
                    },
                    'filter' => DatePicker::widget([
                        'model' => $searchModel,
                        'attribute' => 'date',
                        'value' => $searchModel->date,
                        'type' => DatePicker::TYPE_INPUT,
                        'pluginOptions' => ['format' => 'yyyy-mm-dd', 'todayHighlight' => true],
                    ]),
                    'contentOptions' => ['width' => 120, 'class' => 'text-center'],
                ],
                [
                    'label' => 'Базарных позиций',
                    'value' => function ($model) use ($bazarCount, $bazarFilledCount) {
                        $total = $bazarCount($model->id);
                        $filled = $bazarFilledCount($model->id);
                        return "{$filled} / {$total}";
                    },
                    'contentOptions' => ['class' => 'text-center'],
                ],
                [
                    'attribute' => 'state',
                    'value' => function ($model) {
                        return Orders::$states[$model->state];
                    },
                    'filter' => false,
                    'contentOptions' => ['width' => 180, 'class' => 'text-center'],
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{fill}',
                    'buttons' => [
                        'fill' => function ($url, $model) {
                            return Html::a('Заполнить', ['orders/market-prices-fill', 'id' => $model->id], [
                                'class' => 'btn btn-success btn-xs',
                            ]);
                        },
                    ],
                    'contentOptions' => ['width' => 120, 'class' => 'text-center'],
                ],
            ],
        ]); ?>
    </div>
</div>
```

- [ ] **Step 2: Manual smoke test**

Navigate to `/index.php?r=orders/market-prices` (or the project's route equivalent) as ADMIN. Expect an empty or filled list with links pointing at `orders/market-prices-fill?id=...`. Clicking a link should 404 (action not yet implemented) — that's fine for this step.

- [ ] **Step 3: Commit**

```bash
git add views/orders/market-prices.php
git commit -m "Add market prices list view

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

## Task 9: `actionMarketPricesFill` — render + save (no finish yet)

**Files:**
- Modify: `controllers/OrdersController.php` (append after `actionMarketPrices`).

- [ ] **Step 1: Add the action**

```php
/**
 * Fill market totals for bazar items in a closed order (state = 4).
 * POST action:
 *   - 'save'   → store values, stay in state 4
 *   - 'finish' → validate all bazar items filled, transition to state 2
 */
public function actionMarketPricesFill($id)
{
    $model = $this->findModel($id);

    if ((int)$model->state !== 4) {
        Yii::$app->session->setFlash('error', "Заказ #{$model->id} не находится в статусе «Заполнение цен базара».");
        return $this->redirect(['orders/market-prices']);
    }

    $items = Orders::getOrderProducts($model->id, true);

    if (Yii::$app->request->isPost) {
        $action = Yii::$app->request->post('action', 'save');
        $prices = Yii::$app->request->post('Prices', []);

        $saved = $this->saveMarketPrices($model, $items, $prices);

        if ($action === 'finish') {
            $missing = $this->findUnfilledMarketItems($model->id);
            if (!empty($missing)) {
                Yii::$app->session->setFlash(
                    'error',
                    'Не у всех базарных позиций заполнена сумма: ' . implode(', ', $missing)
                );
                return $this->redirect(['orders/market-prices-fill', 'id' => $model->id]);
            }

            $model->state = 2;
            $model->save(false, ['state']);
            Yii::info("Заказ #{$model->id}: цены базара заполнены, переход в state=2", 'market-prices');
            Yii::$app->session->setFlash('success', 'Цены базара заполнены, заказ завершён.');
            return $this->redirect(['orders/view', 'id' => $model->id]);
        }

        Yii::$app->session->setFlash('success', "Сохранено позиций: {$saved}");
        return $this->redirect(['orders/market-prices-fill', 'id' => $model->id]);
    }

    return $this->render('market-prices-fill', [
        'model' => $model,
        'items' => $items,
    ]);
}

/**
 * Persists market_total_price values and logs changes.
 * @return int number of items whose price was changed.
 */
protected function saveMarketPrices(Orders $model, array $items, array $prices)
{
    $changed = 0;
    $itemsByProduct = [];
    foreach ($items as $row) {
        $itemsByProduct[$row['productId']] = $row;
    }

    foreach ($prices as $productId => $value) {
        if (!isset($itemsByProduct[$productId])) {
            continue;
        }
        $value = trim((string)$value);
        if ($value === '') {
            continue;
        }
        $newPrice = (float)$value;
        if ($newPrice < 0) {
            continue;
        }

        $oi = OrderItems::findOne(['orderId' => $model->id, 'productId' => $productId]);
        if ($oi === null) {
            continue;
        }

        $oldPrice = $oi->market_total_price !== null ? (float)$oi->market_total_price : null;
        if ($oldPrice !== null && abs($oldPrice - $newPrice) < 0.005) {
            continue;
        }

        $oi->market_total_price = $newPrice;
        if ($oi->save(false, ['market_total_price'])) {
            OrderItemsChangelog::logPriceChange($model->id, $productId, $oldPrice, $newPrice);
            $changed++;
        }
    }

    return $changed;
}

/**
 * Returns human-readable identifiers of bazar items whose
 * market_total_price is empty or zero.
 *
 * @param int $orderId
 * @return string[] product names
 */
protected function findUnfilledMarketItems($orderId)
{
    $rows = (new Query())
        ->select(['p.name'])
        ->from('order_items oi')
        ->innerJoin('products p', 'p.id = oi.productId')
        ->innerJoin('product_groups_link pgl', 'pgl.productId = oi.productId')
        ->innerJoin('product_groups pg', 'pg.id = pgl.productGroupId')
        ->where([
            'oi.orderId' => $orderId,
            'pg.is_market' => 1,
            'oi.deleted_at' => null,
        ])
        ->andWhere(['or',
            ['oi.market_total_price' => null],
            ['<=', 'oi.market_total_price', 0],
        ])
        ->all();

    return array_map(function ($r) { return $r['name']; }, $rows);
}
```

- [ ] **Step 2: Commit**

```bash
git add controllers/OrdersController.php
git commit -m "Add actionMarketPricesFill save/finish workflow

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

## Task 10: Create `views/orders/market-prices-fill.php`

**Files:**
- Create: `views/orders/market-prices-fill.php`

- [ ] **Step 1: Write the view**

```php
<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Orders */
/* @var $items array */

$this->title = "Цены базара — заказ #{$model->id}";
$this->params['breadcrumbs'][] = ['label' => 'Заполнение цен базара', 'url' => ['orders/market-prices']];
$this->params['breadcrumbs'][] = "#{$model->id}";
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right">
            <?= Html::a('Назад', ['orders/market-prices'], ['class' => 'btn btn-primary btn-fill']) ?>
        </p>
    </div>
    <hr>

    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <?= Yii::$app->session->getFlash('success') ?>
        </div>
    <?php endif; ?>

    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <?= Yii::$app->session->getFlash('error') ?>
        </div>
    <?php endif; ?>

    <div class="content">
        <dl class="dl-horizontal">
            <dt>Филиал</dt>
            <dd><?= Html::encode($model->store ? $model->store->name : '-') ?></dd>
            <dt>Дата</dt>
            <dd><?= Html::encode(date('d.m.Y', strtotime($model->date))) ?></dd>
            <dt>Комментарий</dt>
            <dd><?= nl2br(Html::encode($model->comment ?: '-')) ?></dd>
        </dl>

        <?php if (empty($items)): ?>
            <div class="alert alert-warning">В заказе нет базарных позиций.</div>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">

                <table class="table table-hover" id="market-prices-table">
                    <thead>
                        <tr>
                            <th>Продукт</th>
                            <th>Ед. изм.</th>
                            <th class="text-right">Кол-во заказа</th>
                            <th class="text-right">Факт приёма</th>
                            <th class="text-right" style="width: 180px;">Сумма за позицию</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $total = 0;
                    foreach ($items as $row):
                        $fact = $row['factStoreQuantity'];
                        if ($fact === null || $fact === '' || (float)$fact == 0.0) {
                            $fact = $row['factSupplierQuantity'];
                        }
                        $price = $row['market_total_price'];
                        if ($price !== null && $price !== '') {
                            $total += (float)$price;
                        }
                    ?>
                        <tr>
                            <td><?= Html::encode($row['name']) ?></td>
                            <td><?= Html::encode($row['mainUnit']) ?></td>
                            <td class="text-right"><?= Html::encode($row['quantity']) ?></td>
                            <td class="text-right"><?= Html::encode($fact) ?></td>
                            <td class="text-right">
                                <input type="number" step="0.01" min="0"
                                       class="form-control market-price-input"
                                       name="Prices[<?= Html::encode($row['productId']) ?>]"
                                       value="<?= $price !== null ? Html::encode((string)(float)$price) : '' ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-right">Итого:</th>
                            <th class="text-right"><span id="market-prices-total"><?= number_format($total, 2, '.', ' ') ?></span></th>
                        </tr>
                    </tfoot>
                </table>

                <div class="form-group">
                    <button type="submit" name="action" value="save" class="btn btn-default btn-fill">Сохранить</button>
                    <button type="submit" name="action" value="finish" class="btn btn-success btn-fill">Сохранить и завершить</button>
                </div>
            </form>

            <script>
                (function () {
                    var inputs = document.querySelectorAll('.market-price-input');
                    var totalEl = document.getElementById('market-prices-total');
                    function recalc() {
                        var sum = 0;
                        inputs.forEach(function (el) {
                            var v = parseFloat(el.value);
                            if (!isNaN(v)) sum += v;
                        });
                        totalEl.textContent = sum.toFixed(2);
                    }
                    inputs.forEach(function (el) { el.addEventListener('input', recalc); });
                })();
            </script>
        <?php endif; ?>
    </div>
</div>
```

- [ ] **Step 2: Manual smoke test**

- Open a state=4 order via `orders/market-prices-fill?id=<id>`; verify only bazar items are shown.
- Fill values, click «Сохранить» — flash `Сохранено позиций: N`, values persist after reload.
- Clear one value, click «Сохранить и завершить» — flash error listing the unfilled item name.
- Fill all, click «Сохранить и завершить» — redirect to `orders/view?id=<id>`, order now `state=2`.

- [ ] **Step 3: Commit**

```bash
git add views/orders/market-prices-fill.php
git commit -m "Add market prices fill form view

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

## Task 11: CTA on `orders/view.php` for state 4

**Files:**
- Modify: `views/orders/view.php`

- [ ] **Step 1: Locate the action buttons block**

Open `views/orders/view.php` and find the top action bar containing the existing «Назад» button (around lines 21–24). We will insert a conditional CTA just above the `<hr>` after that bar.

- [ ] **Step 2: Add the CTA**

Immediately after the closing `</p>` of the action bar (line 24) and before `<hr>` (line 25), insert:

```php
<?php if ((int)$model->state === 4 && in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_OFFICE, User::ROLE_OFFICE_MANAGER])): ?>
    <p>
        <?= Html::a('Заполнить цены базара', ['orders/market-prices-fill', 'id' => $model->id], [
            'class' => 'btn btn-success btn-fill',
        ]) ?>
    </p>
<?php endif; ?>
```

`User` and `Html` are already imported at the top of the file (lines 4–5).

- [ ] **Step 3: Manual smoke test**

Open any order in state 4 as admin: the green button appears and links to the fill page. Open a state=2 order: button absent.

- [ ] **Step 4: Commit**

```bash
git add views/orders/view.php
git commit -m "Show fill-bazar-prices CTA on orders/view for state 4

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

## Task 12: Menu entries for admin and office

**Files:**
- Modify: `views/menu/admin.php`
- Modify: `views/menu/office.php`

- [ ] **Step 1: Add the entry to `admin.php`**

In `views/menu/admin.php`, after the «Заказы блюд» `<li>` (around line 22) and before «Накадная» (line 23), insert:

```php
    <li class="<?= Dashboard::isNavActive('orders', 'market-prices') ? 'active' : '' ?>">
        <?= Html::a('Цены базара', ['orders/market-prices'], ['class' => 'nav-link']) ?>
    </li>
```

- [ ] **Step 2: Add the entry to `office.php`**

In `views/menu/office.php`, after the «Заказы» `<li>` (around line 13) and before «Приём накладных» (line 14), insert the same block:

```php
    <li class="<?= Dashboard::isNavActive('orders', 'market-prices') ? 'active' : '' ?>">
        <?= Html::a('Цены базара', ['orders/market-prices'], ['class' => 'nav-link']) ?>
    </li>
```

- [ ] **Step 3: Manual smoke test**

Log in as admin — nav bar shows «Цены базара»; clicking opens the list. Log in as office user — same entry visible.

Note: if there is a distinct menu file for `ROLE_OFFICE_MANAGER`, add the same entry there. `grep -l ROLE_OFFICE_MANAGER views/` returned no results, suggesting office managers inherit the admin menu via `Dashboard::isOrderMan()` — verify locally and extend if needed.

- [ ] **Step 4: Commit**

```bash
git add views/menu/admin.php views/menu/office.php
git commit -m "Add 'Цены базара' menu entry for admin and office

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

## Task 13: Functional test — full workflow

**Files:**
- Create: `tests/functional/MarketPricesFillCest.php`

- [ ] **Step 1: Inspect existing functional tests**

Run: `ls tests/functional/`
If no similar Cest exists, this file establishes the pattern. If the project lacks usable functional fixtures for orders/products/product_groups, downgrade this task to a documented manual QA checklist (see Step 3-alt).

- [ ] **Step 2: Write the Cest (if fixtures exist)**

```php
<?php

use app\models\Orders;
use app\models\OrderItems;

class MarketPricesFillCest
{
    /**
     * Requires fixtures that seed: an order in state=1 with two items,
     * one belonging to a product group with is_market=1, one not.
     * Adjust IDs to match tests/_data fixtures.
     */
    public function closeRoutesOrderWithBazarItemsIntoStateFour(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1); // admin fixture
        $orderId = 1;        // fixture order with one bazar item
        $I->sendPost("/orders/close?id={$orderId}");

        $order = Orders::findOne($orderId);
        $I->assertSame(4, (int)$order->state);
    }

    public function finishTransitionsStateTwo(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $orderId = 1;

        $I->sendPost("/orders/market-prices-fill?id={$orderId}", [
            'action' => 'finish',
            'Prices' => [
                'product-bazar-id' => '150.50',
            ],
        ]);

        $order = Orders::findOne($orderId);
        $I->assertSame(2, (int)$order->state);
        $item = OrderItems::findOne(['orderId' => $orderId, 'productId' => 'product-bazar-id']);
        $I->assertEqualsWithDelta(150.50, (float)$item->market_total_price, 0.001);
    }
}
```

- [ ] **Step 3: Run the tests**

Run: `vendor/bin/codecept run functional MarketPricesFillCest`
Expected: both tests pass.

- [ ] **Step 3-alt: If fixtures are not available**

Document a manual QA checklist in `docs/superpowers/plans/2026-04-19-market-prices-fill-status-qa.md`:

1. Create an order with both bazar and non-bazar items via admin UI.
2. Close the order — verify state=4 in DB.
3. Open `orders/market-prices` — entry appears.
4. Open fill page — only bazar items listed, fact quantity shown.
5. Save partial prices — flash success, values persist.
6. Finish with unfilled item — flash error lists it.
7. Finish with all filled — redirect to `orders/view`, state=2.
8. Open `orders/view` for the completed order — CTA gone.
9. Changelog table has `price_updated` rows with correct old/new.

- [ ] **Step 4: Commit**

```bash
git add tests/functional/MarketPricesFillCest.php docs/superpowers/plans/2026-04-19-market-prices-fill-status-qa.md
git commit -m "Add workflow tests and manual QA checklist for market prices fill

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>"
```

---

## Self-review notes (applied inline)

- **Spec coverage:** all six spec points covered — status constant (Task 2), conditional transition in `actionClose` (Task 5), list action (Task 7) + view (Task 8), fill page (Tasks 9, 10), per-item `market_total_price` field (Task 1), and price-change changelog (Tasks 3, 4). CTA and menu entries (Tasks 11, 12) realize the "Отображение в других местах" section of the spec.
- **Placeholder scan:** no `TBD` / `TODO` / "implement later" — Task 13 provides two concrete branches (fixtures-present vs manual QA) with exact content.
- **Type consistency:** `Orders::hasMarketItems()`, `OrderItemsChangelog::logPriceChange()`, `OrderItems::$market_total_price`, state value `4` are named identically across all tasks and matching views.
- **Scope check:** single-subsystem, single-plan scope. Telegram message body uses a different wording for `state=4` transitions (see Task 5).
