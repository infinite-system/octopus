<?php
namespace Tests;

use Tests\Models\User;

class AnyTest extends TestCase
{
	public function testLazyLoading2LevelsOne()
	{
		$user = User::where('id', 8)->first();
		$this->assertEquals(21, $user->contact->user_profile->id);
	}
}

