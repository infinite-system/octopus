<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use X\Octopus\Model as Octopus;

class PaymentTerm extends Octopus
{
  use SoftDeletes;

  protected $table = 'payment_term';

  protected $fillable = ['id', 'user_id', 'term', 'created_at'];

  function user(){
    return $this->belongsTo(User::class);
  }
}
