<?php

namespace Tests\Models;



class UserProfile extends Model
{
    protected $fillable = ['id', 'company', 'created_at', 'updated_at'];

    const default = 'Default';

    function payment_sources() {
        return $this->tagManyInverse(PaymentSource::class, status: [ModalTag::status['active']]);
    }

    function user() {
        return $this->tagOneThrough(Category::class, [
            (int) getenv('USER_PROFILE_PERMISSION_OWNER'),
            (int) getenv('USER_PROFILE_PERMISSION_ADMIN')
        ], User::class, status: [ModalTag::status['active']]);
    }

    function one_user_from_billing() {
        return $this->tagOneThroughInverse(Category::class, [
            BILLING_PROFILE
        ], User::class, status: [ModalTag::status['active']]);
    }

    function one_user_from_filling() {
        return $this->tagOneThroughInverse(Category::class, [
            FILLING_PROFILE
        ], User::class, status: [ModalTag::status['active']]);
    }

    function many_users_from_billing() {
        return $this->tagManyThroughInverse(Category::class, [
            BILLING_PROFILE
        ], User::class, status: [ModalTag::status['active']]);
    }

    function many_users_from_filling() {
        return $this->tagManyThroughInverse(Category::class, [
            FILLING_PROFILE
        ], User::class, status: [ModalTag::status['active']]);
    }


    function order() {
        return $this->tagOne(Order::class);
    }

    function orders() {
        return $this->tagManyThroughInverse(Category::class, [
            (int) getenv('BILLING_PROFILE_CATEGORY_ID'),
            (int) getenv('FULFILLMENT_PROFILE_CATEGORY_ID')
        ], Order::class);
    }
}
