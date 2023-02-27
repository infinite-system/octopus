<?php

namespace X\Octopus\Concerns;

use Illuminate\Database\Eloquent\Model;

trait TagSetupModels
{
    /**
     * Default model class to use as tags model.
     *
     * @var string
     */
    protected string $tagModelClass = '\App\ModalTag';

    /**
     * Default model class to use as models model.
     *
     * @var string
     */
    protected string $modelModelClass = '\App\Modal';

    /**
     * Model table name.
     *
     * @var string
     */
    protected string $modelTable;

    /**
     * Tag table name.
     *
     * @var string
     */
    protected string $tagTable;

    /**
     * Model eloquent model.
     *
     * @var Model
     */
    protected Model $modelModel;

    /**
     * Tag eloquent model.
     *
     * @var Model
     */
    protected Model $tagModel;

    /**
     * List of models.
     *  ['\App\ModelName' => 1, ...]
     * @var array
     */
    protected array $modelsList = [];

    /**
     * Get the list of models.
     *
     * @return void
     */
    public function getModels() {
        if (empty($this->modelsList)) {
            $models = $this->modelModelClass::get();
            $this->modelsList = !empty($models) ? $models->pluck('id', 'name')->toArray() : [];
        }
    }

    /**
     * Get specific model id.
     *
     * @return int
     */
    public function getModelId($key) {
        if (empty($this->modelsList)) {
            $this->getModels();
        }
        return $this->modelsList[$key[0] === '\\' ? substr($key, 1) : $key];
    }

    /**
     * Setup model and tag models that are
     * required to make tag relationships.
     *
     * @param $modelModel
     * @param $tagModel
     * @return void
     */
    protected function setupModels($modelModel, $tagModel) {
        $this->modelModelClass = $modelModel;
        $this->tagModelClass = $tagModel;
        $this->modelModel = new $modelModel();
        $this->tagModel = new $tagModel();
        $this->modelTable = $this->getModelTable();
        $this->tagTable = $this->getTagTable();
    }

    /**
     * Get tag model.
     *
     * @return Model
     */
    public function getTagModel() {
        return $this->tagModel;
    }

    /**
     * Get model model.
     *
     * @return Model
     */
    public function getModelModel() {
        return $this->modelModel;
    }

    /**
     * Get tag table.
     *
     * @return string
     */
    public function getTagTable() {
        return $this->tagModel->getTable();
    }

    /**
     * Get model table.
     *
     * @return string
     */
    public function getModelTable() {
        return $this->modelModel->getTable();
    }

    /**
     * Qualify which model we are connecting to as target.
     *
     * @param $target
     * @return string
     */
    protected function qualifyTargetClass($target) {
        return match ($target) {
            'any', null => $this->tagModelClass,
            default => $target,
        };
    }

    /**
     * If through type is not specified it will be 0,
     * otherwise it will find it in models prop.
     *
     * @param $throughType
     * @return int
     */
    protected function qualifyThroughType($throughType) {
        return $throughType === null ? 0 : $this->getModelId($throughType);
    }

    /**
     * Dump SQL for debuging the relation query.
     *
     * @return void
     */
    protected function dumpSql() {
        $bindings = array_map(function ($r) { return "'$r'"; }, $this->relation->getQuery()->getBindings());
        $sqlWithBindings = \str_ireplace_array('?', $bindings,$this->relation->getQuery()->toSql());
        dump($sqlWithBindings);
    }
}
