<?php

namespace App;

use X\Octopus\Model as Octopus;

class Addresses extends Octopus
{
    const status = ['active' => 1, 'in_active' => 0, 'deleted' => 9];

    protected $fillable = ['id', 'updated_at', 'status', 'address', 'details', 'other_status'];

    protected $casts = ['details' => 'array'];

    function setCreatedAt($value) {
        // Do nothing.
    }

    protected $table = 'addresses';

    function location() {
        return $this->tagOne(Location::class)
                 ->where('addresses.status', Addresses::status['active']);
    }

    function billing_category(){
      return $this->tagOneThrough(Category::class,env('orders.address_billing'));
    }

    static function __firstOrCreate($arr){
      $existingAddress = self::where('address', $arr['address'])->first();
      return $existingAddress ? $existingAddress : self::create($arr);
    }
}
