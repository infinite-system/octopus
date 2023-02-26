<?php

namespace X\Octopus;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Tag
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
     * Target class name.
     *
     * @var string|null
     */
    protected string|null $originalTarget;

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
     * Through class name for category tag.
     *
     * @var string
     */
    protected string $categoryThrough;

    /**
     * Through category id or array of ids.
     *
     * @var array|mixed
     */
    protected mixed $categoryThroughId;

    /**
     * Select fields.
     *
     * @var array
     */
    protected array $select;

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
     * @link \Illuminate\Database\Eloquent\Relations\HasManyThrough
     * @link \Illuminate\Database\Eloquent\Relations\HasOneThrough
     * @var HasOneThrough|HasManyThrough
     */
    protected HasOneThrough|HasManyThrough $relation;

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
     * Through type id.
     *
     * @var int
     */
    protected int $throughType;

    /**
     * Construct relationship instance.
     *
     * @param bool $inverse
     * @param string $oneOrMany
     * @param Model $source
     * @param string|null $target
     * @param string|null $through
     * @param mixed $throughId
     * @param array $select
     * @param mixed $status
     * @param string|null $modelModel
     * @param string|null $tagModel
     * @return void
     */
    public function __construct(
        bool        $inverse,
        string      $oneOrMany,
        Model       $source,
        string|null $target = null,
        string|null $through = null,
        mixed       $throughId = [0],
        array       $select = [],
        mixed       $status = [1],
        string|null $modelModel = null,
        string|null $tagModel = null,
    ) {

        $this->setupModels($modelModel, $tagModel);

        $this->self = get_class($source);
        $this->sourceModel = $source;

        $this->target = $target;
        $this->targetClass = $this->qualifyTargetModel($this->target);
        $this->targetModel = new ($this->targetClass)();

        // Get the original target table
        $this->targetTable = $this->targetModel->getTable();


        $this->throughType = $this->qualifyThroughType($through);
        $this->throughId = !is_array($throughId) ? [$throughId] : $throughId;

        $this->select = empty($select) ? [$this->targetTable . '.*'] : $select;
        $this->status = !is_array($status) ? [$status] : $status;

        $this->sourceKey = $this->sourceModel->getKeyName();
        $this->targetKey = $this->targetModel->getKeyName();

        $hasRelation = strtolower($oneOrMany) === 'one' ? 'hasOneThrough' : 'hasManyThrough';

        $this->inverse = $inverse;

        // Create relation of type HasOne or HasMany
        $this->relation = $source->{$hasRelation}(
            $this->targetClass,
            $this->tagModelClass,
            !$this->inverse ? 'source_id' : 'target_id', // Modal tag source or target ids
            !$this->inverse ? $this->targetKey : $this->sourceKey,
            !$this->inverse ? $this->sourceKey : $this->targetKey,
            !$this->inverse ? 'target_id' : 'source_id'
        )
        // Connect modal tag types
        ->where("$this->tagTable.source_type", $this->getModelId(!$this->inverse ? $this->self : $this->targetClass))
            ->where("$this->tagTable.target_type", $this->getModelId(!$this->inverse ? $this->targetClass : $this->self))
            ->where("$this->tagTable.thru_type", $this->throughType)
            ->whereIn("$this->tagTable.thru_id", $this->throughId)
            ->whereIn("$this->tagTable.status", $this->status)
            // Select
            ->select(array_merge($this->select, [
                    // This field has to be present for modal tag to get the reference id
                    "$this->tagTable.id as __tag_id",
                    "$this->tagTable.status as __tag_status"
                ])
            );

    }

    /**
     * Return built relation.
     *
     * @return HasManyThrough|HasOneThrough
     */
    public function getRelation() {
        return $this->relation;
    }
}
