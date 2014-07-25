<?php
/**
 * ModuleMigration class file
 * @copyright Copyright (c) 2014 Galament
 * @license http://www.yiiframework.com/license/
 */

namespace bariew\moduleMigration;

use yii\console\controllers\MigrateController;

/**
 * Runs migrations from module /migrations folder.
 * 
 * @author Pavel Bariev <bariew@yandex.ru>
 */
class ModuleMigration extends MigrateController
{
    /**
     * @var array module base paths
     */
    public $allMigrationPaths = [];
    /**
     * @var array paths to migrations like [path => migrationName]
     */
    public $migrationFiles = [];
    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $this->allMigrationPaths['app'] = $this->migrationPath;
        $this->attachModuleMigrations();
        $this->setMigrationFiles();
        return true;
    }
    
    /**
     * @inheritdoc
     */
    protected function getNewMigrations()
    {
        $result = [];
        foreach ($this->allMigrationPaths as $path) {
            $this->migrationPath = $path;
            $result = array_merge($result, parent::getNewMigrations());
        }
        $this->migrationPath = $this->allMigrationPaths['app'];
        return $result;
    }

    /**
     * gets path to migration file
     * @param string $name migration name
     * @param bool|string $path module migrations base path
     * @return string path to migration file
     */
    protected function getMigrationFile($name, $path = false)
    {
        $path = $path ? $path : $this->migrationPath;
        return $path . DIRECTORY_SEPARATOR . $name . '.php';
    }
    
    /**
     * @inheritdoc
     */
    protected function createMigration($class)
    {
        if (!$file = array_search($class, $this->migrationFiles)) {
            return false;
        }
        require_once($file);
        return new $class(['db' => $this->db]);
    }
    /**
     * creates $allMigrationPaths attribute from module base paths
     */
    protected function attachModuleMigrations()
    {
        $s = DIRECTORY_SEPARATOR;
        foreach (\Yii::$app->modules as $name => $config) {
            switch (gettype($config)) {
                case 'object'   : 
                    $basePath = $config->basePath;
                    break;
                case 'array'    : 
                    if (isset($config['basePath'])) {
                        $basePath = $config['basePath'];
                        break;
                    }
                    $config = $config['class'];
                default         : 
                   // $basePath = \Yii::$app->basePath . "{$s}modules{$s}{$name}";
                    $basePath = str_replace('\\', '/', preg_replace('/^(.*)\\\(\w+)$/', '@$1', $config));
                    $basePath = \Yii::getAlias($basePath);
            }
            $path = $basePath . $s . 'migrations';
            if (file_exists($path) && !is_file($path)) {
                $this->allMigrationPaths[$name] = $path;
            }
        }
    }
    
    /**
     * Creates $migrationFiles array
     * @return array list of migrations like [path=>migrationName]
     */
    protected function setMigrationFiles()
    {
        foreach ($this->allMigrationPaths as $path) {
            $handle = opendir($path);
            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $filePath = $path . DIRECTORY_SEPARATOR . $file;
                if (preg_match('/^(m(\d{6}_\d{6})_.*?)\.php$/', $file, $matches) && is_file($filePath)) {
                    $this->migrationFiles[$filePath] = $matches[1];
                }
            }
            closedir($handle);
        }
        return $this->migrationFiles;
    }
}