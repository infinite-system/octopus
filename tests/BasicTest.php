<?php

namespace Tests;

use Tests\Models\User;

class BasicTest extends TestCase
{

    public function testLazyLoadingOne()
    {
        $contact = User::where('id', 4)->first()->contact;

        $this->assertEquals(11, $contact->id);
    }

    public function testLazyLoadingOneDifferentFromSameSet()
    {
        $contact = User::where('id', 5)->first()->contact;

        $this->assertEquals(12, $contact->id);
    }
    public function testEagerLoadingOne()
    {
        $user = User::where('id', 4)->with('contact')->first();

        $this->assertEquals(11, $user->contact->id);
    }

    public function testEagerLoadingOneDifferentFromSameSet()
    {
        $user = User::where('id', 5)->with('contact')->first();

        $this->assertEquals(12, $user->contact->id);
    }

    public function testLazyLoadingMultiple()
    {
        $contacts = User::where('id', 1)->first()->contacts;

        $this->assertEquals([11, 12], $contacts->pluck('id')->all());
    }

    public function testLazyLoadingMultipleDifferentSet()
    {
        $user = User::where('id', 2)->first();
        $this->assertEquals([14, 15], $user->contacts->pluck('id')->all());
    }

    public function testEagerLoadingMultiple()
    {
        $user = User::where('id', 1)->with('contacts')->first();

        $this->assertEquals([11, 12], $user->contacts->pluck('id')->all());
    }

    public function testEagerLoadingMultipleDifferentSet()
    {
        $user = User::where('id', 2)->with('contacts')->first();
        $this->assertEquals([14, 15], $user->contacts->pluck('id')->all());
    }

}
