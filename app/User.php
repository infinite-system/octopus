<?php

namespace App;

use App\Feed;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use X\Octopus\Concerns\TagRelations;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, TagRelations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    protected $table = 'users';

    const status = ['active' => '1', 'in_active' => '0'];

    protected $fillable = [
        'id', 'created_at', 'updated_at', 'deleted_at',
        'email', 'password', 'status', 'password', 'permission_id',
        'remember_token',
    ];

    protected $casts = ['permissions' => 'array', 'details' => 'array'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    public function paymentSources() {
        return $this->tagMany(PaymentSource::class,
            status: [ModalTag::status['active'], ModalTag::status['hidden']]);
    }

    public function transactions() {

        return $this->hasManyThrough(

            PaymentSource::class,   // The model to access to // from PaymentSource
            ModalTag::class,     // The intermediate table that connects the Order with the PaymentSource. // from PaymentSource join with ModalTag

            'target_id',        // The column of the intermediate table that connects to this model (PaymentSource) by its ID. //select ModalTag source_id, where source_id = PaymentSource id

            'id',             // The column of the intermediate table that connects the PaymentSource by its ID. //join on PaymentSource - id & ModalTag target_id

            'id',               // The column that connects this model (PaymentSource) with the intermediate model table. //PaymentSource table id //joining with this modal using column

            'source_id'   // The column of the <table> that ties it to the PaymentSource. //join ModalTag - target_id on PaymentSource id //relation condition
        )
            ->where(tagTable() . '.source_type', '=', m(PaymentSource::class))
            ->where(tagTable() . '.target_type', '=', m(User::class))
            ->where(tagTable() . '.thru_type', '=', 0)
            ->where(tagTable() . '.thru_id', '=', 0)
            ->where(tagTable() . '.status', 1)
            ->whereNull('payment_sources.deleted_at')
//				->where('pt.amount', '>', 0)
            ->rightJoin('payment_transactions as pt', function ($join) {
                $join->on('payment_sources.id', '=', 'pt.source_id');
            })
            ->join('currencies as c', 'c.id', '=', 'pt.currency_id')
            ->orderBy('pt.created_at', 'asc')
            ->select([
                'pt.*',
                'c.code as currency_code',
                'c.id as currency_id',
                'payment_sources.id as payment_source_id',
                'payment_sources.details as source_details'
            ]);

    }

    public function scopewithPrimaryContact($query) {
        $return = $query->join(tagTable() . ' as mt_users', function ($join) {
            $join->where('mt_users.source_type', '=', m(User::class));
            $join->on('mt_users.source_id', '=', 'users.id');
            $join->where('mt_users.target_type', '=', m(Contact::class));
            $join->where('mt_users.thru_type', '=', 0);
            $join->where('mt_users.thru_id', '=', 0);
        })->join('contacts as c', function ($join) {
            $join->where('mt_users.source_type', '=', m(User::class));
            $join->on('mt_users.source_id', '=', 'users.id');
            $join->on('mt_users.target_id', '=', 'c.id');
            $join->where('mt_users.target_type', '=', m(Contact::class));
            $join->where('mt_users.thru_type', '=', 0);
            $join->where('mt_users.thru_id', '=', 0);
        })->join(tagTable() . ' as mt_primary', function ($join) {
            $join->where('mt_primary.source_type', '=', m(Contact::class));
            $join->where('mt_primary.target_type', '=', m(Category::class));
            $join->where('mt_primary.target_id', '=', (int) env('category_ids.PRIMARY_PROFILE_CATEGORY_ID'));
            $join->where('mt_primary.thru_type', '=', 0);
            $join->where('mt_primary.thru_id', '=', 0);
        })->join('contacts as primary_c', function ($join) {
            $join->on('mt_primary.source_id', '=', 'primary_c.id');
            $join->on('c.id', '=', 'primary_c.id');
        })->select(['users.*',
            'primary_c.name as primary_name',
            'primary_c.email as primary_email',
            'primary_c.phone as primary_phone'
        ]);

        return $return;
    }

    public function primaryContact() {
        return $this->tagOneInverse(Contact::class)
            ->join(tagTable() . ' as mt_primary', function ($join) {
                $join->where('mt_primary.source_type', '=', m(Contact::class));
                $join->on('mt_primary.source_id', '=', 'contacts.id');
                $join->where('mt_primary.target_type', '=', m(Category::class));
                $join->where('mt_primary.target_id', '=', (int) env('category_ids.PRIMARY_PROFILE_CATEGORY_ID'));
                $join->where('mt_primary.thru_type', '=', 0);
                $join->where('mt_primary.thru_id', '=', 0);
            });
    }

    public function sales_contact() {
        return $this->tagOne(Contact::class)
            ->join(tagTable() . ' as mt_sales_contact', function ($join) {
                $join->where('mt_primary.source_type', '=', m(Contact::class));
                $join->where('mt_primary.target_type', '=', m(Category::class));
                $join->where('mt_primary.target_id', '=', (int) env('category_ids.PRIMARY_PROFILE_CATEGORY_ID'));
                $join->where('mt_primary.thru_type', '=', 0);
                $join->where('mt_primary.thru_id', '=', 0);
            });
    }

    public function paymentTerms() {
        return $this->hasMany(PaymentTerm::class);
    }

    public function terms() {
        return $this->tagMany(Category::class)
            ->where('categories.parent_id', env('orders.payment_terms_category'));
    }

    static function getDepartmentIds($startingFrom = 499) {
        $category = \App\Category::where('id', $startingFrom)->first();
        static $children; // cache
        if (empty($children)) $children = self::getChildrenIds($category);
        return $children;
    }

    static function getChildrenIds($category) {
        $children = [];
        if ($category !== null && $category->children) {
            foreach ($category->children as $c) {
                array_push($children, $c->id);
                if ($c->children->isNotEmpty()) array_push_array($children, self::getChildrenIds($c));
            }
        }
        return $children;
    }

    protected static $TALK_DEPARTMENTS = 614;
    protected static $ORDER_DEPARTMENTS = 621;

    public static function byDepartment($type) {
        $variable = strtoupper($type) . '_DEPARTMENTS';
        $id = isset(self::$$variable) ? self::$$variable : 499;
        $departmentIds = self::getDepartmentIds($id);
        return function ($q) use ($departmentIds) {
            $q->whereIn(tagTable() . '.target_id', $departmentIds);
        };
    }

    public function departments() {
        return $this->tagMany(Category::class)
            ->leftJoin(tagTable() . ' as mt_contents', function ($j) {
                $j->on('mt_contents.source_id', tagTable() . '.id');
                $j->where('mt_contents.source_type', '=', m(ModalTag::class));
                $j->where('mt_contents.target_type', '=', m(ModalContent::class));
                $j->where('mt_contents.thru_type', '=', 0);
                $j->where('mt_contents.thru_id', '=', 0);
            })
            ->leftJoin('modal_contents as mc', function ($j) {
                $j->on('mt_contents.target_id', 'mc.id');
                $j->where('mc.type_id', '=', 658);
                $j->where('mc.status', ModalContent::status['active']);
                // Category 658: User->Departments->Levels, to get proper modal_content
            })
            ->addSelect(['mc.details as contents_details']);
    }

    public function location() {
        return $this->tagMany(Location::class);
    }

    public function website() {
        return $this->tagMany(Website::class);
    }

    public static function getTalkAccounts($userId = null) {

        $user = $userId === null ? Auth::user() : self::where('id', $userId)->first();

        if ($user === null) return [];

        $locations = $user->location->pluck('id')->toArray();
        $websites = $user->website->pluck('id')->toArray();

        $accounts = Account::byLocationAndWebsite($locations, $websites, 'api_talk')->get();
        $accountIds = $accounts->pluck('id')->toArray();

        return $accountIds;
    }

    public function contacts() {
        return $this->tagMany(Contact::class);
    }


    public function salesContact() {
        return $this->tagOneThrough(Category::class, (int) env('category_ids.USER_SALES_CONTACT'), Contact::class)
            ->orderBy('mt_contacts.updated_at', 'desc');
    }

    function talkTypes() {
        return $this->tagMany(Category::class)
            ->whereIn(tagTable() . '.target_id', [
                env('talk.type_email'), env('talk.type_call'), env('talk.type_sms')
            ]);
    }

    public static function salesTeam() {
        return User::join(tagTable() . ' as mt_sales', function ($j) {
            $j->where('mt_sales.source_type', '=', m(self::class));
            $j->on('mt_sales.source_id', '=', 'users.id');
            $j->where('mt_sales.target_type', '=', m(Category::class));
            $j->where('mt_sales.target_id', '=', (int) env('category_ids.USER_SALES_CONTACT'));
            $j->where('mt_sales.thru_type', '=', 0);
            $j->where('mt_sales.thru_id', '=', 0);
            // Category => Tags->user->sales->contact = 581
        })->join(tagTable() . ' as mt_contacts', function ($j) {
            $j->where('mt_contacts.source_type', '=', m(ModalTag::class));
            $j->on('mt_contacts.source_id', '=', 'mt_sales.id');
            $j->where('mt_contacts.target_type', '=', m(Contact::class));
            $j->where('mt_contacts.thru_type', '=', 0);
            $j->where('mt_contacts.thru_id', '=', 0);
        })->join('contacts as c', 'c.id', 'mt_contacts.target_id');
    }


    public function payment_sources() {
        return $this->tagManyInverse(PaymentSource::class,
            status: [ModalTag::status['active'], ModalTag::status['hidden']]);
    }

    public function lastOrder() {
        return $this->tagOneInverse(Order::class)
            ->whereHas('typeTag', function ($q) {
                $q->where('target_id', (int) env('category_ids.ORDER_CATEGORY_ID'));
            })
            ->orderByDesc('orders.id');
    }


    public function orderSpaces() {
        return $this->tagManyInverse(OrderSpace::class);
    }

    public function allUserProfiles() {
        return $this->tagManyThroughInverse(Category::class, [
            (int) env('category_ids.user_profile_permission_admin'),
            (int) env('category_ids.user_profile_permission_owner'),
            (int) env('category_ids.user_profile_permission_share_edit'),
            (int) env('category_ids.user_profile_permission_share_view'),
        ], UserProfile::class, status: [
            ModalTag::status['active'],
            ModalTag::status['hidden']
        ]);
    }

    public function userProfiles() {
        $categories = [
            (int) env('category_ids.user_profile_permission_admin'),
            (int) env('category_ids.user_profile_permission_owner'),
        ];
        return $this->allUserProfiles()
            ->whereIn('mt_user_profiles.target_id', $categories)
            ->where('mt_user_profiles.thru_type', 0)
            ->where('mt_user_profiles.thru_id', 0)
            ->whereIn(tagTable() . '.status', [ModalTag::status['active']])
            ->orderBy('user_profiles.updated_at', 'desc');
    }

    public function orders() {
        $modalArr = app('modalArr');

        $categoryArr = [
            (int) env('orders.billing_profile'),
            (int) env('orders.fulfillment_profile'),
            (int) env('orders.user_access_admin'),
            (int) env('orders.user_access_edit'),
            (int) env('orders.user_access_owner'),
            (int) env('orders.user_access_view'),
        ];

        $subQuery = $this->allUserProfiles()->select('mt_user_profiles.source_id');

        return Order::join(tagTable() . ' as mt_orders', 'orders.id', 'mt_orders.source_id')
            ->where([
                'mt_orders.source_type' => $modalArr[Order::class],
                'mt_orders.target_type' => $modalArr[Category::class],
                'mt_orders.thru_type' => 0,
                'mt_orders.thru_id' => 0,
                'mt_orders.status' => ModalTag::status['active'],
            ])
            ->whereIn('mt_orders.target_id', $categoryArr)
            ->join(tagTable() . ' as mt_user_profiles', 'mt_user_profiles.source_id', 'mt_orders.id')
            ->where([
                'mt_user_profiles.source_type' => $modalArr[ModalTag::class],
                'mt_user_profiles.target_type' => $modalArr[UserProfile::class],
                'mt_user_profiles.thru_type' => 0,
                'mt_user_profiles.thru_id' => 0,
                'mt_user_profiles.status' => ModalTag::status['active'],
            ])
            ->whereRaw("mt_user_profiles.target_id in ({$subQuery->toSql() })", $subQuery->getBindings())
            ->whereHas('typeTag', function ($q) {
                $q->where('target_id', '=', (int) env('orders.type_order'));
            })
            ->orderByDesc('orders.id')
            ->groupBy('orders.id')
            ->select('orders.*');
    }


    function feeds() {
        return $this->hasMany(Feed::class, 'owner_id', 'id');
    }

}
