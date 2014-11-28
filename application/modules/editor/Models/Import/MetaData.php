<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
    const META_TBX = 'tbx';
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
    public $tbxFilterRegex = '/\.tbx$/i';
    
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
     * intiiert die Suche nach und dann den import von MetaDaten zum Projekt
     * @param editor_Models_Task $task
     * @param string $importPath
     */
    public function import(editor_Models_Task $task, string $importPath) {
        $this->task = $task;
        $this->importPath = $importPath;

        $this->importTbx();
        $this->importQmFlagXmlFile();
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
     * imports the XML file with the defined QM subsegment types
     */
    protected function importQmFlagXmlFile() {
        $importer = ZfExtended_Factory::get('editor_Models_Import_QmSubsegments');
        /* @var $importer editor_Models_Import_QmSubsegments */
        $importer->importFromXml($this->task,$this->importPath);
        $this->importers[] = $importer;
    }

    /**
     * Importiert die übergebenen TBX Files
     * @todo Import mehrere TBX Dateien ist aktuell ungestestet!
     */
    protected function importTbx() {
        $tbxfiles = $this->getMetaFileToImport($this->tbxFilterRegex);
        if(empty($tbxfiles)){
            return;
        }
        $importer = ZfExtended_Factory::get('editor_Models_Import_TermListParser_Tbx');
        /* @var $importer editor_Models_Import_TermListParser_Tbx */
        foreach($tbxfiles as $file) {
            /* @var $file SplFileInfo */
            $importer->import($file, $this->task, $this->sourceLang, $this->targetLang);
            break; //we consider only one TBX file!
        }
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