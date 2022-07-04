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
namespace Translate5\MaintenanceCli\L10n;

use editor_Models_Import_FileParser_XmlParser;
use SplFileInfo;

class XliffFile {

    private SplFileInfo $file;
    private editor_Models_Import_FileParser_XmlParser $xmlparser;
    private int $unitsModified = 0;

    public function __construct(SplFileInfo $file) {
        $this->file = $file;
        $this->xmlparser = \ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
    }

    /**
     * Adds a new transunit to the XLF file, returns the amount of added units (should be 1)
     * @param string $source
     * @param string|null $target
     * @param string|null $after
     * @return int
     */
    public function add(string $source, string $target = null, string $after = null): int {
        //add at the end
        if(is_null($after)) {
            $this->registerAddToEnd($source, $target);
        }
        else {
            $this->registerAddAfter($source, $target, $after);
        }
        $this->save();
        return $this->unitsModified;
    }

    /**
     * replaces trans-units identified by source content, returns the amount of changed units, should be 1 but replaces duplicates, then it could be > 1
     * @param string $oldSource
     * @param string $newSource
     * @param string|null $target
     * @return int
     */
    public function replace(string $oldSource, string $newSource, string $target = null): int {
        $this->registerAddAfter($newSource, $target, $oldSource, true);
        $this->save();
        if($this->unitsModified === 0) {
            $this->add($newSource, $target);
        }
        return $this->unitsModified;
    }

    /**
     * removes the trans-unit identified by source, could be multiple if multiple found. returns the count of removed.
     * @param string $source
     * @return int
     */
    public function remove(string $source): int {
        $this->registerAddAfter(null, null, $source, true);
        $this->save();
        return $this->unitsModified;
    }

    /**
     * parses the xlf and updates the file
     */
    private function save() {
        file_put_contents($this->file, $this->xmlparser->parse(file_get_contents($this->file)));
    }

    /**
     * generates a trans-unit
     * @param string $source
     * @param string|null $target
     * @return string
     */
    private function makeUnit(string $source, ?string $target): string
    {
        return '<trans-unit id="' . base64_encode($source) . '">
  <source>' . $source . '</source>
  <target>' . ($target ?? '') . '</target>
</trans-unit>';
    }

    /**
     * register the add to the end handlers
     * @param string $source
     * @param string|null $target
     */
    private function registerAddToEnd(string $source, ?string $target)
    {
        $this->xmlparser->registerElement('body', null, function($tag, $key) use ($source, $target) {
            $key = $key - 1;
            $prev = $this->xmlparser->getChunk($key);
            if(strlen(trim($prev)) === 0){
                //if previous node is whitespace only, we add the new content before the whitespace
                $this->xmlparser->replaceChunk($key, "\n".$this->makeUnit($source, $target).$prev);
            }
            else {
                //if it was another node, we add it after it
                $this->xmlparser->replaceChunk($key, $prev."\n".$this->makeUnit($source, $target));
            }
            $this->unitsModified++;
        });
    }

    /**
     * register the add after, replace and remove handlers
     * @param string|null $source
     * @param string|null $target
     * @param string $after
     * @param bool $replace
     */
    private function registerAddAfter(?string $source, ?string $target, string $after, bool $replace = false)
    {
        $found = false;
        $this->xmlparser->registerElement('trans-unit source', null, function($tag, $key, $opener) use ($after, &$found) {
            $sourceContent = $this->xmlparser->getRange($opener['openerKey'] + 1, $key - 1, true);
            if($after === $sourceContent) {
                $found = true;
            }
        });
        $this->xmlparser->registerElement('trans-unit', function() use (&$found){
            // on entering a trans-unit we reset the found flag
            $found = false;
        }, function($tag, $key, $opener) use ($source, $target, &$found, $replace) {
            if(!$found) {
                return;
            }
            $this->unitsModified++;
            if(! $replace) {
                //add after the matching source
                $this->xmlparser->replaceChunk($key, '</trans-unit>' . "\n" . $this->makeUnit($source, $target));
                return;
            }
            if(is_null($source) && is_null($target)) {
                //remove the matching source
                $this->xmlparser->replaceChunk($opener['openerKey'], '', $key - $opener['openerKey'] + 1);
                $prev = $this->xmlparser->getChunk($opener['openerKey'] - 1);
                //the prev chunk was only whitespace, remove it too
                if(strlen(trim($prev)) === 0){
                    $this->xmlparser->replaceChunk($opener['openerKey'] - 1, '');
                }
            }
            else {
                //replace the matching source
                $this->xmlparser->replaceChunk($opener['openerKey'] + 1, '', $key - $opener['openerKey']);
                $this->xmlparser->replaceChunk($opener['openerKey'], $this->makeUnit($source, $target));
            }
        });
    }
}
