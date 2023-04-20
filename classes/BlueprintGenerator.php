<?php namespace RainLab\Builder\Classes;

use Lang;
use File;
use Twig;
use RainLab\Builder\Classes\TailorBlueprintLibrary;
use ApplicationException;
use ValidationException;
use Throwable;

/**
 * BlueprintGenerator is a helper class for generating controller class files and associated files.
 *
 * @package rainlab\builder
 * @author Alexey Bobkov, Samuel Georges
 */
class BlueprintGenerator
{
    use \RainLab\Builder\Classes\BlueprintGenerator\HasMigrations;
    use \RainLab\Builder\Classes\BlueprintGenerator\HasVersionFile;
    use \RainLab\Builder\Classes\BlueprintGenerator\HasControllers;
    use \RainLab\Builder\Classes\BlueprintGenerator\HasPermissions;
    use \RainLab\Builder\Classes\BlueprintGenerator\HasNavigation;
    use \RainLab\Builder\Classes\BlueprintGenerator\HasModels;

    /**
     * @var object sourceModel is the imports model
     */
    protected $sourceModel;

    /**
     * @var array sourceBlueprints are blueprint objects that can be saved to disk
     */
    protected $sourceBlueprints = [];

    /**
     * @var array templateVars are used when rendering templates
     */
    protected $templateVars;

    /**
     * @var array filesGenerated by this process
     */
    protected $filesGenerated;

    /**
     * @var array blueprintFiles to decommission
     */
    protected $blueprintFiles = [];

    /**
     * @var array migrationScripts that have been generated
     */
    protected $migrationScripts = [];

    /**
     * __construct
     */
    public function __construct($source)
    {
        $this->sourceModel = $source;
    }

    /**
     * generate
     */
    public function generate()
    {
        $this->templateVars = [];
        $this->filesGenerated = [];
        $this->blueprintFiles = [];
        $this->migrationScripts = [];
        $this->sourceBlueprints = [];

        $this->loadSourceBlueprints();
        $this->validateNavigation();

        // Validate
        foreach ($this->sourceBlueprints as $blueprint) {
            $this->setBlueprintContext($blueprint);
            $this->validateModel();
            $this->validatePermission();
            $this->validateController();
        }

        // Generate
        try {
            foreach ($this->sourceBlueprints as $blueprint) {
                $this->setBlueprintContext($blueprint);
                $this->generateMigration();
                $this->generateModel();
                $this->generatePermission();
                $this->generateController();

                $this->blueprintFiles[] = $blueprint->getFilePath();
            }
        }
        catch (Throwable $ex) {
            $this->rollback();
            throw $ex;
        }

        $this->generateNavigation();
        $this->generateVersionUpdate();
        $this->disableGeneratedBlueprints();
    }

    /**
     * loadSourceBlueprints
     */
    protected function loadSourceBlueprints()
    {
        $blueprintLib = TailorBlueprintLibrary::instance();

        foreach ($this->sourceModel->blueprints as $uuid => $config) {
            $blueprint = $blueprintLib->getBlueprintObject($uuid);
            if ($blueprint) {
                $this->sourceBlueprints[$uuid] = $blueprint;
            }
        }
    }

    /**
     * setBlueprintContext
     */
    protected function setBlueprintContext($blueprint)
    {
        $config = $this->sourceModel->blueprints[$blueprint->uuid] ?? [];

        $this->sourceModel->setBlueprintContext($blueprint, $config);

        $this->setTemplateVars();
    }

    /**
     * disableGeneratedBlueprints
     */
    protected function disableGeneratedBlueprints()
    {
        foreach ($this->blueprintFiles as $filePath) {
            File::move(
                $filePath,
                str_replace('.yaml', '.yaml.bak', $filePath)
            );
        }
    }

    /**
     * setTemplateVars
     */
    protected function setTemplateVars()
    {
        $pluginCodeObj = $this->sourceModel->getPluginCodeObj();

        $this->templateVars = $this->getConfig();
        $this->templateVars['pluginNamespace'] = $pluginCodeObj->toPluginNamespace();
        $this->templateVars['pluginCode'] = $pluginCodeObj->toCode();
    }

    /**
     * getTemplatePath
     */
    protected function getTemplatePath($template)
    {
        return __DIR__.'/blueprintgenerator/templates/'.$template;
    }

    /**
     * parseTemplate
     */
    protected function parseTemplate($templatePath, $vars = [])
    {
        $template = File::get($templatePath);

        $vars = array_merge($this->templateVars, $vars);
        $code = Twig::parse($template, $vars);

        return $code;
    }

    /**
     * writeFile
     */
    protected function writeFile($path, $data)
    {
        $fileDirectory = dirname($path);
        if (!File::isDirectory($fileDirectory)) {
            if (!File::makeDirectory($fileDirectory, 0777, true, true)) {
                throw new ApplicationException(Lang::get('rainlab.builder::lang.common.error_make_dir', [
                    'name' => $fileDirectory
                ]));
            }
        }

        if (@File::put($path, $data) === false) {
            throw new ApplicationException(Lang::get('rainlab.builder::lang.controller.error_save_file', [
                'file' => basename($path)
            ]));
        }

        @File::chmod($path);
        $this->filesGenerated[] = $path;
    }

    /**
     * rollback
     */
    protected function rollback()
    {
        foreach ($this->filesGenerated as $path) {
            @unlink($path);
        }
    }

    /**
     * makeTabs
     */
    protected function makeTabs($str)
    {
        return str_replace('\t', '    ', $str);
    }

    /**
     * getConfig
     */
    protected function getConfig($key = null, $default = null)
    {
        return $this->sourceModel->getBlueprintConfig($key, $default);
    }

    /**
     * validateUniqueFiles
     */
    protected function validateUniqueFiles(array $files)
    {
        foreach ($files as $path) {
            if (File::isFile($path)) {
                throw new ValidationException([
                    'modelClass' => __("File [:file] already exists for this plugin", [
                        'file' => basename($path)
                    ])
                ]);
            }
        }
    }
}
