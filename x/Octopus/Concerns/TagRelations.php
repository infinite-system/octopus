<?php

namespace X\Octopus\Concerns;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

use X\Octopus\TagThrough;
use X\Octopus\Tag;

trait TagRelations
{
    use TagSetupModels;

    /**
     * Tag one relationship.
     *
     * @param string|null $target
     * @param string|null $through
     * @param mixed $throughId
     * @param array $select
     * @param mixed $status
     * @param string|null $modelModel
     * @param string|null $tagModel
     * @return HasOneThrough
     */
    public function tagOne(
        string|null $target = null,
        string|null $through = null,
        mixed       $throughId = [0],
        array       $select = [],
        mixed       $status = [1],
        string|null $modelModel = null,
        string|null $tagModel = null
    ): HasOneThrough {

        return (new Tag(
            false,
            'one',
            $this,
            $target,
            $through,
            $throughId,
            $select,
            $status,
            $modelModel ?: $this->modelModelClass,
            $tagModel ?: $this->tagModelClass))->getRelation();
    }

    /**
     * Tag one inverse relationship.
     *
     * @param string|null $target
     * @param string|null $through
     * @param mixed $throughId
     * @param array $select
     * @param mixed $status
     * @param string|null $modelModel
     * @param string|null $tagModel
     * @return HasOneThrough
     */
    public function tagOneInverse(
        string|null $target = null,
        string|null $through = null,
        mixed       $throughId = [0],
        array       $select = [],
        mixed       $status = [1],
        string|null $modelModel = null,
        string|null $tagModel = null
    ): HasOneThrough {

        return (new Tag(
            true,
            'one',
            $this,
            $target,
            $through,
            $throughId,
            $select,
            $status,
            $modelModel ?: $this->modelModelClass,
            $tagModel ?: $this->tagModelClass))->getRelation();
    }

    /**
     * Tag many relationship.
     *
     * @param string|null $target
     * @param string|null $through
     * @param mixed $throughId
     * @param array $select
     * @param mixed $status
     * @param string|null $modelModel
     * @param string|null $tagModel
     * @return HasManyThrough
     */
    public function tagMany(
        string|null $target = null,
        string|null $through = null,
        mixed       $throughId = [0],
        array       $select = [],
        mixed       $status = [1],
        string|null $modelModel = null,
        string|null $tagModel = null
    ): HasManyThrough {

        return (new Tag(
            false,
            'many',
            $this,
            $target,
            $through,
            $throughId,
            $select,
            $status,
            $modelModel ?: $this->modelModelClass,
            $tagModel ?: $this->tagModelClass))->getRelation();
    }

    /**
     * Tag many inverse relationship.
     *
     * @param string|null $target
     * @param string|null $through
     * @param mixed $throughId
     * @param array $select
     * @param mixed $status
     * @param string|null $modelModel
     * @param string|null $tagModel
     * @return HasManyThrough
     */
    public function tagManyInverse(
        string|null $target = null,
        string|null $through = null,
        mixed       $throughId = [0],
        array       $select = [],
        mixed       $status = [1],
        string|null $modelModel = null,
        string|null $tagModel = null
    ): HasManyThrough {

        return (new Tag(
            true,
            'many',
            $this,
            $target,
            $through,
            $throughId,
            $select,
            $status,
            $modelModel ?: $this->modelModelClass,
            $tagModel ?: $this->tagModelClass))->getRelation();
    }

    /**
     * Tag one through relationship.
     *
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
     * @return HasOne
     */
    public function tagOneThrough(
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
        string|null $tagModel = null
    ): HasOne {

        return (new TagThrough(
            false,
            'one',
            $this,
            $category,
            $categoryId,
            $target,
            $through,
            $throughId,
            $categoryThrough,
            $categoryThroughId,
            $tableAlias,
            $select,
            $status,
            $modelModel ?: $this->modelModelClass,
            $tagModel ?: $this->tagModelClass))->getRelation();
    }

    /**
     * Tag one through inverse relationship.
     *
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
     * @return HasOne
     */
    public function tagOneThroughInverse(
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
        string|null $tagModel = null
    ): HasOne {

        return (new TagThrough(
            true,
            'one',
            $this,
            $category,
            $categoryId,
            $target,
            $through,
            $throughId,
            $categoryThrough,
            $categoryThroughId,
            $tableAlias,
            $select,
            $status,
            $modelModel ?: $this->modelModelClass,
            $tagModel ?: $this->tagModelClass,
        ))->getRelation();
    }

    /**
     * Tag many through relationship.
     *
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
     * @return HasMany
     */
    public function tagManyThrough(
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
        string|null $tagModel = null
    ): HasMany {

        return (new TagThrough(
            false,
            'many',
            $this,
            $category,
            $categoryId,
            $target,
            $through,
            $throughId,
            $categoryThrough,
            $categoryThroughId,
            $tableAlias,
            $select,
            $status,
            $modelModel ?: $this->modelModelClass,
            $tagModel ?: $this->tagModelClass))->getRelation();
    }

    /**
     * Tag many through inverse relationship.
     *
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
     * @return HasMany
     */
    public function tagManyThroughInverse(
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
        string|null $tagModel = null
    ): HasMany {

        return (new TagThrough(
            true,
            'many',
            $this,
            $category,
            $categoryId,
            $target,
            $through,
            $throughId,
            $categoryThrough,
            $categoryThroughId,
            $tableAlias,
            $select,
            $status,
            $modelModel ?: $this->modelModelClass,
            $tagModel ?: $this->tagModelClass,
        )
        )->getRelation();
    }
}
