<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 * Kapselt den Import der Meta Daten zu einem Projekt.
 * - sucht selbstständig nach MetaDaten im Projekt
 * - importiert die gefundenen MetaDaten
 *
 * Änderung der Definition MetaDaten: die bisher gültige Definition,
 * dass MetaDaten durch Dateinamen = taskGuid definiert sind, ist absofort hinfällig. Bei TBX Dateien soll einfach die erste TBX im Verzeichnis verwendet werden.
 */
class editor_Models_Import_MetaData {
    const META_QMFLAGS = 'qmflags';

    /**
     * @var editor_Models_Task
     */
    protected $task;
    /**
     * @var string
     */
    protected $importPath;
    /**
     * @var editor_Models_Languages
     */
    protected $sourceLang;
    /**
     * @var editor_Models_Languages
     */
    protected $targetLang;

    /**
     * Liste mit den aufgerufenen Importern
     * @var array
     */
    protected $importers = array();

    /**
     * @var array
     */
    protected $cache = array();
    
    /**
     * @var string
     */
    public $filenameTaskTemplate = 'task-template.xml';
    
    /**
     * contains a key for each imported meta data
     * @var array
     */
    protected $hasMetaData = array();

    /**
     * Erhält als Parameter die zu importierenden Sprachen
     * @param editor_Models_Languages $sourceLang
     * @param editor_Models_Languages $targetLang
     */
    public function __construct(editor_Models_Languages $sourceLang, editor_Models_Languages $targetLang){
        $this->sourceLang = $sourceLang;
        $this->targetLang = $targetLang;
    }

    /**
     * @return editor_Models_Languages
     */
    public function getSourceLang() {
        return $this->sourceLang;
    }
    /**
     * @return editor_Models_Languages
     */
    public function getTargetLang() {
        return $this->targetLang;
    }
    /**
     * @return string
     */
    public function getImportPath() {
        return $this->importPath;
    }
    
    /**
     * initiiert die Suche nach und dann den import von MetaDaten zum Projekt
     * @param editor_Models_Task $task
     * @param string $importPath
     */
    public function import(editor_Models_Task $task, string $importPath) {
        $this->task = $task;
        $this->importPath = $importPath;
        
        $this->importTaskTemplateXml();
        
        $events = ZfExtended_Factory::get('ZfExtended_EventManager', array(get_class($this)));
        /* @var $events ZfExtended_EventManager */
        $events->trigger('importMetaData', $this, array(
                'task' => $task, 'metaImporter' => $this
        ));
        
        //Meta Data import from Core Features, currently XML for MQM: 
        $this->addImporter(ZfExtended_Factory::get('editor_Models_Import_QmSubsegments'));
        
        foreach($this->importers as $importer) {
            /* @var $import editor_Models_Import_IMetaDataImporter */
            $importer->import($task, $this);
        }
    }

    /**
     * Gibt den Pfad der zu importiernden Meta-Dateien zurück (aktuell nur die erste)
     * Die Ergebnisse werden intern gecached
     * 
     * @param string $filterRegex a regex on which basis Metafile in the rootfolder of the importfolder are returned
     * @return [SplFileInfo] array aus SplFileInfo Objekten
     */
    public function getMetaFileToImport(string $filterRegex) {
        if(isset($this->cache[$filterRegex])){
            return $this->cache[$filterRegex];
        }
        $directory = new DirectoryIterator($this->importPath);
        $it = new RegexIterator($directory, $filterRegex);
        $list = array();
        foreach($it as $file){
            $list[$file->getFilename()] = clone $file;
        }
        if(empty($list)) {
            return $this->cache[$filterRegex] = array();
        }
        //natcasesort on array index:
        $result = array();
        $keys = array_keys($list);
        natcasesort($keys);
        foreach ($keys as $key){
          $result[$key] = $list[$key];
        }

        //aktuell soll nur die erste TBX verwendet werden, daher dieser Umweg:
        return $this->cache[$filterRegex] = array(reset($result));
    }

    /**
     * import task-template.xml file
     * if exist save it to Zend_Registry::get('taskTemplate');
     */
    protected function importTaskTemplateXml() {
        Zend_Registry::set('taskTemplate', array());
        $templateFilename = $this->importPath.'/'.$this->filenameTaskTemplate;
        
        if (file_exists($templateFilename)) {
            try {
                $config = new Zend_Config_Xml($templateFilename);
                Zend_Registry::set('taskTemplate', $config);
            }
            catch (Exception $e) {
                throw new Exception('.. invalid '.$this->filenameTaskTemplate.' detected at '.__CLASS__.' -> '.__FUNCTION__);
            }
        }
    }

     /**
     * adds a given importer to the internal importer list for further standardized processing
     * @param editor_Models_Import_IMetaDataImporter $importer
     */
    public function addImporter(editor_Models_Import_IMetaDataImporter $importer) {
        $this->importers[] = $importer;
    }

    /**
     * Räumt nach den abgeschlossenen Importvorgängen auf.
     */
    public function cleanup() {
        foreach($this->importers as $importer){
            $importer->cleanup();
        }
    }
}