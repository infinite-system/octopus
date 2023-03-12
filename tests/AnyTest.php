<?php
namespace Tests;

use Tests\Models\Order;

class AnyTest extends TestCase
{
	public function testAnyActualTag()
	{
		$order = Order::where('id', 31)->first();
//		dump($order->order_category->toArray());
		$this->assertEquals(12, $order->order_category->target_type);
	}
	public function testAnyConnection()
	{
		$order = Order::where('id', 31)->first();
		$this->assertEquals(1, $order->order_category_any[0]->target_type);
		$this->assertEquals(21, $order->order_category_any[0]->target_id);
		$this->assertEquals(8, $order->order_category_any[1]->target_type);
		$this->assertEquals(24, $order->order_category_any[1]->target_id);
		$this->assertEquals(11, $order->order_category_any[2]->target_type);
		$this->assertEquals(11, $order->order_category_any[2]->target_id);
	}
}

