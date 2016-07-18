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

/** #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */

/**
 * Parsed mit editor_Models_Import_FileParser_Sdlxliff geparste Dateien für den Export
 */
class editor_Models_Export_FileParser_Sdlxliff extends editor_Models_Export_FileParser {
    /**
     * @var string Klassenname des Difftaggers
     */
    protected $_classNameDifftagger = 'editor_Models_Export_DiffTagger_Sdlxliff';

    public function __construct(integer $fileId, boolean $diff,editor_Models_Task $task,string $path) {
        parent::__construct($fileId, $diff,$task,$path);
    }

    /**
     * Rekonstruiert in einem Segment die ursprüngliche Form der enthaltenen Tags
     *  
     */
    protected function parseSegment($segment) {
        //@todo nächste Zeile rauswerfen, wenn qm-subsegments im Export korrekt abgebildet werden. Das gleiche gilt für den vermerk in tasks.phtml 
        $segment = preg_replace('"<img[^>]*>"','', $segment);
        return parent::parseSegment($segment);
    }

    /**
     * Gibt eine zu exportierende Datei bereits korrekt für den Export geparsed zurück
     *
     * @return string file
     */
    public function getFile() {
        parent::getFile();
        $this->unProtectUnicodeSpecialChars();
        $this->_exportFile = preg_replace('"(<mrk[^>]*[^/])></mrk>"i', '\\1/>', $this->_exportFile);
        $this->injectRevisions();
        return $this->_exportFile;
    }

    /**
     * Generiert die Revisionshistorie für den head der sdlxliff-Datei
     * Beispiel einer Revision: <rev-def id="b37e487f-2c70-4259-84e0-677d8c01f5b8" type="Delete" author="christine.schulze" date="10/23/2012 10:25:04" />
     * @return string
     */
    protected function generateRevisions() {
        $createRevision = function ($rev, $tagType = NULL) {
                    $delete = '';
                    if ($tagType == 'delete') {
                        $delete = ' type="Delete"';
                    }
                    return '<rev-def id="' . $rev['guid'] . '"' . $delete . ' author="' .
                            $rev['username'] . '" date="' . date('m/d/Y H:i:s', strtotime($rev['timestamp'])) . '" />';
                };
        $revisions = "";
        foreach ($this->_diffTagger->_additions as $rev) {
            $revisions .= $createRevision($rev);
        }
        foreach ($this->_diffTagger->_deletions as $rev) {
            $revisions .= $createRevision($rev, 'delete');
        }
        return $revisions;
    }

    /**
     * Injiziert die Revisionshistorie in den head der sdlxliff-Datei
     */
    protected function injectRevisions() {
        $revisions = $this->generateRevisions();
        if ($revisions != '') {
            if (strpos($this->_exportFile, '</rev-defs>')!== false) {
                $this->_exportFile = str_replace('</rev-defs>', $revisions . '</rev-defs>', $this->_exportFile);
            } elseif (strpos($this->_exportFile, '</doc-info>')!== false) {
                $this->_exportFile = str_replace('</doc-info>', '<rev-defs>' . $revisions . '</rev-defs></doc-info>', $this->_exportFile);
            }
            else {
                $this->_exportFile = 
                        preg_replace('"(<xliff[^>]*xmlns:sdl=\")([^\"]*)(\"[^>]*>)"',
                                '\\1\\2\\3<doc-info xmlns="\\2"><rev-defs>' . 
                                $revisions . '</rev-defs></doc-info>', $this->_exportFile);
            }
        }
    }

    /**
     * Entschützt Zeichenketten, die im sdlxliff enthalten sind und mit
     * ImportController->protectUnicodeSpecialChars geschützt wurden
     */
    protected function unProtectUnicodeSpecialChars() {
        $this->_exportFile = preg_replace_callback('"<unicodePrivateUseArea ts=\"[A-Fa-f0-9]*\"/>"', function ($match) {
                    $r = preg_replace('"<unicodePrivateUseArea ts=\"([A-Fa-f0-9]*)\"/>"', "\\1", $match[0]);
                    return pack('H*', $r);
                }, $this->_exportFile);
    }
}
