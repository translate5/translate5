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

declare(strict_types=1);

namespace MittagQI\Translate5\Tools\Tmx\ConvertFromAraya;

/**
 * See MITTAGQI-364 Convert tags in Araya-based TMX
 */
class SegParser
{
    /**
     * contains all chunk-entries as
     * id => content
     * entries in the array
     */
    protected array $entries = [];

    /**
     * container to hold the results of analyze as list of
     * editor_Services_T5Memory_Fixer_FixArayaTmxSegAnalyzeDTO objects
     * @var PlaceholderDTO[]
     */
    protected array $analyzeResults = [];

    /**
     * indicates that analyse has run already, and it will not be run twice
     */
    protected bool $analyseHasRun = false;

    public function addEntry(int $id, string $content): void
    {
        $this->entries[$id] = $content;
    }

    public function getEntries(): array
    {
        return $this->entries;
    }

    protected function analyzeEntries(): void
    {
        // first check if analyze did run already. this makes it possible to call this function as often as you want.
        if ($this->analyseHasRun) {
            return;
        }
        $this->analyseHasRun = true;

        // if there are no entries, nothing to do
        if (empty($this->entries)) {
            return;
        }

        // go to first entry
        reset($this->entries);

        // and now run through all entries
        while (($key = key($this->entries)) !== null) {
            $entry = current($this->entries);

            // if this entry is no placeholder tag, go to next entry
            if (! str_starts_with($entry, '<ph>') && ! str_starts_with($entry, '<ph ')) {
                next($this->entries);

                continue;
            }

            // here we found an opening placeholder-tag
            $tag = new PlaceholderDTO();
            $tag->startId = $key;
            $tag->phTag = $entry;
            // !! when reading out original tag content we already skipped to next entry
            $orgTag = html_entity_decode(next($this->entries));
            $tag->setOrgTag($orgTag);

            // store analyze result
            $this->analyzeResults[] = $tag;

            // and skip next entry, because this is the closing </ph> tag
            next($this->entries);
        }
    }

    /**
     * @return PlaceholderDTO[]
     */
    public function getAnalyzeResults(): array
    {
        $this->analyzeEntries();

        return $this->analyzeResults;
    }
}
