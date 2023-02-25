<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


class PaymentTransaction extends Model
{
    use SoftDeletes;

    const status = [
        'pending' => 0,
        'completed' => 1,
        'authorized' => 2,
        'our_pending' => 3,
        'canceled' => 8,
        'failed' => 9
    ];

    protected $fillable = [
        'id', 'user_id', 'payment_source_id', 'amount', 'status'
    ];

    function payment_source() {
        return $this->belongsTo(PaymentSource::class, 'payment_source_id');
    }

    function order() {
        return $this->tagOne(Order::class);
    }

    function charger_user() {
        return $this->tagOneThrough(Category::class, (int) getenv('category_ids.PAYMENT_TRANSACTION_CREATED_BY'), User::class);
    }
}
