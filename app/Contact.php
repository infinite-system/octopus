<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use X\Octopus\Model as Octopus;

class Contact extends Octopus
{
	use SoftDeletes;

	protected $dates = ['deleted_at'];

	const status = ['active' => 1, 'in_active' => 0, 'deleted' => 9];

	protected $table = 'contacts';

	protected $fillable = ['extension', 'id', 'name', 'email', 'phone', 'created_at', 'updated_at', 'deleted_at', 'status'];

	function user() {
		return $this->tagOne(User::class);
	}

	function locations() {
		return $this->tagManyThroughInverse(Category::class, env('category_ids.location_sales_contact'), Location::class);
	}

	function ext() {
		return $this->hasOne(TalkExtension::class, 'phone', 'phone');
	}
}
