<?php

namespace Tests;

use Tests\Models\User;

class MultiLevelTest extends TestCase
{

    public function testLazyLoading2LevelsOne()
    {
        $user = User::where('id', 8)->first();
        $this->assertEquals(21, $user->contact->user_profile->id);
    }

    public function testLazyLoading2LevelsOneAnotherSet()
    {
        $user = User::where('id', 9)->first();
        $this->assertEquals(22, $user->contact->user_profile->id);
    }

    public function testEagerLoading2LevelsOne()
    {
        $user = User::where('id', 8)->with('contact.user_profile')->first();
        $this->assertEquals(21, $user->contact->user_profile->id);
    }

    public function testEagerLoading2LevelsOneAnotherSet()
    {
        $user = User::where('id', 9)->with('contact.user_profile')->first();
        $this->assertEquals(22, $user->contact->user_profile->id);
    }

    public function testLazyLoading3LevelsOne()
    {
        $user = User::where('id', 8)->first();
        $this->assertEquals(31, $user->contact->user_profile->order->id);
    }

    public function testLazyLoading3LevelsOneAnotherSet()
    {
        $user = User::where('id', 9)->first();
        $this->assertEquals(32, $user->contact->user_profile->order->id);
    }

    public function testEagerLoading3LevelsOne()
    {
        $user = User::where('id', 8)->with('contact.user_profile.order')->first();
        $this->assertEquals(31, $user->contact->user_profile->order->id);
    }

    public function testEagerLoading3LevelsOneAnotherSet()
    {
        $user = User::where('id', 9)->with('contact.user_profile.order')->first();
        $this->assertEquals(32, $user->contact->user_profile->order->id);
    }

}
