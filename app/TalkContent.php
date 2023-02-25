<?php

namespace App;

use DB;
use Plank\Mediable\Mediable;
use X\Octopus\Model as Octopus;

class TalkContent extends Octopus
{
  use Mediable;

  public $timestamps = false;

  protected $table = 'talk_content';

  protected $casts = ['details' => 'array'];

  protected $fillable = [
    'id',
    'talk_id',
    'message_id',
    'unique_id',
    'group_id',
    'reply_to_content_id',
    'forward_id',
    'account_id',
    'api_id',
    'inc',
    'direction',
    'draft_id',
    'content',
    'has_attachment',
    'details',
    'status',
    'completed',
    'born_at',
    'created_at'
  ];

  function setUpdatedAt($value){}

  static function byAccounts($accountIds)
  {
    return self::whereIn('account_id', $accountIds)
               ->join('accounts as a', 'a.id', 'talk_content.account_id')
               ->orderBy('born_at', 'desc')
               ->select([
                  'talk_content.content',
                  'talk_content.account_id',
                  'talk_content.details',
                  'talk_content.id',
                  'talk_content.talk_id',
                  'talk_content.born_at',
                  'talk_content.created_at',
                  'talk_content.status'
                ]);
  }

  function scopebyOptions($q, $options)
  {
    $validSalesTeam = !empty($options['sales_team']);

    $validContacts = !empty($options['contact']);

    $hasAttachment = !empty($options['has_attachment']);

    $hasDraft = !empty($options['draft']);

    $q->byWebsiteAndLocation($options)
      ->whereIn('talk_content.talk_id', $options['type'])

      ->join(tagTable().' as mt_dep_category', function($j) use ($options) {
        $j->on('mt_dep_category.source_id', 'talk_content.id')
          ->where('mt_dep_category.source_type', m(TalkContent::class))
          ->where('mt_dep_category.target_id', (int) env('talk.tag_department_category'))
          ->where('mt_dep_category.target_type',  m(Category::class))
          ->where('mt_dep_category.thru_type', 0)
          ->where('mt_dep_category.thru_id', 0)
          ->where('mt_dep_category.status', 1);
      })

      ->join(tagTable().' as mt_category', function($j) use ($options) {
        $j->on('mt_category.source_id', 'mt_dep_category.id')
          ->where('mt_category.source_type', m(ModalTag::class))
          ->whereIn('mt_category.target_id', $options['department'])
          ->where('mt_category.target_type', m(Category::class))
          ->where('mt_category.thru_type', 0)
          ->where('mt_category.thru_id', 0)
          ->where('mt_category.status', 1);
      })
    ;

    $completedMap = [
      env('talk.status_not_assigned') => 0,
      env('talk.status_accepted') => 1,
      env('talk.status_completed') => 2
    ];

    foreach($options['status'] as $s){
      $completed[] = $completedMap[$s];
    }
    $q->whereIn('talk_content.completed', $completed);

    if (!empty($options['sales_team'])){

      $q->join(tagTable().' as mt_status', function($j) use ($options) {

        $j->on('mt_status.source_id', 'talk_content.id')
          ->where('mt_status.source_type', m(TalkContent::class))
          ->where('mt_status.target_type', m(Category::class))
          ->whereIn('mt_status.target_id', $options['status'])
          ->where('mt_status.thru_type', 0)
          ->where('mt_status.thru_id', 0)
          ->where('mt_status.status', 1);
      });

      $q->join(tagTable().' as mt_user', function($j) use ($options, $validSalesTeam) {

        $j->on('mt_user.source_id', 'mt_status.id')
          ->where('mt_user.source_type', m(ModalTag::class))
          ->where('mt_user.target_type', m(User::class))
          ->where('mt_user.thru_type', 0)
          ->where('mt_user.thru_id', 0)
          ->where('mt_user.status', 1);

        if ($validSalesTeam){
          $j->whereIn('mt_user.target_id', $options['sales_team']);
        }
      })
      ->join('users as u', 'mt_user.target_id', 'u.id');

    }

    $q->select([
        'talk_content.content',
        'talk_content.account_id',
        'talk_content.details',
        'talk_content.direction',
        'talk_content.id',
        'talk_content.talk_id',
        'talk_content.born_at',
        'talk_content.api_id',
        'talk_content.group_id',
        'talk_content.draft_id',
        'talk_content.created_at',
        'talk_content.reply_to_content_id',
        'talk_content.has_attachment',
        'talk_content.status'
    ]);

    if ($validContacts){
      $q->byContact($options);
    }

    if (isset($options['search']) && $options['search'] != ''){
      $q->where('talk_content.content', 'like', '%'.$options['search'].'%');
    }

    if ($hasAttachment) $q->where('has_attachment', 1);

    if ($hasDraft){
      $q->leftJoin('talk_content as talk_drafts', 'talk_drafts.reply_to_content_id', 'talk_content.id');
      $q->where(function($q){
        $q->where('talk_drafts.draft_id', '!=', 0);
        $q->orWhere(function($q){
          $q->whereNotNull('talk_content.draft_id');
          $q->where('talk_content.draft_id', '!=', 0);
        });
      });
      // we need both directions to show drafts
      $options['direction'] = [
        (int) env('talk.direction_inbound'),
        (int) env('talk.direction_outbound')
      ];
    }

    $q->whereIn('talk_content.direction', $options['direction']);

    if (!empty($options['order'])){
      $q->byOrderId($options['order']);
    }

    $q->groupBy('talk_content.id');

    //dd(OrderSystemController::__getSql($q));

    return $q;
  }

  function scopebyContact($q, $options){

    return $q->join(tagTable().' as mt', function($j) use ($options) {
      $j->where('mt.source_type', m(TalkContent::class));
      $j->on('mt.source_id', 'talk_content.id');
      $j->where('mt.target_type', m(Category::class));
      $j->whereIn('mt.target_id', [(int) env('talk.send_from'), (int) env('talk.send_to')]);
      $j->where('mt.thru_type', 0);
      $j->where('mt.thru_id', 0);
      $j->where('mt.status', 1);
    })->join(tagTable().' as mt_contacts', function($j) use ($options) {
      $j->where('mt_contacts.source_type', m(ModalTag::class));
      $j->on('mt_contacts.source_id', 'mt.id');
      $j->where('mt_contacts.target_type', m(Contact::class));
      $j->whereIn('mt_contacts.target_id', $options['contact']);
      $j->where('mt_contacts.thru_type', 0);
      $j->where('mt_contacts.thru_id', 0);
      $j->where('mt_contacts.status', 1);
    })->join('contacts as cc', 'cc.id', 'mt_contacts.target_id');
  }

  function scopebyWebsiteAndLocation($q, $options){
    return $q->join(tagTable().' as mt_website', function($j) use ($options) {
              $j->on('mt_website.source_id', 'talk_content.id')
                ->where('mt_website.source_type', m(TalkContent::class))
                ->whereIn('mt_website.target_id', $options['website'])
                ->where('mt_website.target_type', m(Website::class))
                ->where('mt_website.thru_type', 0)
                ->where('mt_website.thru_id', 0)
                ->where('mt_website.status', 1);
           })
           ->join(tagTable().' as mt_location', function($j) use ($options) {
             $j->on('mt_location.source_id', 'talk_content.id')
               ->where('mt_location.source_type', m(TalkContent::class))
               ->whereIn('mt_location.target_id', $options['location'])
               ->where('mt_location.target_type', m(Location::class))
               ->where('mt_location.thru_type', 0)
               ->where('mt_location.thru_id', 0)
               ->where('mt_location.status', 1);
           });
  }

  function scopebyOrderId($q, $orderId){

    return $q->join(tagTable().' as mt_tags_category', function($j) use ($orderId) {
      $j->on('mt_tags_category.source_id', 'talk_content.id');
      $j->where('mt_tags_category.source_type', m(TalkContent::class));
      $j->where('mt_tags_category.target_type', m(Category::class));
      $j->where('mt_tags_category.target_id', env('talk.tags_category'));
      $j->where('mt_tags_category.thru_type', 0);
      $j->where('mt_tags_category.thru_id', 0);
      $j->where('mt_tags_category.status', 1);
    })
    ->join(tagTable().' as mt_order', function($j) use ($orderId){
      $j->on('mt_order.source_id', 'mt_tags_category.id');
      $j->where('mt_order.source_type', m(ModalTag::class));
      $j->where('mt_order.target_type', m(Order::class));
      $j->whereIn('mt_order.target_id', !is_array($orderId) ? [$orderId] : $orderId);
      $j->where('mt_order.thru_type', 0);
      $j->where('mt_order.thru_id', 0);
      $j->where('mt_order.status', 1);
    })
    ->join(tagTable().' as mt_order_main', function($j) use ($orderId){
      $j->on('mt_order_main.source_id', 'mt_order.id');
      $j->where('mt_order_main.source_type', m(ModalTag::class));
      $j->where('mt_order_main.target_type', m(Category::class));
      $j->where('mt_order_main.target_id', env('talk.main_order_category'));
      $j->where('mt_order_main.thru_type', 0);
      $j->where('mt_order_main.thru_id', 0);
      $j->where('mt_order_main.status', 1);
    });
  }

  function scopebyGroupId($q, $groupId)
  {
    if ($groupId instanceof \Illuminate\Database\Eloquent\Collection
      || $groupId instanceof \Illuminate\Support\Collection){
      $groupId = $groupId->toArray();
    } else {
      $groupId = is_array($groupId) ? $groupId : [$groupId];
    }

    return $q->whereIn('talk_content.group_id', $groupId)
             ->where('talk_content.group_id', '!=', 0)
             ->where('talk_content.draft_id', '=', 0)
             ->groupBy('talk_content.group_id')
             ->select(raw('count(*) as count'), 'talk_content.group_id');
  }

  function from() {
    return thruCategory($this, 'Many', \App\Category::class,env('talk.send_from'), Contact::class)
            ->orderBy('mt_contacts.id', 'asc');
//            ->orderByRaw(self::contactsOrderRank());
  }

  function account(){
    return $this->hasOne(Account::class, 'id', 'account_id');
  }

  function to() {
    return thruCategory($this, 'Many', \App\Category::class,env('talk.send_to'), Contact::class)
            ->orderByRaw(self::contactsOrderRank());
  }

  function draft(){
    return $this->hasOne(TalkContent::class, 'id', 'draft_id')->orderBy('id', 'desc');
  }

  function replyDraft(){
    return $this->hasOne(TalkContent::class, 'reply_to_content_id', 'id')
                ->where('talk_content.draft_id', '!=', 0);
  }

  function replyToContent(){
    return $this->belongsTo(TalkContent::class, 'reply_to_content_id', 'id')
                ->where('talk_content.draft_id', 0);
  }

  function replies(){
    return $this->hasMany(TalkContent::class, 'reply_to_content_id', 'id')
                ->where('talk_content.draft_id', 0);
  }

  function acceptStatus() {
    return $this->tagOne(Category::class)
            ->whereIn('categories.id', [
              env('talk.status_accepted'),
              env('talk.status_completed'),
              env('talk.status_not_assigned')
            ]);
  }

  function assignedUser() {

    return $this->tagOneThrough(Category::class,[
      env('talk.status_accepted'),
      env('talk.status_completed'),
      env('talk.status_not_assigned')
    ], User::class);

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
      ->where('mediables.mediable_type', 'App\TalkContent')
      ->where('mediables.status', 1)
      ->select(['media.*', 'mediables.tag']);
  }

  function salesContact(){
    return $this->tagOneThrough(Category::class,env('category_ids.USER_SALES_CONTACT'), Contact::class);
  }

  function location() {
    return $this->tagMany(Location::class);
  }

  function website() {
    return $this->tagMany(Website::class);
  }

  function language() {
    return $this->tagOne(Lang::class);
  }

   function departments() {
    return thruCategory($this, 'Many', \App\Category::class,env('talk.tag_department_category'), Category::class);
  }

  function order() {
    return $this->tagOneThrough(Category::class,env('talk.tags_category'), Order::class)
            ->join(tagTable().' as mt_main_order', function($j) {
              $j->on('mt_main_order.source_id', 'mt_orders.id');
              $j->where('mt_main_order.source_type', m(ModalTag::class));
              $j->where('mt_main_order.target_type', m(Category::class));
              $j->where('mt_main_order.target_id', env('talk.main_order_category'));
	            $j->where('mt_main_order.thru_type', 0);
	            $j->where('mt_main_order.thru_id', 0);
	            $j->where('mt_main_order.status', 1);
            });
  }

  static function contactsOrderRank()
  {
    return "CASE
              WHEN contacts.phone != '' AND contacts.email != '' AND contacts.name != '' THEN 0
              WHEN contacts.phone != '' AND contacts.email != '' THEN 1
              WHEN contacts.name != ''  AND contacts.phone != '' THEN 2
              WHEN contacts.name != ''  AND contacts.email != '' THEN 2
            ELSE 3 END ASC";
  }

  function tags(){
    return thruCategory($this, 'Many', \App\Category::class,env('talk.tags_category'), 'any');
  }

  function tagsCategory(){
    return $this->tagOneThrough(Category::class,env('talk.tags_category'));
  }

  static function allTags($groupId)
  {
    return TalkContent::where('talk_content.group_id', $groupId)
            ->join(tagTable().' as mt_category', function($j){
              tagJoin($j, 'mt_category', TalkContent::class, 'talk_content.id', Category::class, env('talk.tags_category'));
            })
            ->rightJoin(tagTable().' as tags', function($j){
              tagJoin($j, 'tags', ModalTag::class, 'mt_category.id', null);
            })
            ->groupBy('tags.target_id')
            ->select(['tags.target_type', 'tags.target_id', 'tags.updated_at'])
            ->orderBy('tags.id', 'desc');
  }

  function failedFiles(){
    return $this->hasMany(TalkFailedFiles::class, 'talk_content_id', 'id');
  }

  function failedSend(){
    return $this->hasMany(TalkFailedSend::class, 'talk_content_id', 'id');
  }
}
