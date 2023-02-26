<?php
use Tests\Models\ModalTag;
use Tests\Models\Modal;

/**
 * Tag create function.
 * 
 * @param $sourceModel
 * @param $sourceId
 * @param $targetModel
 * @param $targetId
 * @param $thruModel
 * @param $thruId
 * @param $firstOrCreate
 * @return mixed
 */
function _tag($sourceModel, $sourceId, $targetModel, $targetId, $thruModel = 0, $thruId = 0, $firstOrCreate = true) {
    $method = $firstOrCreate ? 'firstOrCreate' : 'create';
    return ModalTag::$method([
        'source_type' => is_numeric($sourceModel) ? $sourceModel : getModelId('Tests\\Models\\' . $sourceModel),
        'source_id' => $sourceId,
        'target_type' => is_numeric($targetModel) ? $targetModel : getModelId('Tests\\Models\\' . $targetModel),
        'target_id' => $targetId,
        'thru_type' => is_numeric($thruModel) ? $thruModel : getModelId('Tests\\Models\\' . $thruModel),
        'thru_id' => $thruId,
        'status' => 1
    ]);
}

/**
 * Category tag create function.
 * 
 * @param $sourceId
 * @param $targetModel
 * @param $targetId
 * @param $thruModel
 * @param $thruId
 * @param $firstOrCreate
 * @return mixed
 */
function _tagCategory($sourceId, $targetModel, $targetId, $thruModel = 0, $thruId = 0, $firstOrCreate = true) {
    $method = $firstOrCreate ? 'firstOrCreate' : 'create';

    return ModalTag::$method([
        'source_type' => getModelId('Tests\\Models\\ModalTag'),
        'source_id' => $sourceId,
        'target_type' => is_numeric($targetModel) ? $targetModel : getModelId('Tests\\Models\\' . $targetModel),
        'target_id' => $targetId,
        'thru_type' => is_numeric($thruModel) ? $thruModel : getModelId('Tests\\Models\\' . $thruModel),
        'thru_id' => $thruId,
        'status' => 1
    ]);
}


/**
 * Get inherited traits from parent classes.
 *
 * @param $class
 * @param bool $autoload
 * @return array
 */
function _class_uses_deep($class, $autoload = true) {
    $traits = [];

    // Get traits of all parent classes
    do {
        $traits = array_merge(class_uses($class, $autoload), $traits);
    } while ($class = get_parent_class($class));

    // Get traits of all parent traits
    $traitsToSearch = $traits;
    while (!empty($traitsToSearch)) {
        $newTraits = class_uses(array_pop($traitsToSearch), $autoload);
        $traits = array_merge($newTraits, $traits);
        $traitsToSearch = array_merge($newTraits, $traitsToSearch);
    };

    foreach ($traits as $trait => $same) {
        $traits = array_merge(class_uses($trait, $autoload), $traits);
    }

    return array_unique($traits);
}

/**
 * Return model id.
 *
 * @param $model
 * @return int
 */
function getModelId($model) {
    return (int) getModels()[$model[0] === '\\' ? substr($model, 1) : $model];
}

/**
 * Return models list.
 *
 * @return array
 */
function getModels(){
    static $modelsList = [];
    if (empty($modelsList)) {
        $models = Modal::get();
        $modelsList = !empty($models) ? $models->pluck('id', 'name')->toArray() : [];
    }
    return $modelsList;
}
