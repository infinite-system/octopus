<?php

namespace App;

use X\Octopus\Model as Octopus;

class UserProfile extends Octopus
{
    protected $fillable = ['id', 'company', 'created_at', 'updated_at'];

    const default = 'Default';

    function payment_sources(){
      return $this->tagManyInverse(PaymentSource::class, status:
              [ModalTag::status['active'], ModalTag::status['hidden']]);
    }

    function user(){
      return $this->tagOneThrough(Category::class,[
          (int) env('category_ids.user_profile_permission_owner'),
          (int) env('category_ids.user_profile_permission_admin')
        ], User::class,
	      status: [ModalTag::status['active'], ModalTag::status['hidden']])
        // Owner user has priority
        // if owner user gets deleted, the admin user can still access the profile as the user
        ->orderByRaw("CASE WHEN ".tagTable().".target_id = '".(int) env('category_ids.user_profile_permission_owner')."'
                      THEN 0 ELSE 1 END asc");
    }

    function ownerAdmin(){
      return $this->tagOneThrough(Category::class,[
          (int) env('category_ids.user_profile_permission_owner'),
          (int) env('category_ids.user_profile_permission_admin')
        ], User::class)
        ->orderByRaw("CASE WHEN ".tagTable().".target_id = '".(int) env('category_ids.user_profile_permission_owner')."'
                      THEN 0 ELSE 1 END asc");
    }

    function orders(){
      return $this->tagManyThroughInverse(Category::class, [
         (int) env('category_ids.BILLING_PROFILE_CATEGORY_ID'),
         (int) env('category_ids.FULFILLMENT_PROFILE_CATEGORY_ID')
        ], Order::class);
    }

    function lastOrder(){
      return $this->tagOneThroughInverse(Category::class, [
         (int) env('category_ids.BILLING_PROFILE_CATEGORY_ID'),
         (int) env('category_ids.FULFILLMENT_PROFILE_CATEGORY_ID')
        ], Order::class)
        ->orderBy('submitted_at', 'desc');
    }
}
