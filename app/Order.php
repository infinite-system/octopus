<?php

namespace App;

use App\Traits\OrderModel;
use App\Traits\RelationAlias;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Illuminate\Database\Eloquent\SoftDeletes;
use X\Octopus\Model as Octopus;

class Order extends Octopus
{
    use RelationAlias;
    use SoftDeletes;
    use OrderModel;

    const status = [
        'draft' => 0,
        'hold' => 1,
        'in_progress_incomplete' => 2,
        'in_progress' => 3,
        'completed' => 7,
        'completed_incomplete' => 8,
        'canceled' => 9
    ];

    protected $fillable = [
        'id', 'updated_at', 'status', 'balance_due_at', 'invoice_number', 'details', 'submitted_at'
    ];

    protected $casts = ['details' => 'array'];

    protected $table = 'orders';

    static function byOptions($options = [], $type = 'order') {
        $q = self::query();

        // order type filter
        switch ($type) {
            case 'order':
                $q->byOrderType($options);
                $q->byWebsiteAndLocation($options);
                $q->bySalesStatus($options);

                if (!empty($options['sales_team']))
                    $q->bySalesTeam($options);

                if (!empty($options['contact']))
                    $q->byContact($options);

                if (!empty($options['address']))
                    $q->byAddress($options);

                if (!empty($options['profile']))
                    $q->byProfile($options);

                if (!empty($options['fulfillment_type']) || !empty($options['fulfillment_range']))
                    $q->byFulfillmentType($options);

                if (!empty($options['action_required']))
                    $q->whereNotNull("orders.details->action_required");

                $q->baseSelect();
                $q->addSelect([
                    'mt_type.target_id as _type',
                    'type.name as type_name',
                    'type.id as type_id',
                    'mt_sales_status.id as acceptedStatusId',
                    'status.name as acceptedStatus'
                ]);

                if (!empty($options['has_balance']) || !empty($options['has_credit']) || !empty($options['overdue']))
                    $q->balanceSelect();

                if (!empty($options['overdue'])) {
                    $q->having('_is_overdue', 1);
                    $q->having('balance', '>', 0);
                }

                $q->groupBy('orders.id')
                    ->orderBy('orders.updated_at', 'desc');

                if (!empty($options['status'])) $q->whereIn('orders.status', $options['status']);
                if (!empty($options['order'])) $q->whereIn('orders.id', $options['order']);

                $q->byFulfillmentDateRange($options);
                $q->byOrderDateRange($options);

                $having = '';
                if (!empty($options['has_balance'])) $having = 'balance > 0 ';
                if (!empty($options['has_credit'])) $having .= ($having !== '' ? ' OR' : '') . ' balance < 0';
                if ($having !== '') $q->havingRaw($having);

                break;
            case 'order_id':
                $q->byWebsiteAndLocation($options);
                $q->groupBy('orders.id');
                break;
            case 'sales_team':
                $q->byWebsiteAndLocation($options);
                $q->bySalesStatus($options);
                $q->bySalesTeam($options);
                $q->bySalesTeamContact($options);
                $q->groupBy('orders.id');
                break;
            case 'contact':
                $q->byWebsiteAndLocation($options);
                $q->bySalesStatus($options);
                $q->byContact($options);
                $q->byContactJoin($options);
                $q->groupBy('c.id');
                break;
            case 'profile':
                $q->byWebsiteAndLocation($options);
                $q->bySalesStatus($options);
                $q->byProfile($options);
                $q->byProfileJoin($options);
                $q->groupBy('up.id');
                break;
            case 'address':
                $q->byWebsiteAndLocation($options);
                $q->byAddress($options);
                $q->byAddressJoin($options);
                $q->groupBy('a.id');
                break;
        }
        return $q;
    }

    function scopebaseSelect($q) {
        return $q->select([
            'orders.*',
            // 371 = orders.type_cart, is not considered when calculating _is_overdue
            raw('(IF(balance_due_at IS NULL, IF(submitted_at IS NULL, NOW(), submitted_at), balance_due_at) < NOW()) as _is_overdue'),
        ]);
    }

    function scopebyOrderType($q, $options) {
        return $q->join(tagTable() . ' as mt_type', function ($j) use ($options) {
            $j->where('mt_type.source_type', '=', m(Order::class));
            $j->on('mt_type.source_id', '=', 'orders.id');
            $j->where('mt_type.target_type', '=', m(Category::class));
            $j->whereIn('mt_type.target_id', $options['type']);
            $j->where('mt_type.thru_type', 0);
            $j->where('mt_type.thru_id', 0);
            $j->where('mt_type.status', 1);
        })
            ->join('categories as type', 'mt_type.target_id', 'type.id');
    }

    function scopebyWebsiteAndLocation($q, $options) {

        return $q->whereExists(function ($q) use ($options) {
            $q->select('mt_website.id')
                ->from(tagTable() . ' as mt_website')
                ->whereRaw('mt_website.source_id = orders.id')
                ->where('mt_website.source_type', '=', m(Order::class))
                ->whereIn('mt_website.target_id', $options['website'])
                ->where('mt_website.target_type', '=', m(Website::class))
                ->where('mt_website.thru_type', '=', 0)
                ->where('mt_website.thru_id', '=', 0)
                ->where('mt_website.status', '=', 1);
        })
            ->whereExists(function ($q) use ($options) {
                $q->select('mt_location.id')
                    ->from(tagTable() . ' as mt_location')
                    ->whereRaw('mt_location.source_id = orders.id')
                    ->where('mt_location.source_type', '=', m(Order::class))
                    ->whereIn('mt_location.target_id', $options['location'])
                    ->where('mt_location.target_type', '=', m(Location::class))
                    ->where('mt_location.thru_type', '=', 0)
                    ->where('mt_location.thru_id', '=', 0)
                    ->where('mt_location.status', '=', 1);
            });
    }

    function scopebyContact($q, $options) {

        return $q->join(tagTable() . ' as mt_contact_c', function ($j) {
            $j->where('mt_contact_c.source_type', '=', m(Order::class));
            $j->on('mt_contact_c.source_id', '=', 'orders.id');
            $j->where('mt_contact_c.status', '=', 1);
            $j->where('mt_contact_c.target_type', '=', m(Category::class));
            $j->whereIn('mt_contact_c.target_id', [
                    (int) env('orders.fulfillment_profile'),
                    (int) env('orders.billing_profile')
                ]
            );
            $j->where('mt_contact_c.thru_type', '=', 0);
            $j->where('mt_contact_c.thru_id', '=', 0);
        })
            ->join(tagTable() . ' as mt_contact', function ($j) use ($options) {
                $j->on('mt_contact.source_id', '=', 'mt_contact_c.id');
                $j->where('mt_contact.source_type', '=', m(ModalTag::class));
                $j->where('mt_contact.status', '=', 1);
                $j->where('mt_contact.target_type', '=', m(Contact::class));
                if (!empty($options['contact'])) {
                    $j->whereIn('mt_contact.target_id', $options['contact']);
                }
                $j->where('mt_contact.thru_type', '=', 0);
                $j->where('mt_contact.thru_id', '=', 0);
            });
    }

    function scopebyContactJoin($q, $options) {
        return $q->join('contacts as c', 'mt_contact.target_id', 'c.id');
    }

    function scopebyAddress($q, $options) {

        return $q->join(tagTable() . ' as mt_address_c', function ($j) {
            $j->where('mt_address_c.source_type', '=', m(Order::class));
            $j->on('mt_address_c.source_id', '=', 'orders.id');
            $j->where('mt_address_c.status', '=', 1);
            $j->where('mt_address_c.target_type', '=', m(Category::class));
            $j->whereIn('mt_address_c.target_id', [
                (int) env('orders.fulfillment_profile'),
                (int) env('orders.billing_profile')
            ]);
            $j->where('mt_address_c.thru_type', '=', 0);
            $j->where('mt_address_c.thru_id', '=', 0);
        })
            ->join(tagTable() . ' as mt_address', function ($j) use ($options) {
                $j->where('mt_address.source_type', '=', m(ModalTag::class));
                $j->on('mt_address.source_id', '=', 'mt_address_c.id');
                $j->where('mt_address.status', '=', 1);
                $j->where('mt_address.target_type', '=', m(Addresses::class));
                if (!empty($options['address'])) {
                    $j->whereIn('mt_address.target_id', $options['address']);
                }
                $j->where('mt_address.thru_type', '=', 0);
                $j->where('mt_address.thru_id', '=', 0);
            });
    }

    function scopebyAddressJoin($q) {
        return $q->join('addresses as a', 'a.id', 'mt_address.target_id');
    }

    function scopebyProfile($q, $options, $profileTypes = ['fulfillment_profile', 'billing_profile']) {
        return $q->join(tagTable() . ' as mt_profile_c', function ($j) use ($profileTypes) {
            $profileTypeIds = [];
            foreach ($profileTypes as $pT) $profileTypeIds[] = (int) env('orders.' . $pT);

            $j->where('mt_profile_c.source_type', '=', m(Order::class));
            $j->on('mt_profile_c.source_id', '=', 'orders.id');
            $j->where('mt_profile_c.target_type', '=', m(Category::class));
            $j->whereIn('mt_profile_c.target_id', $profileTypeIds);
            $j->where('mt_profile_c.thru_type', '=', 0);
            $j->where('mt_profile_c.thru_id', '=', 0);
            $j->where('mt_profile_c.status', '=', 1);
        })
            ->join(tagTable() . ' as mt_profile', function ($j) use ($options) {
                $j->on('mt_profile.source_id', '=', 'mt_profile_c.id');
                $j->where('mt_profile.source_type', '=', m(ModalTag::class));
                $j->where('mt_profile.target_type', '=', m(UserProfile::class));
                $j->where('mt_profile.thru_type', '=', 0);
                $j->where('mt_profile.thru_id', '=', 0);
                $j->where('mt_profile.status', '=', 1);
                if (!empty($options['profile'])) {
                    $j->whereIn('mt_profile.target_id', $options['profile']);
                }
            });
    }

    function scopebyProfileJoin($q, $options = []) {
        return $q->join('user_profiles as up', 'mt_profile.target_id', 'up.id');
    }

    function scopebyProfileId($q, $id, $orderTypes = [], // all types if empty
                              $profileTypes = ['fulfillment_profile', 'billing_profile']) {

        $q->byOrderType(['type' => $orderTypes]);
        $q->byProfile(['profile' => [$id]], $profileTypes);
        $q->byProfileJoin();
        $q->select(['orders.*', 'up.id as user_profile_id']);

        return $q;
    }

    function scopebySalesStatus($q, $options) {

        return $q->join(tagTable() . ' as mt_sales_status', function ($j) use ($options) {
            $j->on('mt_sales_status.source_id', '=', 'orders.id');
            $j->where('mt_sales_status.source_type', '=', m(self::class));
            $j->where('mt_sales_status.target_type', '=', m(Category::class));
            $j->whereIn('mt_sales_status.target_id', $options['sales_status']);
            $j->where('mt_sales_status.thru_type', '=', 0);
            $j->where('mt_sales_status.thru_id', '=', 0);
            $j->where('mt_sales_status.status', '=', 1);
        })
            ->join('categories as status', 'mt_sales_status.target_id', 'status.id');
    }

    static function scopebySalesTeam($q, $options) {

        return $q->join(tagTable() . ' as mt_user', function ($j) use ($options) {
            $j->on('mt_user.source_id', '=', 'mt_sales_status.id');
            $j->where('mt_user.source_type', '=', m(ModalTag::class));
            $j->where('mt_user.target_type', '=', m(User::class));
            if (!empty($options['sales_team'])) {
                $j->whereIn('mt_user.target_id', $options['sales_team']);
            }
            $j->where('mt_user.thru_type', '=', 0);
            $j->where('mt_user.thru_id', '=', 0);
            $j->where('mt_user.status', '=', 1);
        });
    }

    static function scopebySalesTeamContact($q, $options) {
        return $q->join('users as u', 'mt_user.target_id', 'u.id')
            ->join(tagTable() . ' as mt_contact_category', function ($j) {
                $j->on('mt_contact_category.source_id', '=', 'mt_user.target_id');
                $j->where('mt_contact_category.source_type', '=', m(User::class));
                $j->where('mt_contact_category.target_id', '=', env('category_ids.USER_SALES_CONTACT')); // sales contact tag 581
                $j->where('mt_contact_category.target_type', '=', m(Category::class));
                $j->where('mt_contact_category.thru_type', '=', 0);
                $j->where('mt_contact_category.thru_id', '=', 0);
                $j->where('mt_contact_category.status', '=', 1);
            })
            ->join(tagTable() . ' as mt_sales_contact', function ($j) {
                $j->on('mt_contact_category.id', '=', 'mt_sales_contact.source_id');
                $j->where('mt_sales_contact.source_type', '=', m(ModalTag::class));
                $j->where('mt_sales_contact.target_type', '=', m(Contact::class));
                $j->where('mt_sales_contact.thru_type', '=', 0);
                $j->where('mt_sales_contact.thru_id', '=', 0);
                $j->where('mt_sales_contact.status', '=', 1);
            })
            ->join('contacts as sc', 'sc.id', 'mt_sales_contact.target_id');
    }

    static function scopebyFulfillmentType($q, $options) {

        return $q->join('order_items as oi', function ($j) {
            $j->on('oi.order_id', '=', 'orders.id');
            $j->where('oi.status', '=', 1);
            $j->where('oi.details->is_estimate', '=', 0);
        })->join(tagTable() . ' as mt_ff', function ($j) use ($options) {
            $j->where('mt_ff.source_type', '=', m(OrderItem::class));
            $j->on('mt_ff.source_id', '=', 'oi.id');
            $j->where('mt_ff.target_type', '=', m(Category::class));
            if (!empty($options['fulfillment_type'])) {
                $j->whereIn('mt_ff.target_id', $options['fulfillment_type']);
            }
            $j->where('mt_ff.thru_type', '=', 0);
            $j->where('mt_ff.thru_id', '=', 0);
            $j->where('mt_ff.status', '=', 1);
        });
    }

    static function scopebyFulfillment($q) {

        return $q->join('order_items', function ($j) {
            $j->on('order_items.order_id', 'orders.id')
                ->where('order_items.status', 1)
                ->whereNull('order_items.deleted_at')
                ->where('order_items.details->is_estimate', 0)
                ->where('order_items.details->type', OrderItem::TYPE_SERVICE)
                ->where('order_items.details->service_type', OrderItem::SERVICE_TYPES['fulfillment']);
        });
    }

    function scopebyFulfillmentDateRange($q, $options) {

        if (!empty($options['fulfillment_range'])) {
            $ffRange = explode(' - ', $options['fulfillment_range'][0]);
            $startDate = makeDate(date_create_from_format('m/d/Y', $ffRange[0]))->format('Y-m-d');
            $endDate = makeDate()->setTimestamp(date_create_from_format('m/d/Y', $ffRange[1])->getTimestamp() + 24 * 60 * 60)->format('Y-m-d');

            $q->where('oi.fulfill_at', '>=', $startDate);
            $q->where('oi.fulfill_at', '<=', $endDate);
        }

    }

    function scopebyOrderDateRange($q, $options) {
        if (!empty($options['date_range'])) {
            $dateRange = explode(' - ', $options['date_range'][0]);
            $startDate = makeDate(date_create_from_format('m/d/Y', $dateRange[0]))->format('Y-m-d');
            $endDate = makeDate()->setTimestamp(date_create_from_format('m/d/Y', $dateRange[1])->getTimestamp() + 24 * 60 * 60)->format('Y-m-d');
            $q->where('orders.submitted_at', '>=', $startDate);
            $q->where('orders.submitted_at', '<=', $endDate);
        }
    }

    function scopeJoinLastPaymentReminder($q) {

        $q->addSelect([
            raw('DATEDIFF(DATE(balance_due_at), DATE(submitted_at)) as _submitted_to_due_days'),
            raw('DATEDIFF(DATE(balance_due_at), DATE(NOW())) as _now_to_due_days'),
            raw('MAX(IF(reminder_task.updated_at IS NOT NULL, DATEDIFF(DATE(balance_due_at), DATE(reminder_task.updated_at)), NULL))
              as _reminded_to_due_days'),
            raw('MAX(DATE(reminder_task.updated_at)) as _reminded_at'),
            raw('MAX(reminder_task.id) as task_id'),
            raw('MAX(reminder_task.task_status) as task_status'),
            raw('MAX(reminder_task.task_details) as task_details')
        ])
            ->leftJoin(tagTable() . ' as mt_task', function ($j) {
                tagJoin($j, 'mt_task', Order::class, 'orders.id', ModalTask::class);
            })
            ->leftJoin(raw("(SELECT
              MAX(pr_task.updated_at) AS updated_at,
              MAX(pr_task.id) AS id,
              mt_task2.source_id AS order_id,
              MAX(pr_task.status) as task_status,
              MAX(modal_contents.details) as task_details
            FROM " . tagTable() . " as mt_task2
            JOIN modal_tasks as pr_task
              ON pr_task.id = mt_task2.target_id
              AND pr_task.status != '" . env('order_reminders.status_failed') . "'
            JOIN " . tagTable() . " as mt_content2
              ON mt_content2.source_type = '" . m(ModalTask::class) . "'
              AND mt_content2.source_id = pr_task.id
              AND mt_content2.target_type = '" . m(ModalContent::class) . "'
              AND mt_content2.thru_type = '0'
              AND mt_content2.thru_id = '0'
              AND mt_content2.status = '1'
            JOIN
              modal_contents ON modal_contents.id = mt_content2.target_id
              AND modal_contents.status = " . esc_sql(ModalContent::status['active']) . "
            WHERE
              mt_task2.source_type = '" . m(Order::class) . "'
              AND mt_task2.target_type = '" . m(ModalTask::class) . "'
              AND mt_task2.thru_type = '0'
              AND mt_task2.thru_id = '0'
              AND mt_task2.status = '1'
              AND JSON_EXTRACT(modal_contents.details, \"$.emailTemplate\") = 'order_reminder_balance_due'
            GROUP BY mt_task2.source_id
         ) as reminder_task"), function ($j) {
                $j->on('reminder_task.id', 'mt_task.target_id');
                $j->on('reminder_task.order_id', 'orders.id');
                $j->where('reminder_task.task_status', '!=', env('order_reminders.status_failed'));
            })
            ->leftJoin(tagTable() . ' as mt_content', function ($j) {
                tagJoin($j, 'mt_content', ModalTask::class, 'reminder_task.id', ModalContent::class);
            })
            ->leftJoin('modal_contents', function ($j) {
                $j->on('mt_content.target_id', 'modal_contents.id');
                $j->where('modal_contents.status', ModalContent::status['active']);
                $j->where('modal_contents.details->emailTemplate', 'order_reminder_balance_due');
            });

    }

    function scopeJoinLastOverdueReminder($q) {

        $q->addSelect([
            raw('DATEDIFF(DATE(NOW()), DATE(balance_due_at)) as _now_to_due_days'),
            raw('MAX(IF(reminder_task.updated_at IS NOT NULL, DATEDIFF(DATE(reminder_task.updated_at), DATE(balance_due_at)), NULL))
             as _reminded_to_due_days'),
            raw('MAX(IF(reminder_task.updated_at IS NOT NULL, DATEDIFF(DATE(NOW()), DATE(reminder_task.updated_at)), NULL))
             as _reminded_to_now_days'),
            raw('MAX(DATE(modal_tasks.updated_at)) as _reminded_at'),
            raw('MAX(reminder_task.id) as task_id'),
            raw('MAX(reminder_task.task_status) as task_status'),
            raw('MAX(reminder_task.task_details) as task_details')
        ])
            ->leftJoin(tagTable() . ' as mt_task', function ($j) {
                tagJoin($j, 'mt_task', Order::class, 'orders.id', ModalTask::class);
            })
            ->leftJoin(raw("(SELECT
            MAX(pr_task.updated_at) AS updated_at,
            MAX(pr_task.id) AS id,
            mt_task2.source_id AS order_id,
            MAX(pr_task.status) as task_status,
            MAX(modal_contents.details) as task_details
          FROM " . tagTable() . " as mt_task2
          JOIN modal_tasks as pr_task
            ON pr_task.id = mt_task2.target_id
            AND pr_task.status != '" . env('order_reminders.status_failed') . "'
          JOIN " . tagTable() . " as mt_content2
            ON mt_content2.source_type = '" . m(ModalTask::class) . "'
            AND mt_content2.source_id = pr_task.id
            AND mt_content2.target_type = '" . m(ModalContent::class) . "'
            AND mt_content2.thru_type = '0'
            AND mt_content2.thru_id = '0'
            AND mt_content2.status = '1'
          JOIN modal_contents
            ON modal_contents.id = mt_content2.target_id
            AND modal_contents.status = " . esc_sql(ModalContent::status['active']) . "
          WHERE
            mt_task2.source_type = '" . m(Order::class) . "'
            AND mt_task2.target_type = '" . m(ModalTask::class) . "'
            AND mt_task2.thru_type = '0'
            AND mt_task2.thru_id = '0'
            AND mt_task2.status = '1'
            AND JSON_EXTRACT(modal_contents.details, \"$.emailTemplate\") = 'order_reminder_balance_overdue'
          GROUP BY mt_task2.source_id
        ) as reminder_task"), function ($j) {
                $j->on('reminder_task.order_id', 'orders.id');
                $j->on('reminder_task.id', 'mt_task.target_id');
                $j->where('reminder_task.task_status', '!=', env('order_reminders.status_failed'));
            })
            ->leftJoin('modal_tasks', 'reminder_task.id', 'modal_tasks.id')
            ->leftJoin(tagTable() . ' as mt_content', function ($j) {
                tagJoin($j, 'mt_content', ModalTask::class, 'reminder_task.id', ModalContent::class);
            })
            ->leftJoin('modal_contents', function ($j) {
                $j->on('mt_content.target_id', 'modal_contents.id');
                $j->where('modal_contents.status', ModalContent::status['active']);
                $j->where('modal_contents.details->emailTemplate', 'order_reminder_balance_overdue');
            });

    }

    function scopeJoinLastFulfillmentReminder($q) {

        $q->addSelect([
            raw('DATEDIFF(DATE(fulfill_at), DATE(NOW())) as _now_to_due_days'),
            raw('MAX(IF(reminder_task.updated_at IS NOT NULL, DATEDIFF(DATE(fulfill_at), DATE(reminder_task.updated_at)), NULL))
              as _reminded_to_due_days'),
            raw('MAX(DATE(reminder_task.updated_at)) as _reminded_at'),
            raw('MAX(reminder_task.id) as reminder_task_id')
        ])
            ->leftJoin(tagTable() . ' as mt_task', function ($j) {
                tagJoin($j, 'mt_task', OrderItem::class, 'order_items.id', ModalTask::class);
            })
            ->leftJoin(raw("(
            SELECT
              MAX(pr_task.id) as id,
              MAX(pr_task.updated_at) as updated_at,
              MAX(pr_task.status) as status,
              mt_task2.source_id as order_item_id
            FROM " . tagTable() . " as mt_task2
            JOIN modal_tasks as pr_task
              ON pr_task.id = mt_task2.target_id
              AND pr_task.status != '" . env('order_reminders.status_failed') . "'
            JOIN " . tagTable() . " as mt_content2
              ON mt_content2.source_type = '" . m(ModalTask::class) . "'
              AND mt_content2.source_id = pr_task.id
              AND mt_content2.target_type = '" . m(ModalContent::class) . "'
              AND mt_content2.thru_type = '0'
              AND mt_content2.thru_id = '0'
              AND mt_content2.status = '1'
            JOIN modal_contents
              ON modal_contents.id = mt_content2.target_id
              AND modal_contents.status = " . esc_sql(ModalContent::status['active']) . "
            WHERE
              mt_task2.source_type = '" . m(OrderItem::class) . "'
              AND mt_task2.target_type = '" . m(ModalTask::class) . "'
              AND mt_task2.thru_type = '0'
              AND mt_task2.thru_id = '0'
              AND mt_task2.status = '1'
              AND JSON_EXTRACT(modal_contents.details, \"$.emailTemplate\") = 'order_reminder_fulfillment'
            GROUP BY mt_task2.source_id
          ) as reminder_task"), function ($j) {
                $j->on('reminder_task.id', 'mt_task.target_id');
                $j->on('reminder_task.order_item_id', 'order_items.id');
                $j->where('reminder_task.status', '!=', env('order_reminders.status_failed'));
            })
            ->leftJoin(tagTable() . ' as mt_content', function ($j) {
                tagJoin($j, 'mt_content', ModalTask::class, 'reminder_task.id', ModalContent::class);
            })
            ->leftJoin('modal_contents', function ($j) {
                $j->on('mt_content.target_id', 'modal_contents.id');
                $j->where('modal_contents.status', ModalContent::status['active']);
                $j->where('modal_contents.details->emailTemplate', 'order_reminder_fulfillment');
            });

    }

    function scopeJoinFulfillmentStatusChangeAlert($q, $emailTemplate = 'order_fulfillment_thank_you') {

        $q->addSelect([
            raw('DATEDIFF(DATE(fulfillment.updated_at), DATE(NOW())) as _now_to_due_days'), // needed for reminder scheduling, do not remove
            raw('DATEDIFF(DATE(order_items.fulfill_at), DATE(NOW())) as _now_to_fulfill_at'),
            raw('MAX(IF(reminder_task.updated_at IS NOT NULL, DATEDIFF(DATE(fulfillment.updated_at), DATE(reminder_task.updated_at)), NULL))
              as _reminded_to_due_days'),
            raw('MAX(DATE(reminder_task.updated_at)) as _reminded_at'),
            raw('MAX(reminder_task.id) as reminder_task_id')
        ])
            ->leftJoin(tagTable() . ' as mt_task', function ($j) {
                tagJoin($j, 'mt_task', OrderItem::class, 'order_items.id', ModalTask::class);
            })
            ->leftJoin(raw("(
            SELECT
              MAX(pr_task.id) as id,
              MAX(pr_task.updated_at) as updated_at,
              MAX(pr_task.status) as status,
              mt_task2.source_id as order_item_id
            FROM " . tagTable() . " as mt_task2
            JOIN modal_tasks as pr_task
              ON pr_task.id = mt_task2.target_id
              AND pr_task.status != '" . env('order_reminders.status_failed') . "'
            JOIN " . tagTable() . " as mt_content2
              ON mt_content2.source_type = '" . m(ModalTask::class) . "'
              AND mt_content2.source_id = pr_task.id
              AND mt_content2.target_type = '" . m(ModalContent::class) . "'
              AND mt_content2.thru_type = '0'
              AND mt_content2.thru_id = '0'
              AND mt_content2.status = '1'
            JOIN modal_contents
              ON modal_contents.id = mt_content2.target_id
              AND modal_contents.status = " . esc_sql(ModalContent::status['active']) . "
            WHERE
              mt_task2.source_type = '" . m(OrderItem::class) . "'
              AND mt_task2.target_type = '" . m(ModalTask::class) . "'
              AND mt_task2.thru_type = '0'
              AND mt_task2.thru_id = '0'
              AND mt_task2.status = '1'
              AND JSON_EXTRACT(modal_contents.details, \"$.emailTemplate\") = '$emailTemplate'
            GROUP BY mt_task2.source_id
          ) as reminder_task"), function ($j) {
                $j->on('reminder_task.id', 'mt_task.target_id');
                $j->on('reminder_task.order_item_id', 'order_items.id');
                $j->where('reminder_task.status', '!=', env('order_reminders.status_failed'));
            })
            ->leftJoin(tagTable() . ' as mt_content', function ($j) {
                tagJoin($j, 'mt_content', ModalTask::class, 'reminder_task.id', ModalContent::class);
            })
            ->leftJoin('modal_contents', function ($j) use ($emailTemplate) {
                $j->on('mt_content.target_id', 'modal_contents.id');
                $j->where('modal_contents.status', ModalContent::status['active']);
                $j->where('modal_contents.details->emailTemplate', $emailTemplate);
            });

    }

    function nowToBalanceDueDays() {

        $utc = new CarbonTimeZone('UTC');
        $now = Carbon::create('now', $utc)->startOfDay();

        $balanceDueAt = Carbon::create($this->balance_due_at, $utc)->startOfDay();

        return $now->diffInDays($balanceDueAt);
    }

    function salesStatusCategory() {

        return $this->tagOneThrough(Category::class, [
            env('orders.sales_team_not_assigned'),
            env('orders.sales_team_assigned'),
            env('orders.sales_team_completed')
        ]);
    }

    function payment_source() {
        return $this->tagOneInverse(PaymentSource::class,
            status: [ModalTag::status['active'], ModalTag::status['hidden']])
            ->orderByDesc(tagTable() . '.id');
    }

    function items() {
        return $this->hasMany(OrderItem::class)
            ->where('order_items.details->type', OrderItem::TYPE_PRODUCT)
            ->where('status', OrderItem::status['active'])
            ->with('services');
    }

    function scheduledEmails() {
        return $this->tagMany(ModalTask::class);
    }

    function orderSpaces() {
        return $this->tagMany(OrderSpace::class);
    }

    function billing_profile() {
        return $this->tagOneThrough(Category::class, (int) env('orders.billing_profile'), UserProfile::class);
    }

    function billing_profile_category() {
        return $this->tagOneThrough(Category::class, (int) env('orders.billing_profile'));
    }

    function billing_profile_category_all() {
        return $this->tagManyThrough(Category::class, (int) env('orders.billing_profile'), 'any');
    }

    function billing_address() {
        return $this->tagOneThrough(Category::class, (int) env('orders.billing_profile'), Addresses::class);
    }

    function billing_contact() {
        return $this->tagOneThrough(Category::class, (int) env('orders.billing_profile'), Contact::class)
            ->where('contacts.status', 1);
    }

    function fulfillment_profile() {
        return $this->tagOneThrough(Category::class, (int) env('orders.fulfillment_profile'), UserProfile::class);
    }

    function fulfillment_profile_category() {
        return $this->tagOneThrough(Category::class, (int) env('orders.fulfillment_profile'));
    }

    function fulfillment_profile_category_all() {
        return $this->tagManyThrough(Category::class, (int) env('orders.fulfillment_profile'), 'any');
    }

    function fulfillment_address() {
        return $this->tagOneThrough(Category::class, (int) env('orders.fulfillment_profile'), Addresses::class);
    }

    function fulfillment_contact() {
        return $this->tagOneThrough(Category::class, (int) env('orders.fulfillment_profile'), Contact::class)
            ->where('contacts.status', 1);
    }

    function shipping_profile() {
        return $this->tagOneThrough(Category::class, (int) env('orders.fulfillment_profile'), UserProfile::class);
    }

    function creditOrderItems() {
        return $this->hasMany(OrderItem::class, 'order_id', 'id')
            ->where('order_items.details->type', OrderItem::TYPE_CREDIT)
            ->where('status', OrderItem::status['active']);
    }

    function fulfillmentOrderItems() {
        return $this->hasMany(OrderItem::class, 'order_id', 'id')
            ->where('order_items.details->type', '=', OrderItem::TYPE_SERVICE)
            ->where('order_items.details->service_type', OrderItem::SERVICE_TYPES['fulfillment'])
            ->where('status', OrderItem::status['active']);
    }

    function productOrderItems() {
        return $this->hasMany(OrderItem::class)
            ->where('order_items.status', OrderItem::status['active'])
            ->where('order_items.details->type', \App\OrderItem::TYPE_PRODUCT)
            ->select([
                'order_items.*',
                'order_items.details->product_id AS product_id',
                'order_items.details->product->colors AS colors',
            ]);
    }

    function assemblyOrderItems() {
        return $this->hasMany(OrderItem::class)
            ->where('order_items.status', OrderItem::status['active'])
            ->where('order_items.details->type', OrderItem::TYPE_SERVICE)
            ->where('order_items.details->service_type', OrderItem::SERVICE_TYPES['assembly'])
            ->select([
                'order_items.*',
                'order_items.details->product_id AS product_id',
                'order_items.details->transaction_details->id AS provider_id',
            ]);
    }

    function type() {
        return $this->tagOne(Category::class)
            ->whereIn(tagTable() . '.target_id', [
                (int) env('orders.type_cart'),
                (int) env('orders.type_order'),
                (int) env('orders.type_archive')
            ])
            ->where(tagTable() . '.thru_type', '=', 0)
            ->where(tagTable() . '.thru_id', '=', 0)
            ->where('categories.status', 1);
    }

    function typeTag() {
        return $this->hasOne(ModalTag::class, 'source_id', 'id')
            ->where('source_type', '=', m(Order::class))
            ->where('target_type', '=', m(Category::class))
            ->where('thru_type', '=', 0)
            ->where('thru_id', '=', 0)
            ->where('status', ModalTag::status['active']);
    }

    function fulfillment_details() {
        return $this->hasMany(OrderItem::class, 'order_id', 'id')->where([
            'order_items.details->type' => OrderItem::TYPE_SERVICE,
            'order_items.details->service_type' => OrderItem::SERVICE_TYPES['fulfillment'],
            'order_items.status' => OrderItem::status['active'],
        ])->orderBy('fulfill_at', 'asc');
    }

    function all_items() {
        return $this->hasMany(OrderItem::class, 'order_id', 'id')->where([
            'order_items.status' => OrderItem::status['active']
        ]);
    }

    function location() {
        return $this->tagOne(Location::class);
    }

    function website() {
        return $this->tagOne(Website::class);
    }

    function user() {
        return $this->tagOne(User::class);
    }

    function language() {
        return $this->tagOne(Lang::class);
    }

    function assignedSalesUsers() {
        return $this->tagManyThrough(\App\Category::class, env('orders.sales_team_assigned'), User::class);
    }

    function paymentTerm() {
        return $this->tagOne(PaymentTerm::class);
    }

    function lastTalkTask() {
        return $this->tagOne(ModalTask::class)
            ->join(tagTable() . ' as mt_content', function ($j) {
                tagJoin($j, 'mt_content', ModalTask::class, 'modal_tasks.id', ModalContent::class);
            })
            ->join('modal_contents', 'mt_content.target_id', 'modal_contents.id')
            ->where('modal_contents.status', ModalContent::status['active'])
            ->where('modal_tasks.type_id', env('talk.talk_orders'))
            ->whereIn('modal_tasks.status', [
                env('order_reminders.status_pending'),
                env('order_reminders.status_completed')
            ])
            ->orderBy('modal_tasks.scheduled_at', 'desc');
    }

    function tasks() {
        return $this->tagMany(ModalTask::class);
    }

    static function lastTalkOrderSummary() {
        return function ($q) {
            $q->where('modal_contents.details->emailTemplate', env('orders.talk_email_order_confirmation'));
            $q->addSelect('modal_contents.details');
        };
    }

    function getIsCanceledAttribute() {
        return $this->status == self::status['canceled'];
    }

    function getCanDoTransactionAttribute() {
        return in_array($this->status, [
            Order::status['in_progress_incomplete'],
            Order::status['in_progress'],
            Order::status['completed'],
            Order::status['completed_incomplete'],
            Order::status['canceled']
        ]);
    }

    function files() {
        return $this->hasManyThrough(
            Media::class,        // The model to access to
            \App\Mediable::class,// The intermediate table that connects the Media with TalkContent.
            'mediable_id',       // The column of the intermediate table that connects to the model (Media) by its ID.
            'id',                // The column of the intermediate table that connects the <table> by its ID.
            'id',                // The column that connects this model (Media) with the intermediate model table.
            'media_id'           // The column of the <table> that ties it to the TalkContent.
        )
            ->where('mediables.mediable_type', 'App\Order')
            ->where('mediables.status', 1)
            ->select(['media.*', 'mediables.tag']);
    }
}
