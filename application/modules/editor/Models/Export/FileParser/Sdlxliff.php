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

/* * #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

  /**
 * Parsed mit editor_Models_Import_FileParser_Sdlxliff geparste Dateien für den Export
 */

class editor_Models_Export_FileParser_Sdlxliff extends editor_Models_Export_FileParser {
    /**
     * @var string Klassenname des Difftaggers
     */
    protected $_classNameDifftagger = 'editor_Models_Export_DiffTagger_Sdlxliff';

    public function __construct(integer $fileId, boolean $diff,editor_Models_Task $task) {
        parent::__construct($fileId, $diff,$task);
    }

    /**
     * Rekonstruiert in einem Segment die ursprüngliche Form der enthaltenen Tags
     *
     * Erwartet werden Tags von der Struktur wie der folgende im Segment:
     * <div class="single g"><span title="&lt;footnotereference
     * style=&quot;Footnote Reference&quot; autonumber=&quot;1&quot;/&gt;"
     * class="short">&lt;1/&gt;</span><span id="ph14-5-5f55ad4870140e4d3e594e0f83870083"
     *  class="full">&lt;footnotereference style=&quot;Footnote Reference&quot;
     * autonumber=&quot;1&quot;/&gt;</span></div>
     *
     * @param string $segment
     * @throws Zend_Exception 'Der Tagtyp '.$segment[$i].' ist nicht definiert.'
     * @return string $segment
     */
    protected function parseSegment($segment) {
        //Baut einen einzelnen Tag in seine Ursprungsform zurück
        //die folgende Form besteht nur noch, weil vor dem 31.02.2013 importierte 
        //Projekte noch nicht den gesamten tagContent als CSS-Klasse verpackt mitgegeben 
        //hatten, sondern je nach Tagart ggf. nur ausgesuchte Teile. Dies ist seit dem anders.
        //Daher sollten alle Betandsprojekte entfernt sein, kann diese parseSegment durch die nachfolgend auskommentierte ersetzt werden.
        $rebuildTag = function ($tagType, $tagId, $toPack) {
                    try {
                        $tagContent = pack('H*', $toPack);
                    } catch (Exception $exc) {
                        $tagContent = $toPack;
                    }
                    if ($tagType === 'open') {
                        if($tagContent === 'g') return '<' . $tagContent . ' id="' . $tagId . '">';
                        return  '<' . $tagContent .'>';
                    }
                    if ($tagType === 'close') {
                        if($tagContent === 'g') return '</' . $tagContent . '>';
                        return  '<' . $tagContent .'>';
                    }
                    if ($tagType === 'single') {
                        if ($tagId == 'mrk' || $tagId == 'unicodePrivateUseArea'|| $tagId == "br"|| $tagId == "space"){
                            if(preg_match('"^mrk"', $tagContent)==0 && 
                                    preg_match('"^unicodePrivateUseArea"', $tagContent)==0 &&
                                    preg_match('"^br"', $tagContent)==0 &&
                                    preg_match('"^space"', $tagContent)==0
                                    ){
                                return '<' . $tagId . $tagContent . '>';
                            }
                            return  '<' . $tagContent .'>';
                        }
                            
                        if (strpos($tagId, 'locked')!== false){
                            if(preg_match('"^x"', $tagContent)==0)return '<x' .$tagContent . '>';
                            return  '<' . $tagContent .'>';
                        }
                        if($tagContent === 'g' or $tagContent === 'x') return '<' . $tagContent . ' id="' . $tagId . '" />';
                        return  '<' . $tagContent .'>';
                    }
                    throw new Zend_Exception('Der Tagtyp ' . $tagType . ' ist nicht definiert.');
                };
/*@todo nächste Zeile rauswerfen, wenn qm-subsegments im Export korrekt abgebildet werden. Das gleiche gilt für den vermerk in tasks.phtml */
        $segment = preg_replace('"<img[^>]*>"','', $segment);
        $segmentArr = preg_split($this->config->runtimeOptions->editor->export->regexInternalTags, $segment, NULL, PREG_SPLIT_DELIM_CAPTURE);
        $count = count($segmentArr);
        for ($i = 1; $i < $count;) {
            $j = $i + 2;
            //$segmentArr[$i] = '<' . pack('H*', $segmentArr[$i + 1]) .'">';
            $segmentArr[$i] = $rebuildTag($segmentArr[$i], $segmentArr[$j], $segmentArr[$i + 1]);
            unset($segmentArr[$j]);
            unset($segmentArr[$i + 1]);
            $i = $i + 4;
        }
        return implode('', $segmentArr);
    }
    
    /**
     * @todo sobald keine Altdaten mehr in der DB sind, die vor dem 31.01.2013 importiert wurden,
     * kann die obige parseSegment Methode durch die folgende komplett ersetzt werden!
     *  
    protected function parseSegment($segment) {
        //@todo nächste Zeile rauswerfen, wenn qm-subsegments im Export korrekt abgebildet werden. Das gleiche gilt für den vermerk in tasks.phtml 
        $segment = preg_replace('"<img[^>]*>"','', $segment);
        return parent::parseSegment($segment);
    }
     */

    /**
     * Stellt die Term Auszeichnungen gemäß sdlxliff-Syntax (<mrk ...) wieder her
     * 
     * Konvertiert von:
     * <div class="term admittedTerm transNotFound" id="term_05_1_de_1_00010-0" title="">Hause</div>
     * nach:
     * <mrk mtype="x-term-admittedTerm" mid="term_05_1_de_1_00010">Hause</mrk>
     * entfernt dabei Informationen zu trans[Not]Found
     * 
     * @param string $segment
     * @param boolean $removeTermTags, default = true
     * @return string $segment
     */
    protected function recreateTermTags($segment, $removeTermTags=true) {
        $segmentArr = preg_split('/<div\s*class="term([^"]+)"\s+id="([^"]+)-\d+"[^>]*>/s', $segment, NULL, PREG_SPLIT_DELIM_CAPTURE);
        
        $cssClassFilter = function($input) {
            return($input !== 'transFound' && $input !== 'transNotFound');
        };
        
        $count = count($segmentArr);
        $closingTag =  '</mrk>';
        if($removeTermTags){
            $closingTag = '';
        }
        for ($i = 1; $i < $count; $i = $i + 3) {
            $tagExpl/* segment aufgespalten an den öffenden Tags */ = explode('<div', $segmentArr[$i + 2]/* segmentteil hinter öffnendem Termtag */);
            $openTagCount = 0;
            $tCount = count($tagExpl);
            for ($j = 0; $j < $tCount; $j++) {
                $numOfClosedDiv = substr_count($tagExpl[$j], '</div>');
                $containsOpeningTag = preg_match('"^ class=\""', $tagExpl[$j]) === 1 || false;
                if ($openTagCount === 0 and
                        (
                        ($containsOpeningTag === true and $numOfClosedDiv > 1)
                        or
                        ($containsOpeningTag === false and $numOfClosedDiv === 1))) {
                    $parts = explode('</div>', $tagExpl[$j]); //der letzte </div> muss der schließende mrk-Tag sein, da ja kein div-Tag innerhalb des Term-Tags mehr geöffnet ist
                    $end = array_pop($parts);
                    $tagExpl[$j] = implode('</div>', $parts) . $closingTag. $end;
                    break; //go to the next termtag, because this one is now closed.
                } elseif (($containsOpeningTag === true and $numOfClosedDiv > 1)
                        or
                        ($containsOpeningTag === false and $numOfClosedDiv === 1)) {
                    $openTagCount--;
                } elseif ($containsOpeningTag and $numOfClosedDiv === 0) {
                    $openTagCount++;
                }
            }
            if(!$removeTermTags){
                $cssClasses = explode(' ', trim($segmentArr[$i]));
                //@todo actually were removing the trans[Not]Found info. 
                //it would be better to set it for source segments by checking the target if the term exists  
                $segmentArr[$i] = join('-', array_filter($cssClasses, $cssClassFilter));
                $segmentArr[$i] = '<mrk mtype="x-term-' . $segmentArr[$i] . '" mid="' . $segmentArr[$i + 1] . '">';
            }
            else{
                $segmentArr[$i] = '';
            }
            $segmentArr[$i] .= implode('<div', $tagExpl);
            unset($segmentArr[$i + 1]);
            unset($segmentArr[$i + 2]);
        }
        return implode('', $segmentArr);
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
