<?php

namespace X\Octopus;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Relations\HasOne;
use \Illuminate\Database\Eloquent\Relations\HasMany;
use \Illuminate\Database\Eloquent\Builder;

class TagThrough
{
    use Concerns\TagSetupModels;

    /**
     * Inverse relationship.
     *
     * @var bool
     */
    protected bool $inverse;

    /**
     * One or many relationships.
     *
     * @var string|'one'|'many'
     */
    protected string $oneOrMany;

    /**
     * Source model that gets the relationship.
     *
     * @var Model
     */
    protected Model $sourceModel;

    /**
     * Through category class name.
     *
     * @var string
     */
    protected string $category;

    /**
     * The category id through which we are connecting.
     *
     * @var array|mixed
     */
    protected mixed $categoryId;

    /**
     * Table alias.
     *
     * @var string|null
     */
    protected mixed $tableAlias;

    /**
     * Self parent class name.
     *
     * @var string
     */
    protected string $self;

    /**
     * Target class name.
     *
     * @var string|null
     */
    protected string|null $target;

    /**
     * Through class name.
     *
     * @var string|null
     */
    protected string|null $through;

    /**
     * Through id or array of ids.
     *
     * @var array|mixed
     */
    protected mixed $throughId;

    /**
     * Through category id or array of ids.
     *
     * @var array|mixed
     */
    protected mixed $categoryThroughId;

    /**
     * Select fields.
     *
     * @var array|null
     */
    protected array|null $select;

    /**
     * "status" field value(s) for tags table.
     *
     * @var array|mixed
     */
    protected array $status;

    /**
     * Source table primary key.
     *
     * @var string
     */
    protected string $sourceKey;

    /**
     * Target table primary key.
     *
     * @var string
     */
    protected string $targetKey;

    /**
     * Target table name.
     *
     * @var string
     */
    protected string $targetTable;

    /**
     * Query builder.
     *
     * @var Builder
     */
    protected Builder $query;

    /**
     * Eloquent built in has relation class name.
     *
     * @link \Illuminate\Database\Eloquent\Relations\HasMany
     * @link \Illuminate\Database\Eloquent\Relations\HasOne
     * @var string
     */
    protected string $hasRelation;


    /**
     * Eloquent built in has relation class name.
     *
     * @link \Illuminate\Database\Eloquent\Relations\HasMany
     * @link \Illuminate\Database\Eloquent\Relations\HasOne
     * @var HasOne|HasMany
     */
    protected HasOne|HasMany $relation;

    /**
     * Target model instance.
     *
     * @var Model
     */
    protected Model $targetModel;

    /**
     * Target model class name.
     *
     * @var string
     */
    protected string $targetClass;

    /**
     * Category type.
     *
     * @var int
     */
    protected int $categoryType;

    /**
     * Through type id.
     *
     * @var int
     */
    protected int $throughType;

    /**
     * Category through type id.
     *
     * @var int
     */
    protected int $categoryThroughType;


    /**
     * Connect key depends on inverse param.
     *
     * @var string
     */
    protected string $connectKey;

    /**
     * Connect key with prefixed table name.
     *
     * @var mixed
     */
    protected mixed $connectKeyFull;

    /**
     * Construct relationship instance.
     *
     * @param bool $inverse
     * @param string $oneOrMany
     * @param Model $source
     * @param string $category
     * @param mixed $categoryId
     * @param string|null $target
     * @param string|null $through
     * @param mixed $throughId
     * @param string|null $categoryThrough
     * @param mixed $categoryThroughId
     * @param string|null $tableAlias
     * @param array|null $select
     * @param mixed $status
     * @param string|null $modelModel
     * @param string|null $tagModel
     * @return void
     */
    public function __construct(
        bool        $inverse,
        string      $oneOrMany,
        Model       $source,
        string      $category,
        mixed       $categoryId,
        string|null $target = null,
        string|null $through = null,
        mixed       $throughId = [0],
        string|null $categoryThrough = null,
        mixed       $categoryThroughId = [0],
        string|null $tableAlias = null,
        array|null  $select = null,
        mixed       $status = [1],
        string|null $modelModel = null,
        string|null $tagModel = null,
    ) {

        $this->setupModels($modelModel, $tagModel);

        $this->oneOrMany = $oneOrMany;
        $this->self = get_class($source);
        $this->sourceModel = $source;

        $this->category = $category;
        $this->categoryType = $this->getModelId($category);
        $this->categoryId = !is_array($categoryId) ? [$categoryId] : $categoryId;

        $this->tableAlias = $tableAlias;
        $this->target = $target;
        $this->targetClass = $this->qualifyTargetModel($this->target);
        $this->targetModel = new ($this->targetClass)();


        // Get the original target table
        $this->targetTable = $this->targetModel->getTable();

        // Switch the table to tags table to establish connection via eloquent
        $this->targetModel->setTable($this->tagTable);


        $this->throughType = $this->qualifyThroughType($through);
        $this->throughId = !is_array($throughId) ? [$throughId] : $throughId;

        $this->categoryThroughType = $this->qualifyThroughType($categoryThrough);
        $this->categoryThroughId = [...$categoryThroughId];

        $this->select = $select;
        $this->status = !is_array($status) ? [$status] : $status;

        $this->sourceKey = $this->sourceModel->getKeyName();
        $this->targetKey = $this->targetModel->getKeyName();


        $this->query = $this->targetModel->newQuery();

        $this->hasRelation = strtolower($oneOrMany) === 'one' ? HasOne::class : HasMany::class;


        $this->inverse = $inverse;
        $this->connectKey = !$this->inverse ? 'source_id' : 'target_id';
        $this->connectKeyFull = "$this->tagTable.$this->connectKey";

        // Create relation of type HasOne or HasMany
        $this->relation = new $this->hasRelation(
            $this->query,
            $this->sourceModel,
            $this->connectKeyFull,
            !$this->inverse ? $this->sourceModel->getKeyName() : $this->targetModel->getKeyName()
        );


        if ($this->inverse) {
            $this->buildTagThroughInverse();
        } else {
            $this->buildTagThrough();
        }

        $this->handleSoftDeletes();

        $this->resetTargetTable();
    }


    /**
     * Build tag through relationship.
     *
     * @return void
     */
    protected function buildTagThrough() {

        $this->joinRelation();

        $this->whereRelation();

        $this->buildSelect();

        $this->handleSoftDeletes();

        $this->resetTargetTable();
    }

    /**
     * Join to target model.
     *
     * @return void
     */
    protected function joinRelation() {

        if ($this->hasTarget()) {

            $this->relation->join("$this->tagTable as mt_$this->targetTable", function ($join) {

                // Join on source_type and source_id with the tag table
                $join->where("mt_$this->targetTable.source_type", $this->getModelId($this->tagModelClass))
                    ->on("mt_$this->targetTable.source_id", "$this->tagTable.id");

                // Join on target_type only if target was specified
                if ($this->target !== null && $this->target !== 'any') {
                    $join->where("mt_$this->targetTable.target_type", $this->getModelId($this->target));
                }

                $join->where("mt_$this->targetTable.thru_type", $this->throughType)
                    ->whereIn("mt_$this->targetTable.thru_id", $this->throughId);

                $join->whereIn("mt_$this->targetTable.status", $this->status);

            });

            if ($this->hasTableAlias()) {
                $joinAlias = "$this->targetTable as $this->tableAlias";
            } else {
                if ($this->target === 'any') {
                    $this->tableAlias = 'any_' . $this->targetTable;
                    $joinAlias = "$this->targetTable as $this->tableAlias";
                } else {
                    $this->tableAlias = $this->targetTable;
                    $joinAlias = "$this->targetTable";
                }
            }

            $join = $this->target == 'any' ? 'rightJoin' : 'join';

            $this->relation->$join($joinAlias, function ($join) {
                $sourceOrTarget = $this->target === 'any' ? 'source_id' : 'target_id';
                $join->on("mt_$this->targetTable.$sourceOrTarget", "$this->tableAlias.$this->targetKey");
            });

        }

    }

    /**
     * Find octopus relation via specific where clauses.
     *
     * @return void
     */
    protected function whereRelation() {

        $this->relation->where("$this->tagTable.source_type", $this->getModelId($this->self))
            ->where("$this->tagTable.target_type", $this->categoryType)
            ->whereIn("$this->tagTable.target_id", $this->categoryId)
            ->where("$this->tagTable.thru_type", $this->categoryThroughType)
            ->whereIn("$this->tagTable.thru_id", $this->categoryThroughId)
            ->whereIn("$this->tagTable.status", $this->status);
    }

    /**
     * Build select statement for the relationship.
     *
     * @return void
     */
    protected function buildSelect() {

        // Prepare fields to select:
        $selectFields = $this->select === null
            // No select query supplied?
            ? [
                // Then get all columns, depending if there is a target model specified
                !$this->hasTarget()
                    // No target specified? then get all columns from the tags table
                    ? "$this->tagTable.*"
                    // We have a target specified!
                    : (
                $this->target === 'any'
                    // Is it 'any' target? then get all columns from joined table
                    ? "mt_$this->targetTable.*"
                    // Is it an actual target table? then get all
                    // columns from the target table specified
                    // inside the tableAlias
                    : "$this->tableAlias.*"
                )
            ]
            : $this->select;

        $this->relation->select(array_merge(
                $selectFields,
                !$this->hasTarget() ?
                    // Select nothing more if there is no target model * table.
                    [] :
                    // There is a target model & table? Then select detailed
                    // useful columns from intermediate connection tables.
                    [
                        ...($this->target === 'any' ? ["mt_$this->targetTable.source_id as __source_id"] : []),
                        // Note! that 'source_id' field has to be present for the tag
                        // so that eloquent can merge the relationships
                        "$this->tagTable.source_id",
                        "$this->tagTable.id as __tag_category_id",
                        "$this->tagTable.status as __tag_category_status",
                        "mt_$this->targetTable.id as __tag_id",
                        "mt_$this->targetTable.status as __tag_status"
                    ]
            )
        );
    }


    /**
     * Build inverse relationship.
     *
     * @return void
     */
    protected function buildTagThroughInverse() {

        $this->joinRelationInverse();

        $this->whereRelationInverse();

        $this->buildSelectInverse();
    }


    /**
     * Join to target model inverse.
     *
     * @return void
     */
    protected function joinRelationInverse() {

        if ($this->hasTarget()) {

            $this->relation->join("$this->tagTable as mt_$this->targetTable", function ($join) {

                $join->on("mt_$this->targetTable.id", "$this->tagTable.source_id")
                    ->where("mt_$this->targetTable.target_type", $this->getModelId($this->category));

                if ($this->hasTarget()) {
                    $join->where("mt_$this->targetTable.source_type", $this->getModelId($this->targetClass));
                }

                $join->where("mt_$this->targetTable.thru_type", $this->throughType)
                    ->whereIn("mt_$this->targetTable.thru_id", $this->throughId)
                    ->whereIn("mt_$this->targetTable.status", $this->status);
            });

            if ($this->tableAlias == null) {
                $this->tableAlias = $this->targetTable;
                $joinTable = $this->targetTable;
            } else {
                $joinTable = "$this->targetTable as $this->tableAlias";
            }

            $this->relation->join($joinTable, function ($join) {
                $join->on("mt_$this->targetTable.source_id", "$this->tableAlias.$this->targetKey");
            });
        }

    }

    /**
     * Find octopus inverse relation via specific where clauses.
     *
     * @return void
     */
    protected function whereRelationInverse() {

        $this->relation
            ->where("$this->tagTable.source_type", $this->getModelId($this->tagModelClass))
            ->where("$this->tagTable.target_type", $this->getModelId($this->self))
            ->whereIn("mt_$this->targetTable.target_id", $this->categoryId)
            ->where("$this->tagTable.thru_type", $this->categoryThroughType)
            ->whereIn("$this->tagTable.thru_id", $this->categoryThroughId)
            ->whereIn("$this->tagTable.status", $this->status);

    }


    /**
     * Build select statement for the inverse relationship.
     *
     * @return void
     */
    protected function buildSelectInverse() {
        $selectFields = $this->select === null
            ? [
                !$this->hasTarget()
                    ? "$this->tagTable.*"
                    : "$this->tableAlias.*"
            ]
            : $this->select;
        $this->relation->select(array_merge($selectFields, $this->hasTarget() ? [
            // 'target_id' field has to be present for eloquent to be able to get
            // the reference id and build other relationships
            $this->connectKeyFull,
            // these are other intermediary table ids that are useful
            "$this->tagTable.id as __tag_id",
            "$this->tagTable.status as __tags_status",
            "mt_$this->targetTable.id as __tag_category_id",
            "mt_$this->targetTable.status as __tag_category_status"
        ] : []
        ));
    }

    protected function hasTableAlias() {
        return $this->tableAlias !== null;
    }

    /**
     * Reset target table to its initial name.
     *
     * @return void
     */
    protected function resetTargetTable() {
        $this->targetModel->setTable($this->targetTable);
    }

    /**
     * Return built relation.
     *
     * @return HasMany|HasOne
     */
    public function getRelation() {
        return $this->relation;
    }

    /**
     * Target exists.
     *
     * @return bool
     */
    protected function hasTarget() {
        return $this->target !== null;
    }


    /**
     * Handle soft deletes if they are enabled in target model.
     *
     * @return void
     */
    protected function handleSoftDeletes() {
        // Handle deleted_at parameter

        $traits = _class_uses_deep($this->targetClass);

        if (isset($traits['Illuminate\Database\Eloquent\SoftDeletes']) && $this->hasTarget()) {

            $deletedAtField = defined("$this->targetClass::DELETED_AT")
                ? $this->targetClass::DELETED_AT
                : 'deleted_at';

            $this->relation
                ->withTrashed()
                ->whereNull("$this->tableAlias.$deletedAtField");
        }

    }

}
