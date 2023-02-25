<?php

namespace Tests;

use Illuminate\Support\Facades\DB;
use Tests\Models\User;
use Tests\Models\UserProfile;

class CategoryTest extends TestCase
{

    public function testLazyCategoryOne()
    {
        $user = User::where('id', 15)->first();
        $this->assertEquals(24, $user->billing_user_profile->id);
    }

    public function testLazyCategoryOneAnotherSet()
    {
        $user = User::where('id', 16)->first();
        $this->assertEquals(25, $user->filling_user_profile->id);
    }

    public function testEagerCategoryMany()
    {
        $user = User::where('id', 17)->with('billing_user_profiles')->first();
        $this->assertEquals([26, 27], $user->billing_user_profiles->pluck('id')->all());
    }

    public function testEagerCategoryManyAnotherSet()
    {
        $user = User::where('id', 18)->with('filling_user_profiles')->first();
        $this->assertEquals([28,29], $user->filling_user_profiles->pluck('id')->all());
    }


}
