<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    function user_profile() {
        return $this->tagOne(UserProfile::class);
    }

    function billing_profile() {
        return $this->tagOneThrough(Category::class, (int) getenv('ORDERS_BILLING_PROFILE'), UserProfile::class);
    }

    function billing_profile_category() {
        return $this->tagOneThrough(Category::class, (int) getenv('ORDERS_BILLING_PROFILE'));
    }

    function billing_profile_category_all() {
        return $this->tagManyThrough(Category::class, (int) getenv('ORDERS_BILLING_PROFILE'), 'any');
    }

    function billing_address() {
        return $this->tagOneThrough(Category::class, (int) getenv('ORDERS_BILLING_PROFILE'), Addresses::class);
    }

    function billing_contact() {
        return $this->tagOneThrough(Category::class, (int) getenv('ORDERS_BILLING_PROFILE'), Contact::class)
            ->where('contacts.status', 1);
    }

	function order_category() {
		return $this->tagOneThrough(Category::class, ORDER_CATEGORY);
	}

	function order_category_any() {
		return $this->tagManyThrough(Category::class, ORDER_CATEGORY, 'any');
	}
}
