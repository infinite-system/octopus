<?php

namespace Tests;

use Illuminate\Support\Facades\DB;
use Tests\Models\User;
use Tests\Models\UserProfile;

class CategoryInverseTest extends TestCase
{

    public function testLazyCategoryOneInverse()
    {
        $userProfile = UserProfile::where('id', 24)->first();
        $this->assertEquals(15, $userProfile->one_user_from_billing->id);
    }

    public function testLazyCategoryOneInverseAnotherSet()
    {
        $user = UserProfile::where('id', 25)->first();
        $this->assertEquals(16, $user->one_user_from_filling->id);
    }

    public function testLazyCategoryManyInverse()
    {
        $userProfile = UserProfile::where('id', 24)->first();
        $this->assertEquals([15], $userProfile->many_users_from_billing->pluck('id')->all());
    }

    public function testLazyCategoryManyInverseAnotherSet()
    {
        $user = UserProfile::where('id', 25)->first();
        $this->assertEquals([16], $user->many_users_from_filling->pluck('id')->all());
    }

    public function testLazyCategoryManyInverseMultiples()
    {
        $userProfile = UserProfile::where('id', 30)->first();
        $this->assertEquals([19,20], $userProfile->many_users_from_billing->pluck('id')->all());
    }

    public function testLazyCategoryManyInverseAnotherSetOfMultiples()
    {
        $user = UserProfile::where('id', 31)->first();
        $this->assertEquals([19,20], $user->many_users_from_filling->pluck('id')->all());
    }

    // Eager loading tests

    public function testEagerCategoryOneInverse()
    {
        $userProfile = UserProfile::where('id', 24)->with('one_user_from_filling')->first();
        $this->assertEquals(15, $userProfile->one_user_from_billing->id);
    }

    public function testEagerCategoryOneInverseAnotherSet()
    {
        $user = UserProfile::where('id', 25)->with('one_user_from_billing')->first();
        $this->assertEquals(16, $user->one_user_from_filling->id);
    }

    public function testEagerCategoryManyInverse()
    {
        $userProfile = UserProfile::where('id', 24)->with('many_users_from_billing')->first();
        $this->assertEquals([15], $userProfile->many_users_from_billing->pluck('id')->all());
    }

    public function testEagerCategoryManyInverseAnotherSet()
    {
        $user = UserProfile::where('id', 25)->with('many_users_from_filling')->first();
        $this->assertEquals([16], $user->many_users_from_filling->pluck('id')->all());
    }

    public function testEagerCategoryManyInverseMultiples()
    {
        $userProfile = UserProfile::where('id', 30)->with('many_users_from_billing')->first();
        $this->assertEquals([19,20], $userProfile->many_users_from_billing->pluck('id')->all());
    }

    public function testEagerCategoryManyInverseAnotherSetOfMultiples()
    {
        $user = UserProfile::where('id', 31)->with('many_users_from_filling')->first();
        $this->assertEquals([19,20], $user->many_users_from_filling->pluck('id')->all());
    }

}
