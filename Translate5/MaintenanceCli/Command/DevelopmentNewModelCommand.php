<?php
/*
 START LICENSE AND COPYRIGHT
 
 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
 
 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
 
 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
 
 END LICENSE AND COPYRIGHT
 */
namespace Translate5\MaintenanceCli\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zend_Db_Table;


/***
 * Quick create of zend model with database and validator file. The mysql table must exist in the database so the files are generated.
 * The field types and names are loaded from the given mysql table.
 * Example of creating TaskConfig model:
 * ./translate5.sh dev:newmodel -N TaskConfig -T Zf_task_config
 * TODO: test me for plugins. not testet may not work
 */
class DevelopmentNewModelCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'dev:newmodel';
    
    /***
     * Class name prefix used in generate files. For plugins this will be defferent
     * @var string
     */
    protected $classNamePrefix="editor_Models_";
    
    /***
     * 
     * @var Zend_Db_Table
     */
    protected $table;
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Development: Creates a new model php file.')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Creates a new php model and model validator file, with all defined fields.');

        $this->addArgument('path',
            InputArgument::OPTIONAL,
            'Path which should be used. Plugin or ZfExtended or default module, editor Models is the default if empty.'
        );
        
        $this->addOption(
            'name',
            'N',
            InputOption::VALUE_REQUIRED,
            'Force to enter Model name');
        
        $this->addOption(
            'table',
            'T',
            InputOption::VALUE_REQUIRED,
            'Force to enter database table name');
        
        $this->addOption(
            'plugin',
            'P',
            InputOption::VALUE_OPTIONAL,
            'Plugin name when the current files are create in plugin contenxt.');
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();
        
        $this->writeTitle('Create a new Model file');
        
        $dbDirectory = $this->getDirectory();
        
        $this->setClassNamePrefix($input->getOption('plugin'));
        
        $name = $input->getOption('name') ?? '';
        $this->checkFile($dbDirectory, $name);
        
        $table = $input->getOption('table');
        $this->loadTable($table);

        $this->makePhp($dbDirectory, $name,$this->table->info($this->table::METADATA));
        
        $validatorDir = $this->getDirectoryValidator($dbDirectory);
        $this->makePhpValidator($validatorDir, $name,$this->table->info($this->table::METADATA));
        
        $dbDir = $this->getDirectoryDb($dbDirectory);
        $this->makeModelDbClass($dbDir, $name, $this->table->info());
        
        return 0;
    }

    protected function loadTable(string $tableName){
        $this->table = \ZfExtended_Factory::get('Zend_Db_Table',[$tableName]);
    }
    
    /**
     * returns the directory to be used.
     * @throws \Exception
     * @return string|string|string[]|NULL
     */
    protected function getDirectory() {
        $search = $this->input->getArgument('path');
        if(empty($search)) {
            return APPLICATION_PATH.'/modules/editor/Models';
        }
        if(!is_dir($search)) {
            throw new \Exception('Given path is no directory! Path: '.$search);
        }
        $dir = basename($search);
        if($dir != 'Models') {
            //$this->io->warning(['Given path does not end with Models. Please check that and move the created files.','Path: '.$search]);
        }
        return $search;
    }

    /***
     * Get the validator file directory path
     * @param string $modelDirectory
     * @throws \Exception
     * @return string
     */
    protected function getDirectoryValidator(string $modelDirectory = "") {
        if(empty($modelDirectory)){
            $modelDirectory = $this->getDirectory();
        }
        $validatorDir = $modelDirectory.DIRECTORY_SEPARATOR.'Validator';
        if(!is_dir($validatorDir)) {
            if (!mkdir($validatorDir) && !is_dir($validatorDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $validatorDir));
            }
        }
        return $validatorDir;
    }
    
    /***
     * Get the db file directory path
     * @param string $modelDirectory
     * @throws \Exception
     * @return string
     */
    protected function getDirectoryDb(string $modelDirectory = "") {
        if(empty($modelDirectory)){
            $modelDirectory = $this->getDirectory();
        }
        $dbDir = $modelDirectory.DIRECTORY_SEPARATOR.'Db';
        if(!is_dir($dbDir) && !mkdir($dbDir) && !is_dir($dbDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dbDir));
        }
        return $dbDir;
    }
    
    /**
     * Check the impot file name
     * @param string $dbDirectory
     * @param string $name
     * @throws \Exception
     * @return string
     */
    protected function checkFile(string $dbDirectory, string $name): string {
        $fullPath = $dbDirectory.DIRECTORY_SEPARATOR.ucfirst($name).'php';
        if(is_file($fullPath)){
            throw new \Exception('Given Model already exist. Path was :'.$fullPath);
        }
        return true;
    }
    
    protected function getFieldRenderFunction ($field,$fieldInfo) {
        $return = [];
        $ufirst = ucfirst($field);
        $type = $this->getFieldType($fieldInfo['DATA_TYPE']);
        $return[]='* @method void set'.$ufirst.'() set'.$ufirst.'('.$type.' $'.$field.')';
        $return[]='* @method '.$type.' get'.$ufirst.'() get'.$ufirst.'()';
        return join(PHP_EOL,$return);
    }
    
    /***
     * Get render validator for given field and type.
     * TODO: add more validator types here
     * @param string $field
     * @param array $fieldInfo : contains the info about the field
     * @return string
     */
    protected function getFieldValidatorRenderFunction ($field,$fieldInfo) {
        $return = "";
        $fieldType = $this->getFieldType($fieldInfo['DATA_TYPE']);;
        switch ($fieldType) {
            case 'int':
                $return = '$this->addValidator("'.$field.'", "int");';
            break;
            case 'string':
                $return = '$this->addValidator("'.$field.'","stringLength"';
                if(!empty($fieldInfo['LENGTH'])){
                    $return.=', array("min" => '.abs($fieldInfo['NULLABLE']-=1).', "max" => '.$fieldInfo['LENGTH'].')';
                }
                $return.=');';
                break;
            case 'float':
                $return = '$this->addValidator("'.$field.'", "float");';
                break;
            default:
                $return = '$this->addValidator("'.$field.'","'.$fieldType.'");';
            break;
        }
        return $return;
    }
    
    /***
     * Return field php type based on the field mysql type
     * @param string $metadataType
     * @return string
     */
    protected function getFieldType(string $metadataType){
        switch ($metadataType) {
            case "char":
            case "varchar":
            case "binary":
            case "varbinary":
            case "blob":
            case "text":
            case "enum":
            case "set":
                return "string";
            case "tinyint":
            case "smallint":
            case "mediumint":
            case "integer":
            case "int":
            case "bigint":
                return "int";
            case "float":
            case "double":
                return "float";
            case "date":
            case "datetime":
            case "timestamp":
                return "string";
        }
        return "string";
    }
    
    protected function setClassNamePrefix($pluginName="") {
        if(empty($pluginName)){
            $this->classNamePrefix = "editor_Models_";
        }else{
            $this->classNamePrefix = "editor_Plugins_".$pluginName."_Models_";
        }
    }
    

    /***
     * Create database model file
     * 
     * @param string $dir
     */
    protected function makeModelDbClass(string $dir,string $name, array $info){
        $name = ucfirst($name);
        $file = $dir.'/'.$name.'.php';
        $this->io->success('Created file '.$file);
        $primary = array_shift(array_values($info[$this->table::PRIMARY])) ?? 'id';
        file_put_contents($file, '<?php '
            .$this->getTranslate5LicenceText().'

class '.$this->classNamePrefix.'Db_'.$name.' extends Zend_Db_Table_Abstract {
    protected $_name  = "'.$info[$this->table::NAME].'";
    public $_primary = "'.$primary.'";
}
');
    }
    
    /***
     * Create model validator class
     * @param string $dir
     * @param string $name
     * @param array $fields
     */
    protected function makePhpValidator($dir, $name,$fields){
        $name = ucfirst($name);
        $file = $dir.'/'.$name.'.php';
        $this->io->success('Created file '.$file);
        $validators = [];
        foreach ($fields as $field => $fieldInfo){
            $validators[] = $this->getFieldValidatorRenderFunction($field, $fieldInfo);
        }
        //add the validators wit 2 tabs
        file_put_contents($file, '<?php '
            .$this->getTranslate5LicenceText().'

class '.$this->classNamePrefix.'Validator_'.$name.' extends ZfExtended_Models_Validator_Abstract {
    protected function defineValidators() {
        '.implode(PHP_EOL.str_repeat(" ", 8), $validators).'
    }
}
');
    }
    /***
     * Create the model class with fields getters and setters
     * @param string $dir
     * @param string $name
     * @param array $fields
     */
    protected function makePhp($dir, $name,$fields) {
        $name = ucfirst($name);
        //TODO: pugins implemenatation
        $file = $dir.'/'.$name.'.php';
        $this->io->success('Created file '.$file);
        $settersGetters = [];
        foreach ($fields as $field => $info){
            $settersGetters[] = $this->getFieldRenderFunction($field, $info);
        }
        
        file_put_contents($file, '<?php '
.$this->getTranslate5LicenceText().'
/***
'.implode(PHP_EOL, $settersGetters).'
*/

class '.$this->classNamePrefix.$name.' extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = "'.$this->classNamePrefix.'Db_'.$name.'";
    protected $validatorInstanceClass = "'.$this->classNamePrefix.'Validator_'.$name.'";
}
');
    }
}
