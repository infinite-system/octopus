<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


class Location extends Model
{
	use SoftDeletes;

	protected $dates = ['deleted_at'];

	const status = ['active' => 1, 'in_active' => 0];


	function contact() {
		return $this->tagOne(Contact::class);
	}

}
