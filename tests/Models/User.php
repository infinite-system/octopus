<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use SoftDeletes;

    public function payment_sources() {
        return $this->tagMany(PaymentSource::class,
            status: [ModalTag::status['active']]);
    }

    public function payment_sources_hidden() {
        return $this->tagMany(PaymentSource::class,
            status: [ModalTag::status['hidden']]);
    }

    public function location() {
        return $this->tagMany(Location::class);
    }

    public function website() {
        return $this->tagMany(Website::class);
    }

    public function contacts() {
        return $this->tagMany(Contact::class);
    }

    public function contact() {
        return $this->tagOne(Contact::class);
    }

    public function sales_contacts() {
        return $this->tagMany(Contact::class, Category::class, USER_SALES_CONTACT);
    }

    public function sales_contact() {
        return $this->tagOne(Contact::class, Category::class, USER_SALES_CONTACT);
    }
    
    public function sales_and_primary_contacts() {
        return $this->tagMany(Contact::class, Category::class, [USER_SALES_CONTACT, USER_PRIMARY_CONTACT]);
    }

    public function sales_and_primary_contact() {
        return $this->tagOne(Contact::class, Category::class, USER_SALES_CONTACT);
    }

    public function billing_user_profile() {
        return $this->tagOneThrough(Category::class, BILLING_PROFILE, UserProfile::class);
    }

    public function filling_user_profile() {
        return $this->tagOneThrough(Category::class, FILLING_PROFILE, UserProfile::class);
    }

    public function billing_user_profiles() {
        return $this->tagManyThrough(Category::class, BILLING_PROFILE, UserProfile::class);
    }

    public function filling_user_profiles() {
        return $this->tagManyThrough(Category::class, FILLING_PROFILE, UserProfile::class);
    }
}
