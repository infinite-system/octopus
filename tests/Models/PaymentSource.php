<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;



class PaymentSource extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $casts = ['details' => 'array'];

    protected $fillable = ['id', 'created_at', 'updated_at', 'deleted_at', 'gateway_id', 'details', 'expired_at'];

    function user(){
      return $this->tagOne(User::class);
    }

    function charger_user(){
      return $this->tagOneThrough(Category::class, (int) getenv('PAYMENT_SOURCE_CREATED_BY'), User::class);
    }

    function billing_profile(){
      return $this->tagOne(UserProfile::class);
    }
}
