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
