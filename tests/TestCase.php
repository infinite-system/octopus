<?php

namespace Tests;


use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase as Base;

use Tests\Models\Modal;
use Tests\Models\ModalTag;

use Tests\Models\User;
use Tests\Models\Order;
use Tests\Models\UserProfile;
use Tests\Models\Contact;

define('USER_SALES_CONTACT', 1);
define('USER_PRIMARY_CONTACT', 2);
define('BILLING_PROFILE', 3);
define('FILLING_PROFILE', 4);
define('ORDER_CATEGORY', 5);

abstract class TestCase extends Base
{

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void {
        parent::setUp();
        $this->createApplication();

        $config = require __DIR__ . '/config/database.php';

        $db = new DB();
        $db->addConnection($config[getenv('DATABASE') ?: 'sqlite']);
        $db->setAsGlobal();
        $db->bootEloquent();

        $this->migrate();

        $this->seed();
    }

    protected function migrate(): void {
        DB::schema()->dropAllTables();

        DB::schema()->create('modals', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
        });

        DB::schema()->create('tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('source_type');
            $table->unsignedBigInteger('target_type');
            $table->unsignedBigInteger('target_id');
            $table->unsignedBigInteger('thru_type');
            $table->unsignedBigInteger('thru_id');
            $table->unsignedBigInteger('status');
            $table->softDeletes();
        });

        DB::schema()->create('category', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
        });

        DB::schema()->create('countries', function (Blueprint $table) {
            $table->id();
        });

        DB::schema()->create('users', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });

        DB::schema()->create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });

        DB::schema()->create('contacts', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });

        DB::schema()->create('orders', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });

        DB::schema()->create('payment_sources', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });

        DB::schema()->create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->softDeletes();
        });


    }

    protected function seed(): void {
        Model::unguard();

        DB::table('modals')->insert([
            ['id' => 1, 'name' => 'Tests\\Models\\User'],
            ['id' => 2, 'name' => 'Tests\\Models\\Order'],
            ['id' => 3, 'name' => 'Tests\\Models\\Modal'],
            ['id' => 4, 'name' => 'Tests\\Models\\ModalTag'],
            ['id' => 5, 'name' => 'Tests\\Models\\UserProfile'],
            ['id' => 6, 'name' => 'Tests\\Models\\PaymentSource'],
            ['id' => 7, 'name' => 'Tests\\Models\\PaymentTransaction'],
            ['id' => 8, 'name' => 'Tests\\Models\\UserProfile'],
            ['id' => 9, 'name' => 'Tests\\Models\\Website'],
            ['id' => 10, 'name' => 'Tests\\Models\\Location'],
            ['id' => 11, 'name' => 'Tests\\Models\\Contact'],
            ['id' => 12, 'name' => 'Tests\\Models\\Category']
        ]);

        DB::table('category')->insert([
            ['id' => USER_SALES_CONTACT, 'name' => USER_SALES_CONTACT],
            ['id' => USER_PRIMARY_CONTACT, 'name' => USER_PRIMARY_CONTACT],
            ['id' => BILLING_PROFILE, 'name' => BILLING_PROFILE],
            ['id' => FILLING_PROFILE, 'name' => FILLING_PROFILE],
        ]);


        User::create(['id' => 1, 'deleted_at' => null]);
        User::create(['id' => 2, 'deleted_at' => null]);
        User::create(['id' => 3, 'deleted_at' => Carbon::yesterday()]);
        User::create(['id' => 4, 'deleted_at' => null]);
        User::create(['id' => 5, 'deleted_at' => null]);
        User::create(['id' => 6, 'deleted_at' => null]);
        User::create(['id' => 7, 'deleted_at' => null]);
        User::create(['id' => 8, 'deleted_at' => null]);
        User::create(['id' => 9, 'deleted_at' => null]);
        User::create(['id' => 10, 'deleted_at' => null]);
        User::create(['id' => 11, 'deleted_at' => null]);
        User::create(['id' => 12, 'deleted_at' => null]);
        User::create(['id' => 13, 'deleted_at' => null]);
        User::create(['id' => 14, 'deleted_at' => null]);
        User::create(['id' => 15, 'deleted_at' => null]);
        User::create(['id' => 16, 'deleted_at' => null]);
        User::create(['id' => 17, 'deleted_at' => null]);
        User::create(['id' => 18, 'deleted_at' => null]);
        User::create(['id' => 19, 'deleted_at' => null]);
        User::create(['id' => 20, 'deleted_at' => null]);

        Contact::create(['id' => 11, 'deleted_at' => null]);
        Contact::create(['id' => 12, 'deleted_at' => null]);
        Contact::create(['id' => 13, 'deleted_at' => null]);
        Contact::create(['id' => 14, 'deleted_at' => null]);
        Contact::create(['id' => 15, 'deleted_at' => null]);
        Contact::create(['id' => 16, 'deleted_at' => null]);
        Contact::create(['id' => 17, 'deleted_at' => null]);
        Contact::create(['id' => 18, 'deleted_at' => null]);
        Contact::create(['id' => 19, 'deleted_at' => null]);
        Contact::create(['id' => 20, 'deleted_at' => null]);

        UserProfile::create(['id' => 21, 'deleted_at' => null]);
        UserProfile::create(['id' => 22, 'deleted_at' => null]);
        UserProfile::create(['id' => 23, 'deleted_at' => null]);
        UserProfile::create(['id' => 24, 'deleted_at' => null]);
        UserProfile::create(['id' => 25, 'deleted_at' => null]);
        UserProfile::create(['id' => 26, 'deleted_at' => null]);
        UserProfile::create(['id' => 27, 'deleted_at' => null]);
        UserProfile::create(['id' => 28, 'deleted_at' => null]);
        UserProfile::create(['id' => 29, 'deleted_at' => null]);
        UserProfile::create(['id' => 30, 'deleted_at' => null]);
        UserProfile::create(['id' => 31, 'deleted_at' => null]);

        Order::create(['id' => 31, 'deleted_at' => null]);
        Order::create(['id' => 32, 'deleted_at' => null]);
        Order::create(['id' => 33, 'deleted_at' => null]);
        Order::create(['id' => 34, 'deleted_at' => null]);
        Order::create(['id' => 35, 'deleted_at' => null]);

        _tag('User', 1, 'Contact', 11);
        _tag('User', 1, 'Contact', 12);

        _tag('User', 2, 'Contact', 14);
        _tag('User', 2, 'Contact', 15);

        _tag('User', 4, 'Contact', 11);
        _tag('User', 5, 'Contact', 12);


        // for through testing
        _tag('User', 4, 'Contact', 11, 'Category', USER_SALES_CONTACT);
        _tag('User', 6, 'Contact', 12, 'Category', USER_SALES_CONTACT);

        _tag('User', 5, 'Contact', 12, 'Category', USER_SALES_CONTACT);
        _tag('User', 5, 'Contact', 13, 'Category', USER_SALES_CONTACT);
        _tag('User', 5, 'Contact', 14, 'Category', USER_SALES_CONTACT);
        _tag('User', 5, 'Contact', 15, 'Category', USER_PRIMARY_CONTACT);

        _tag('User', 7, 'Contact', 12, 'Category', USER_PRIMARY_CONTACT);
        _tag('User', 7, 'Contact', 13, 'Category', USER_SALES_CONTACT);

        // for inverse testing

        _tag('User', 8, 'Contact', 16);
        _tag('User', 9, 'Contact', 17);

        _tag('User', 10, 'Contact', 18);
        _tag('User', 11, 'Contact', 18);

        _tag('User', 12, 'Contact', 19);
        _tag('User', 13, 'Contact', 19);
        _tag('User', 14, 'Contact', 19);

        // for multilevel testing
        _tag('Contact', 16, 'UserProfile', 21);
        _tag('Contact', 17, 'UserProfile', 22);

        _tag('UserProfile', 21, 'Order', 31);
        _tag('UserProfile', 22, 'Order', 32);

        $orderCategoryTag = _tag('Order', 31, 'Category', ORDER_CATEGORY);
	    _tagCategory($orderCategoryTag->id, 'UserProfile', 24);
	    _tagCategory($orderCategoryTag->id, 'User', 21);
	    _tagCategory($orderCategoryTag->id, 'Contact', 11);

        // for category testing
        $categoryTag = _tag('User', 15, 'Category', BILLING_PROFILE);
        $categoryTag2 = _tag('User', 16, 'Category', FILLING_PROFILE);

        _tagCategory($categoryTag->id, 'UserProfile', 24);
        _tagCategory($categoryTag2->id, 'UserProfile', 25);


        $categoryTag3 = _tag('User', 17, 'Category', BILLING_PROFILE);
        $categoryTag4 = _tag('User', 18, 'Category', FILLING_PROFILE);

        _tagCategory($categoryTag3->id, 'UserProfile', 26);
        _tagCategory($categoryTag3->id, 'UserProfile', 27);
        _tagCategory($categoryTag4->id, 'UserProfile', 28);
        _tagCategory($categoryTag4->id, 'UserProfile', 29);

        // for category inverse many testing
        $categoryTag5 = _tag('User', 19, 'Category', BILLING_PROFILE);
        $categoryTag6 = _tag('User', 20, 'Category', BILLING_PROFILE);
        _tagCategory($categoryTag5->id, 'UserProfile', 30);
        _tagCategory($categoryTag6->id, 'UserProfile', 30);


        $categoryTag7 = _tag('User', 19, 'Category', FILLING_PROFILE);
        $categoryTag8 = _tag('User', 20, 'Category', FILLING_PROFILE);
        _tagCategory($categoryTag7->id, 'UserProfile', 31);
        _tagCategory($categoryTag8->id, 'UserProfile', 31);

        // for debugging purposes
        // dump(ModalTag::get()->toArray());

        Model::reguard();
    }
}
