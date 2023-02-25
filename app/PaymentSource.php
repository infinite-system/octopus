<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use X\Octopus\Model as Octopus;

class PaymentSource extends Octopus
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $casts = ['details' => 'array'];

    protected $fillable = ['id', 'created_at', 'updated_at', 'deleted_at', 'gateway_id', 'details', 'expired_at'];

    function user(){
      return $this->tagOne(User::class);
    }

    function charger_user(){
      return $this->tagOneThrough(Category::class,(int) env('category_ids.payment_source_created_by'), User::class);
    }

    function billing_profile(){
      return $this->tagOne(UserProfile::class);
    }
}
