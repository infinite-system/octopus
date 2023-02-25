<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


class Contact extends Model
{
	use SoftDeletes;

	protected $dates = ['deleted_at'];

	const status = ['active' => 1, 'in_active' => 0, 'deleted' => 9];

	protected $table = 'contacts';

	protected $fillable = ['extension', 'id', 'name', 'email', 'phone', 'created_at', 'updated_at', 'deleted_at', 'status'];

	function user() {
		return $this->tagOneInverse(User::class);
	}

	function users() {
		return $this->tagManyInverse(User::class);
	}

    function user_profile() {
        return $this->tagOne(UserProfile::class);
    }

	function locations() {
		return $this->tagManyThroughInverse(Category::class, USER_SALES_CONTACT, Location::class);
	}

	function ext() {
		return $this->hasOne(TalkExtension::class, 'phone', 'phone');
	}
}
