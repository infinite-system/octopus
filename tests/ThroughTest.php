<?php

namespace Tests;

use Tests\Models\User;

class ThroughTest extends TestCase
{

    public function testEagerLoadingOneThrough()
    {
        $user = User::where('id', 4)->with('sales_contact')->first();

        $this->assertEquals(11, $user->sales_contact->id);
    }

    public function testEagerLoadingOneThroughDifferentSet()
    {
        $user = User::where('id', 6)->with('sales_contact')->first();

        $this->assertEquals(12, $user->sales_contact->id);
    }

    public function testEagerLoadingMultipleThrough()
    {
        $user = User::where('id', 5)->with('sales_contacts')->first();
        $this->assertEquals([12, 13, 14], $user->sales_contacts->pluck('id')->all());
    }

    public function testEagerLoadingMultipleThroughAndMultipleIds()
    {
        $user = User::where('id', 5)->with('sales_and_primary_contacts')->first();
        $this->assertEquals([12, 13, 14, 15], $user->sales_and_primary_contacts->pluck('id')->all());
    }



    public function testEagerLoadingMultipleThroughAndMultipleIdsDifferentSet()
    {
        $user = User::where('id', 7)->with('sales_and_primary_contacts')->first();
        $this->assertEquals([12, 13], $user->sales_and_primary_contacts->pluck('id')->all());
    }

    public function testLazyLoadingOneThrough()
    {
        $user = User::where('id', 4)->first();

        $this->assertEquals(11, $user->sales_contact->id);
    }

    public function testLazyLoadingOneThroughDifferentSet()
    {
        $user = User::where('id', 6)->first();

        $this->assertEquals(12, $user->sales_contact->id);
    }

    public function testLazyLoadingMultipleThrough()
    {
        $user = User::where('id', 5)->first();
        $this->assertEquals([12, 13, 14], $user->sales_contacts->pluck('id')->all());
    }

    public function testLazyLoadingMultipleThroughAndMultipleIds()
    {
        $user = User::where('id', 5)->first();
        $this->assertEquals([12, 13, 14, 15], $user->sales_and_primary_contacts->pluck('id')->all());
    }

    public function testLazyLoadingMultipleThroughAndMultipleIdsDifferentSet()
    {
        $user = User::where('id', 7)->first();
        $this->assertEquals([12, 13], $user->sales_and_primary_contacts->pluck('id')->all());
    }

}
