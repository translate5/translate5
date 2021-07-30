<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Plugin Bootstrap for Segment Statistics Plugin
 */
class editor_Plugins_ArchiveTaskBeforeDelete_Archive {
    use editor_Plugins_ArchiveTaskBeforeDelete_TLogger;
    
    const TASK_STATE = 'archiving';
    
    /**
     * returns true if the archive was created, false otherwise
     * @param string $taskGuid
     * @throws Exception
     * @return boolean
     */
    public function createFor($taskGuid) {
        //
        //mit flock gedöhns die ausgabedatei sichern
        //
        $this->log("Start archiving for ".$taskGuid);
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        if(!$task->lock(NOW_ISO, self::TASK_STATE)) {
            return false;
        }
        $oldState = $task->getState();
        $task->setState(self::TASK_STATE);
        $task->save();
        
        try {
            $db = ZfExtended_Factory::get('editor_Plugins_ArchiveTaskBeforeDelete_Archiver_Database');
            /* @var $db editor_Plugins_ArchiveTaskBeforeDelete_Archiver_Database */
            $db->archive($task->getAbsoluteTaskDataPath(), $task);
            
            $data = ZfExtended_Factory::get('editor_Plugins_ArchiveTaskBeforeDelete_Archiver_DataDirectory');
            /* @var $data editor_Plugins_ArchiveTaskBeforeDelete_Archiver_DataDirectory */
            
            $config = Zend_Registry::get('config');
            $taskDirRoot = $config->runtimeOptions->dir->taskData.DIRECTORY_SEPARATOR;
            
            $name = $data->archive($taskDirRoot, $task);
            $this->log('Task Archive created: '.$name);
        }
        catch(Exception $exceptionOnArchiving) {
            //do nothing here, unlock task first
        }
        $task->unlock();
        $task->setState($oldState);
        $task->save();
        if(!empty($exceptionOnArchiving)) {
            throw $exceptionOnArchiving;
        }
        return true;
        /*
- Sicherstellung dass neue Tabellen ebenfalls archiviert werden:
  - Im Export Model alle Tabellen auflisten, mit zugehöriger Info ob archiviert ja oder nein. Wenn ja sind zusätzlich zum Tabellen Namen das where Statement und die single-transaction info wie im Beispiel oben verwendet angegeben.
  - Die hinterlegte Tabellenliste kann mit den real existierenden Tabellen abgeglichen werden um neue zu identifizieren. Dies sollte als eine Art Test im build / deploy Prozess verankert werden. Der Build schlägt dann fehl wenn der Entwickler Tabellen vergessen hat mit in die Liste mitaufzunehmen.

- Adaptieren der export Architektur für die Archivierung 	0,5d
- Auflistung aller Tabellen inkl. Parameter. 			0,5d
- Umsetzung Tabellen Test im Build Script 			0,5d
- Erzeugung der gewünschten Daten / Zip Struktur 
  und löschen des Tasks						1d
- Tabellen aufräumen (unbenutzte Löschen, fehlende Foreign Keys nachtragen (MQM Tabelle))
  → MQM Alle Einträge löschen zu denen es keine LEK_task mehr gibt, dann Foreign Key anlegen
- Testing / Fixing 						1d
/
 */ 
    }
}