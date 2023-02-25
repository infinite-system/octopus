<?php

namespace Tests;

use Tests\Models\Contact;

class BasicInverseTest extends TestCase
{


    public function testLazyLoadingInverseOne()
    {
        $user = Contact::where('id', 16)->first()->user;

        $this->assertEquals(8, $user->id);
    }

    public function testLazyLoadingInverseOneAnotherSet()
    {
        $user = Contact::where('id', 17)->first()->user;

        $this->assertEquals(9, $user->id);
    }

    public function testEagerLoadingInverseOne()
    {
        $contact = Contact::where('id', 16)->with('user')->first();

        $this->assertEquals(8, $contact->user->id);
    }

    public function testEagerLoadingInverseOneAnotherSet()
    {
        $contact = Contact::where('id', 17)->with('user')->first();

        $this->assertEquals(9, $contact->user->id);
    }


    public function testLazyLoadingInverseMany()
    {
        $users = Contact::where('id', 18)->first()->users;

        $this->assertEquals([10,11], $users->pluck('id')->all());
    }

    public function testLazyLoadingInverseManyAnotherSet()
    {
        $users = Contact::where('id', 19)->first()->users;

        $this->assertEquals([12,13,14], $users->pluck('id')->all());
    }

    public function testEagerLoadingInverseMany()
    {
        $contact = Contact::where('id', 18)->with('users')->first();

        $this->assertEquals([10,11], $contact->users->pluck('id')->all());
    }

    public function testEagerLoadingInverseManyAnotherSet()
    {
        $contact = Contact::where('id', 19)->with('users')->first();

        $this->assertEquals([12,13,14], $contact->users->pluck('id')->all());
    }
}
