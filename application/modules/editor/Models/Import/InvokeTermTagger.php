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
 * Enthält Methoden zum Fileparsing für den Import
 *
 */

class editor_Models_Import_InvokeTermTagger {

  /**
   * filenames with these extensions will be taggged (without leading dot)
   * - caseInsensitiv
   * - all others will be ignored
   * @var array
   */
  protected $taggedExtensionList = array('sdlxliff');
  
  protected $libDir;
    protected $classPath;
    protected $java;
    protected $debug;
    protected $stemmed;
        
    const LANG_TYPE_RFC5646 = 'termTagFileList.txt';
    /**
     * path at which the filelist for the termtagger is saved during import time
     * @var string filepath
     */
    protected $termTagFileList;
    /**
     * @var string import folder, under which the to be imported folder and file hierarchy resides
     */
    protected $importFolder;
    /**
     *
     * @var type array
     */
    protected $filePaths;
    /**
     *
     * @var type editor_Models_Import_MetaData
     */
    protected $metaDataImporter;
    /**
     *
     * @var type boolean
     */
    protected $fuzzy;
    protected $fuzzyPercent;
    protected $lowercase;
    protected $parameter = '-Xmx1024M';

    public function __construct(array $filePaths = NULL, $importFolder = NULL, editor_Models_Import_MetaData $metaDataImporter = NULL) {
        $this->metaDataImporter = $metaDataImporter;
        $this->filePaths = $filePaths;
        $this->importFolder = $importFolder;
        $config = Zend_Registry::get('config');

        $this->java = $config->runtimeOptions->termTagger->javaExec;
        $this->libDir = $config->runtimeOptions->termTagger->dir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR;
        $this->debug = $config->runtimeOptions->termTagger->debug;
        $this->stemmed = $config->runtimeOptions->termTagger->stemmed;
        $this->fuzzy = ($config->runtimeOptions->termTagger->fuzzy === 'true');
        $this->fuzzyPercent = (int)$config->runtimeOptions->termTagger->fuzzyPercent;
        $this->lowercase = $config->runtimeOptions->termTagger->lowercase;
        $this->maxWordLengthSearch = (int)$config->runtimeOptions->termTagger->maxWordLengthSearch;
        $this->minFuzzyStringLength = (int)$config->runtimeOptions->termTagger->minFuzzyStringLength;
        $this->minFuzzyStartLength = (int)$config->runtimeOptions->termTagger->minFuzzyStartLength;
        $cp = array('.', $this->libDir.'openTMS.jar', $this->libDir.'external.jar', '');
        $this->classPath = join(PATH_SEPARATOR, $cp);
    }

    public function tagTbx($source, $target){
        $cmd = array($this->java);
        $cmd[] = '-cp';
        $cmd[] = '"'.$this->classPath.'"';
        $cmd[] = $this->parameter;
        $cmd[] = 'de.folt.models.documentmodel.tbx.TbxDocument';
        $cmd[] = $source;
        $cmd[] = $target;
        if(strtolower(PHP_OS) == 'linux') {
          $cmd[] = '2>&1';
        }
        $out = $this->invoke($cmd);
        if(strpos($out, 'error code: 0')=== false){
          trigger_error('Die Rückgabe des Tools, welches der TBX IDs hinzufügt enthielt einen Fehler. Der übergebene Command sah wie folgt aus:<br/> '.
              join(' ', $cmd) .'<br/><br/>   Der Output des Tools sah wie folgt aus: <br/>'.$out.'<br/><br/>', E_USER_ERROR);
        }
    }

    public function tagXliff( $dataSource, $sourceLang, $targetLang){
        $cmd = array($this->java);
        $cmd[] = '-cp';
        $cmd[] = '"'.$this->classPath.'"';
        $cmd[] = $this->parameter;
        $cmd[] = 'de.folt.models.applicationmodel.termtagger.XliffTermTagger';
        $cmd[] = '-xliffFileList';
        $cmd[] = $this->termTagFileList; //$this->importFolder.DIRECTORY_SEPARATOR.self::LANG_TYPE_RFC5646;
        $cmd[] = '-dataSource';
        $cmd[] = $dataSource;
        $cmd[] = '-sourceLanguage';
        $cmd[] = $sourceLang;
        $cmd[] = '-targetLanguage';
        $cmd[] = $targetLang;
        $cmd[] = '-debug';
        $cmd[] = $this->debug;
        $cmd[] = '-stemmed';
        $cmd[] = $this->stemmed;
        if($this->fuzzy){
            $cmd[] = '-fuzzyPercent';
            $cmd[] = $this->fuzzyPercent;
         //   $cmd[] = '-maxWordLengthSearch';
           // $cmd[] = $this->maxWordLengthSearch;
           // $cmd[] = '-minFuzzyStringLength';
           // $cmd[] = $this->minFuzzyStringLength;
           // $cmd[] = '-minFuzzyStartLength';
           // $cmd[] = $this->minFuzzyStartLength;
        }
        $cmd[] = '-lowercase';
        $cmd[] = $this->lowercase;

        if(strtolower(PHP_OS) == 'linux') {
          $cmd[] = '2>&1';
        }
        $out = $this->invoke($cmd);

        if(strpos($out, 'error')!== false){
            trigger_error('Die Rückgabe des TermTaggers enthielt einen Fehler. Der übergebene Command war: '.
                    join(' ', $cmd).' Ggf. könnte es für die Analyse helfen, den Parameter runtimeOptions.termTagger.debug in der application.ini des Editors auf "true" zu stellen. Der Output des Taggers sah wie folgt aus: '.$out, E_USER_ERROR);
        }
    }
    
    /**
     * removes already existent termTags before the parsing; each parsed file-format
     * needs to be implemented here
     * 
     */
    public function removeTermTags() {
        $files = file($this->termTagFileList );
        foreach ($files as $file) {
            $file = trim($file);
            if(!file_exists($file))
                continue;
            foreach ($this->taggedExtensionList as $format){
                $f = file_get_contents($file);
                $funcName = 'remove'.ucfirst(strtolower($format)).'TermTags';
                $f = $this->$funcName($f);
                if(file_put_contents($file, $f)===false){
                    trigger_error('File '.$file.
                            ' with termTags removed has not been saved.', E_USER_ERROR);
               }
            }
        }
    }
    /**
     * removes already existent termTags according to xliff-translate5-convention
     * 
     * @param string $f file
     * @return string $f file
     */
    protected function removeSdlxliffTermTags(string $f) {
        if(strpos($f, 'mtype="x-term')===false)
                return $f;
        $f = preg_split('"(<mrk[^>]* mtype=\"seg\"[^<>]*>)"', $f, NULL, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $fCount = count($f);
        for($k=2;$k<$fCount;) {
            $segment = preg_split('"<mrk[^>]* mtype=\"x-term[^<>]*>"', $f[$k]);
            $sCount = count($segment);
            for($l=1;$l<$sCount;$l++){
                $segment[$l] = explode('</mrk>', $segment[$l]);
                $sPartCount = count($segment[$l]);
                $mrkOpenCount = 0;
                for($i=0;$i<$sPartCount;$i++) {
                    //keep an eye on other mrk-Tags, which might be part of the file
                    $c = count(preg_split('"<mrk[^>]*[^/]>"', $segment[$l][$i]));
                    $mrkOpenCount = $c -1 +$mrkOpenCount;
                    if($mrkOpenCount === 0){
                        break;
                    }
                    $mrkOpenCount--;
                }
                if(!isset($segment[$l][$i+1])){
                    trigger_error('index '.$i.
                         ' +1 is not set in segment '.$l, E_USER_ERROR);
                }
                $segment[$l][$i] .= $segment[$l][$i+1];
                unset($segment[$l][$i+1]);
                $segment[$l] = implode('</mrk>', $segment[$l]);
            }
            $f[$k] = implode('', $segment);
            $k++;
            $k++;
        }
        return implode('', $f);
    }
    protected function invoke(array $cmd) {
        return shell_exec(join(' ', $cmd));
    }
    /**
     * parses all files in the import dir to the termTagger
     *
     * - first removes 
     * - Caution: at the moment the termTagger only parses sdlxliff
     */
    public function termTagFiles(editor_Models_Languages $sourceLang,editor_Models_Languages $targetLang){
        $tbxFiles = $this->metaDataImporter->getMetaFileToImport($this->metaDataImporter->tbxFilterRegex);
        /* @var $tbxFile SplFileInfo */
        $tbxFile = reset($tbxFiles);
        
        $this->saveTermTagFileList();
        
        $this->removeTermTags($this->termTagFileList);

        if($tbxFile && $tbxFile->isReadable()){
            $this->tagXliff($tbxFile->getPathname(), $sourceLang->getRfc5646(), $targetLang->getRfc5646());
            $this->renameTermTagFiles();
        }
        $this->deleteTermTagFileList();
    }

    /**
     * speichert im Importverzeichnis eine txt-Datei mit den Pfaden aller Dateien für den termTagger
     *
     */
    public function saveTermTagFileList(){
        $list = '';
        foreach ($this->filePaths as $fileId => $path) {
            foreach ($this->taggedExtensionList as $format){
                if(preg_match('"\.'.$format.'$"i', $path)===1){
                    $list .= $this->importFolder.DIRECTORY_SEPARATOR.$path."\n";
                }
            }
        }
        $this->termTagFileList = $this->importFolder.DIRECTORY_SEPARATOR.self::LANG_TYPE_RFC5646;
        file_put_contents($this->termTagFileList, trim($list));
    }
    /**
     * benennt Dateien nach dem Tagging um
     *
     * - der termTagger exportiert sein Ergebnis in eine neue Datei mit dem Namen alteDatei.sdlxliff.xlf
     * - demzufolge: Umbenennung alteDatei.sdlxliff => alteDatei.sdlxliff.untagged
     * - und: Umbenennung alteDatei.sdlxliff.xlf => alteDatei.sdlxliff
     *
     */
    protected function renameTermTagFiles(){
        $localEncoded = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper('LocalEncoded');
        $files = file($this->termTagFileList);
        foreach ($files as $file) {
            $file = $localEncoded->encode(trim($file));
            rename($file,$file.'.untagged');
            rename($file.'.xlf',$file);
        }
    }
    /**
     * löscht im Importverzeichnis die txt-Datei mit den Pfaden aller Dateien für den termTagger
     *
     */
    public function deleteTermTagFileList(){
        unlink($this->termTagFileList);
    }
}
