<?php namespace RainLab\Builder\Classes;

use Cache;
use Input;
use RainLab\Builder\Models\PluginBaseModel;
use RainLab\Builder\Models\ModelModel;
use ApplicationException;
use Exception;

/**
 * Provides helper methods for Builder CMS components.
 *
 * @package rainlab\builder
 * @author Alexey Bobkov, Samuel Georges
 */
class ComponentHelper
{
    use \October\Rain\Support\Traits\Singleton;

    /**
     * @var array|null modelListCache
     */
    protected $modelListCache = null;

    /**
     * listGlobalModels
     */
    public function listGlobalModels()
    {
        if ($this->modelListCache !== null) {
            return $this->modelListCache;
        }

        $key = 'builder-global-model-list';
        $cached = Cache::get($key, false);

        if ($cached !== false && ($cached = @unserialize($cached)) !== false) {
            return $this->modelListCache = $cached;
        }

        $plugins = PluginBaseModel::listAllPluginCodes();

        $result = [];
        foreach ($plugins as $pluginCode) {
            try {
                $pluginCodeObj = new PluginCode($pluginCode);

                $models = ModelModel::listPluginModels($pluginCodeObj);

                $pluginCodeStr = $pluginCodeObj->toCode();
                $pluginModelsNamespace = $pluginCodeObj->toPluginNamespace().'\\Models\\';
                foreach ($models as $model) {
                    $fullClassName = $pluginModelsNamespace.$model->className;

                    // Exclude builder models
                    if (str_starts_with($fullClassName, 'RainLab\Builder')) {
                        continue;
                    }

                    $result[$fullClassName] = $pluginCodeStr.' - '.$model->className;
                }
            }
            catch (Exception $ex) {
                // Ignore invalid plugins and models
            }
        }

        $expiresAt = now()->addMinutes(1);
        Cache::put($key, serialize($result), $expiresAt);

        return $this->modelListCache = $result;
    }

    /**
     * getModelClassDesignTime
     */
    public function getModelClassDesignTime()
    {
        $modelClass = trim(Input::get('modelClass'));

        if ($modelClass && !is_scalar($modelClass)) {
            throw new ApplicationException('Model class name should be a string.');
        }

        if (!strlen($modelClass)) {
            $models = $this->listGlobalModels();
            $modelClass = key($models);
        }

        if (!ModelModel::validateModelClassName($modelClass)) {
            throw new ApplicationException('Invalid model class name.');
        }

        return $modelClass;
    }

    /**
     * listModelColumnNames
     */
    public function listModelColumnNames()
    {
        $modelClass = $this->getModelClassDesignTime();

        $key = md5('builder-global-model-list-'.$modelClass);
        $cached = Cache::get($key, false);

        if ($cached !== false && ($cached = @unserialize($cached)) !== false) {
            return $cached;
        }

        $pluginCodeObj = PluginCode::createFromNamespace($modelClass);

        $modelClassParts = explode('\\', $modelClass); // The full class name is already validated in PluginCode::createFromNamespace()
        $modelClass = array_pop($modelClassParts);

        $columnNames = ModelModel::getModelFields($pluginCodeObj, $modelClass);

        $result = [];
        foreach ($columnNames as $columnName) {
            $result[$columnName] = $columnName;
        }

        $expiresAt = now()->addMinutes(1);
        Cache::put($key, serialize($result), $expiresAt);

        return $result;
    }
}
