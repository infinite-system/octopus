<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use X\Octopus\Model as Octopus;

class Location extends Octopus
{
	use SoftDeletes;

	protected $dates = ['deleted_at'];

	const status = ['active' => 1, 'in_active' => 0];

	protected $fillable = [
		'id', 'created_at', 'updated_at', 'deleted_at', 'timezone',
		'name', 'type', 'status', 're_stocking_fee', 'country', 'base_pickup_cost'
	];

	function country() {
		return $this->tagOne(Country::class);
	}

	function address() {
		return $this->tagOne(Addresses::class);
	}

	function currency() {
		return $this->tagOneThrough(Category::class,env('category_ids.DEFAULT_CATEGORY_ID'), Currency::class);
	}

	function terminal() {
		return $this->tagOne(ModalContent::class)
			->where('modal_contents.type_id', env('category_ids.MODAL_CONTENT_PAYMENT_SQUARE_TERMINAL'))
			->where('modal_contents.status', ModalContent::status['active']);
	}

	function taxes() {
		return $this->tagMany(Taxes::class)->where('taxes.status', Taxes::status['active']);
	}

	function measurement() {
		return $this->tagOneThrough(Category::class,[env('category_ids.units_metric'), env('category_ids.units_imperial')])
			->join('categories', tagTable().'.target_id', 'categories.id')
			->addSelect(['categories.*']);
	}

	function contact() {
		return $this->tagOne(Contact::class);
	}

	function languages() {
		return $this->tagMany(Lang::class)->orderBy('langs.id', 'asc');
	}

	function extensions() {
		return $this->tagManyInverse(TalkExtension::class);
	}

	function schedules() {
		return $this->hasMany(ModalSchedule::class, 'source_id', 'id')
			->where('modal_schedule.status', 1)
			->where('modal_schedule.source_type', m(Location::class));
	}
}
