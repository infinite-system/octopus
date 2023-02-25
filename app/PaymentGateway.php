<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use X\Octopus\Model as Octopus;

class PaymentGateway extends Octopus
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $casts = ['details' => 'array'];

    protected $fillable = [
        'created_at', 'updated_at', 'deleted_at', 'name', 'type_id', 'details',
    ];

    public function type() {
        return $this->belongsTo(PaymentGateway::class, 'type_id', 'id');
    }

    public function accounts(){
        return $this->tagMany(Account::class)
                ->where('accounts.status', Account::status['active']);
    }

    public function locations(){
        return $this->tagMany(Location::class)
                ->where('locations.status', Location::status['active']);
    }

    public function websites(){
        return $this->tagMany(Website::class);
    }
}
