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

namespace MittagQI\Translate5\TranslationMemory;

use editor_Models_Import_FileParser_XmlParser;
use ZfExtended_Exception;
use ZfExtended_Languages;

/**
 * Fileparser to collect source/target pairs out of TMX-files
 * By default, this class is tailored to extract textual segments out of translation-memories / TMX files
 * It will dismiss HTML-entities if tag-stripping is active (default)
 * Also, only segments that have at least two following characters are returned,
 * everything else is not regarded as textual
 */
final class SimpleTmxParser extends editor_Models_Import_FileParser_XmlParser {

    /*

    A TMX file generally looks like this

    <?xml version="1.0" encoding="UTF-8" ?>

    <tmx version="1.4">
      <header creationtoolversion="1.5.1.1" segtype="sentence" adminlang="en-us" srclang="en-US" o-tmf="OpenTM2" creationtool="OpenTM2" datatype="plaintext" />
      <body>

        <tu tuid="16" creationdate="20200819T081431Z">
          <prop type="tmgr:segNum">0</prop>
          <prop type="tmgr:markup">OTMXUXLF</prop>
          <prop type="tmgr:docname">Contact---22-posts_275597.html</prop>
          <tuv xml:lang="en-US">
            <prop type="tmgr:language">English(U.S.)</prop>
            <seg><x id="1"/><g id="2">Test for free for 30 days !</g><x id="4"/><x id="5"/><x id="6"/><x id="7"/><x id="8"/><x id="9"/><x id="10"/></seg>
          </tuv>
          <tuv xml:lang="de-DE">
            <prop type="tmgr:language">GERMAN(REFORM)</prop>
            <seg><x id="1"/>30 Tage lang kostenlos testen!<g id="2"></g><x id="4"/><x id="5"/> <x id="6"/><x id="7"/><x id="8"/><x id="9"/><x id="10"/></seg>
          </tuv>
        </tu>

        ...

       </body>
    </tmx>
     */

    private array $segments;

    private int $numSegments;

    private string $sourceLang;

    private string $targetLang;

    private ?string $primaryTargetLang;

    private ?string $primarySourceLang;

    private bool $stripTags;

    private bool $fuzzyTargets;

    private bool $singleTargetsNoArray = true;

    private array $currentVariants;

    private ?string $currentLang;

    /**
     * Extracts the segments for the given languages out of the given absolute xml-path
     * Returns, if at least one segment could be extracted
     * @param string $tmxFilePath
     * @param string $sourceLanguageCode
     * @param string $targetLanguageCode
     * @param bool $stripXliffTags
     * @param bool $fuzzyTargetMatching
     * @return bool
     * @throws ZfExtended_Exception
     */
    public function extractFile(
        string $tmxFilePath,
        string $sourceLanguageCode,
        string $targetLanguageCode,
        bool $stripXliffTags = true,
        bool $fuzzyTargetMatching = false,
    ): bool
    {
        // load file
        $xmlString = @file_get_contents($tmxFilePath);
        if(!$xmlString){
            throw new ZfExtended_Exception('SimpleTmxParser: Could not get contents of file '.basename($tmxFilePath));
        }
        return $this->extract(
            $xmlString,
            $sourceLanguageCode,
            $targetLanguageCode,
            $stripXliffTags,
            $fuzzyTargetMatching
        );
    }
    /**
     * Extracts the segments for the given languages out of the given xml-string
     * Returns, if at least one segment could be extracted
     * @param string $xmlString
     * @param string $sourceLanguageCode
     * @param string $targetLanguageCode
     * @param bool $stripXliffTags
     * @param bool $fuzzyTargetMatching
     * @return bool
     */
    public function extract(
        string $xmlString,
        string $sourceLanguageCode,
        string $targetLanguageCode,
        bool $stripXliffTags = true,
        bool $fuzzyTargetMatching = false,
    ): bool
    {
        $this->sourceLang = $sourceLanguageCode;
        $this->targetLang = $targetLanguageCode;
        $this->stripTags = $stripXliffTags;
        // primary languages
        $primarySource = ZfExtended_Languages::primaryCodeByRfc5646($this->sourceLang);
        $this->primarySourceLang = ($primarySource === $this->sourceLang) ? null : $primarySource;
        $primaryTarget = ZfExtended_Languages::primaryCodeByRfc5646($this->targetLang);
        $this->primaryTargetLang = ($primaryTarget === $this->targetLang) ? null : $primaryTarget;
        $this->fuzzyTargets = $fuzzyTargetMatching;
        $this->segments = [];
        $this->numSegments = 0;

        // register the needed parsers
        $this->registerElement('tmx tu', [$this, 'startTransUnit'], [$this, 'endTransUnit']);
        $this->registerElement('tmx tu > tuv', [$this, 'startTransUnitVariant']);
        $this->registerElement('tmx tu > tuv > seg', null, [$this, 'endTransUnitVariantSegment']);

        // parse the XML
        $this->parse($xmlString);

        return ($this->numSegments > 0);
    }

    /**
     * Retrieves a 2-dimensional array of segments
     * The 2nd dimension is also numerical, the first element is the source as string,
     * the second element is an array with at least one entry when fuzzy-search was set, or otherwise also a string
     * @return array
     */
    public function getSegments(): array
    {
        return $this->segments;
    }

    /**
     * @return int
     */
    public function countSegments(): int
    {
        return $this->numSegments;
    }

    /**
     * Handler when trans-unit starts
     * @param string $tag
     * @param array $attributes
     * @param int $key
     * @param bool $isSingle
     * @return void
     */
    public function startTransUnit(string $tag, array $attributes, int $key, bool $isSingle): void
    {

        $this->currentVariants = [];
        $this->currentLang = null;
    }
    /**
     * Handler sets the collected target content to the evaluated target-node (if the target-node was no single node)
     * @param string $tag
     * @param int $key
     * @param array $opener
     * @return void
     */
    public function endTransUnit(string $tag, int $key, array $opener): void
    {
        $source = null;
        $target = [];

        foreach($this->currentVariants as $lang => $content){
            if($this->languageMatches($lang, true)){
                $source = $content;
            } else if($this->languageMatches($lang, false)){
                $target[$lang] = $content;
            }
        }

        $numTargets = count($target);
        if(!empty($source) && $numTargets > 0){
            if(!$this->fuzzyTargets || ($this->singleTargetsNoArray && $numTargets === 1)){
                $this->segments[] = [$source, reset($target)];
            } else {
                $this->segments[] = [$source, $target];
            }
            $this->numSegments++;
        }
    }

    private function languageMatches(string $lang, bool $isSource): bool
    {
        $primary = substr($lang, 0,2);
        if($isSource){
            return $lang === $this->sourceLang ||
                ($this->fuzzyTargets && ($primary === $this->sourceLang || $primary === $this->primarySourceLang));
        }
        return $lang === $this->targetLang ||
            ($this->fuzzyTargets && ($primary === $this->targetLang || $primary === $this->primaryTargetLang));
    }

    /**
     * Starts a variant: gets the language from attributes
     * @param string $tag
     * @param array $attributes
     * @param int $key
     * @param bool $isSingle
     * @return void
     */
    public function startTransUnitVariant(string $tag, array $attributes, int $key, bool $isSingle): void
    {
        $this->currentLang = array_key_exists('xml:lang', $attributes) ? $attributes['xml:lang'] : null;
    }

    /**
     * Ends a variant-segment: extracts the segment-content
     * @param string $tag
     * @param int $key
     * @param array $opener
     * @return void
     */
    public function endTransUnitVariantSegment(string $tag, int $key, array $opener): void
    {
        $content = $this->prepareContent(
            $this->getRange($opener['openerKey'] + 1, $key - 1, true)
        );
        if($this->currentLang !== null && strlen($content) > 0){
            $this->currentVariants[$this->currentLang] = $content;
        }
        $this->currentLang = null;
    }

    /**
     * Prepares a segment
     * @param string $content
     * @return string
     */
    private function prepareContent(string $content): string
    {
        if($this->stripTags){
            // some TMs have lots of escaped contents, makes no sense for textual contents
            $content = html_entity_decode($content, ENT_QUOTES);
            $content = strip_tags($content);
        }
        $content = trim($content);

        // use only matches, that have a least two following "real" characters
        if(preg_match('/\pL\pL/u', $content) === 1){
            return $content;
        }
        return '';
    }
}