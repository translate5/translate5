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


/***
 * Quick create of zend model with database and validator file.
 * Example of creating TaskConfig model:
 * ./translate5.sh dev:newmodel -N TaskConfig -T LEK_task_config -K id -F id:int -F taskGuid:string:255 -F name:stringLength:255 -F value:string
 * TODO: test me for plugins. not testet may not work
 */
class DevelopmentNewModelCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'dev:newmodel';
    
    /**
     * @var InputInterface
     */
    protected $input;
    
    /**
     * @var OutputInterface
     */
    protected $output;
    
    /**
     * @var SymfonyStyle
     */
    protected $io;
    
    /***
     * Class name prefix used in generate files. For plugins this will be defferent
     * @var string
     */
    protected $classNamePrefix="editor_Models_";
    
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
            'key',
            'K',
            InputOption::VALUE_OPTIONAL,
            'Optional table primary key. By default id.');
        
        $this->addOption(
            'plugin',
            'P',
            InputOption::VALUE_OPTIONAL,
            'Plugin name when the current files are create in plugin contenxt.');

        $this->addOption(
            'fields',
            'F',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Table fields and field properties separated with ":". The first element is the field name, secound is the field type, third is the field size(for strings only for now).');
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
        
        $name = $input->getOption('name');
        $this->checkFile($dbDirectory, $name);
        $fields = $input->getOption('fields');
        $fields = $this->converFields($fields);
        $this->makePhp($dbDirectory, $name,$fields);
        
        $validatorDir = $this->getDirectoryValidator($dbDirectory);
        $this->makePhpValidator($validatorDir, $name,$fields);
        
        $table = $input->getOption('table');
        $key = $input->getOption('key');
        $dbDir = $this->getDirectoryDb($dbDirectory);
        $this->makeModelDbClass($dbDir, $name, $table,$key);
        return 0;
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
            $this->io->warning(['Given path does not end with Models. Please check that and move the created files.','Path: '.$search]);
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
            throw new \Exception('Given path is no directory! Path: '.$validatorDir);
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
        if(!is_dir($dbDir)) {
            throw new \Exception('Given path is no directory! Path: '.$dbDir);
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
    
    /***
     * Convert the files parametar to key value pair array. 
     * @param array $fields
     * @return array
     */
    protected function converFields($fields){
        $return = [];
        foreach ($fields as $f) {
            $keyvalue = explode(':', $f);
            //for additional field params (length for example we can add more fields as value param)
            if(count($keyvalue)>2){
                $return[$keyvalue[0]] = $keyvalue;
            }else{
                $return[$keyvalue[0]] = $keyvalue[1];
            }
        }
        return $return;
    }
    
    protected function getFieldRenderFunction ($field,$type) {
        $return = [];
        $ufirst = ucfirst($field);
        switch ($type) {
            case "stringLength":
                $type = "string";
            break;
            case "integer":
                $type = "int";
                break;
            case "date":
            case "timestamp":
                $type = "string";
                break;
        }
        $return[]='* @method void set'.$ufirst.'() set'.$ufirst.'('.$type.' $'.$field.')';
        $return[]='* @method '.$type.' get'.$ufirst.'() get'.$ufirst.'()';
        return join(PHP_EOL,$return);
    }
    
    /***
     * Get render validator for given field and type.
     * TODO: add more validator types here
     * @param string $field
     * @param mixed $type
     * @return string
     */
    protected function getFieldValidatorRenderFunction ($field,$type) {
        $return = "";
        $fieldType = $type;
        $length = null;
        if(is_array($type)){
            $fieldType = $type[1];
            $length = $type[2];
        }
        switch ($fieldType) {
            case 'int':
            case 'integer':
                $return = '$this->addValidator("'.$field.'", "int");';
            break;
            case 'stringLength':
                $return = '$this->addValidator("'.$field.'","stringLength"';
                if(!empty($length)){
                    $return.=', array("min" => 0, "max" => '.$length.')';
                }
                $return.=');';
                break;
            default:
                $return = '$this->addValidator("'.$field.'","'.$fieldType.'");';
            break;
        }
        return $return;
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
     * @param string $name
     * @param string $table
     * @param string $key
     */
    protected function makeModelDbClass($dir,$name,$table,$key='id'){
        $name = ucfirst($name);
        $file = $dir.'/'.$name.'.php';
        $this->io->success('Created file '.$file);
        
        file_put_contents($file, '<?php '
            .$this->getTranslate5LicenceText().'

class '.$this->classNamePrefix.'Db_'.$name.' extends Zend_Db_Table_Abstract {
    protected $_name  = "'.$table.'";
    public $_primary = "'.$key.'";
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
        foreach ($fields as $field => $type){
            $validators[] = $this->getFieldValidatorRenderFunction($field, $type);
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
        foreach ($fields as $field => $type){
            if(is_array($type)){
                $type = $type[1];
            }
            $settersGetters[] = $this->getFieldRenderFunction($field, $type);
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
