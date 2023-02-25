<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use X\Octopus\Model as Octopus;

class Account extends Octopus
{
    use SoftDeletes;

    const status = ['active' => 1, 'in_active' => 0];

    protected $dates = ['deleted_at'];

    protected $casts = ['details' => 'array'];

    protected $fillable = [
        'id', 'name', 'status', 'details', 'created_at', 'updated_at', 'deleted_at'
    ];

    function scopebyLocationAndWebsite($q, $locationIds, $websiteIds, $prefix = '')
    {
      return $q->where(function($q) use ($prefix){
          $q->where('status', 1);
          if ($prefix !== '') $q->where('name', 'LIKE', "$prefix%");
        })
        ->whereNull('deleted_at')
        ->whereHas('location', function($q) use ($locationIds){
          $q->whereIn(tagTable().'.target_id', $locationIds);
        })
        ->whereHas('website', function($q) use ($websiteIds){
          $q->whereIn(tagTable().'.target_id', $websiteIds);
        });
    }

    function location() {
      return $this->tagMany(Location::class);
    }

    function website() {
      return $this->tagMany(Website::class);
    }

    function callLogExtensions(){
      return $this->hasMany(TalkExtension::class, 'account_id');
    }
}
