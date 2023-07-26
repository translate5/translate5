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

namespace MittagQI\Translate5\Task\Import\FileParser\Xlf\Namespaces\Translate5;

use editor_Models_Import_FileParser_Xlf_ContentConverter;
use editor_Models_Import_FileParser_Xlf_Exception;

class ContentConverter extends editor_Models_Import_FileParser_Xlf_ContentConverter
{

    /**
     * @var array[]
     */
    protected array $tagMap = [];

    public function resetTagMap(): void
    {
        $this->tagMap = [];
    }

    public function setInTagMap(string $key, array $value): void
    {
        $this->tagMap[$key] = $value;
    }

    public function handleReplacerTag($tag, $key, $opener): void
    {
        //load tag from tagmap, if not found process as usual
        $fromTagMap = $this->getSingleTag($key);
        if (empty($fromTagMap)) {
            parent::handleReplacerTag($tag, $key, $opener);
        } else {
            $this->result[] = $fromTagMap[0];
        }
    }

    /**
     * Returns the Translate5 internal single tag to the given XLF single tag (<x>, <it> etc..)
     *   from the internal tagmap stored in translate5 XLF
     * @param int $key
     * @return array the internal tag to the given xlf single tag
     */
    private function getSingleTag(int $key): array
    {
        $xlfTag = $this->xmlparser->getChunk($key);
        //some foreign tools add spaces between the last attribute and the closing />
        $xlfTag = preg_replace('#"\s+/>$#', '"/>', $xlfTag);
        if (!empty($this->tagMap[$xlfTag])) {
            return $this->tagMap[$xlfTag];
        }
        //some tools convert <g> tag pair to just a self closing <g/> tag,
        // if we got no tagmap match we try to find a g tag without the slash then
        $xlfTag = preg_replace('#<g([^>]+)/>#', '<g$1>', $xlfTag);
        if (!empty($this->tagMap[$xlfTag])) {
            return $this->tagMap[$xlfTag];
        }
        return [];
    }

    /**
     * Handler for G tags
     * @param string $tag
     * @param array $attributes
     * @param int $key
     * @throws editor_Models_Import_FileParser_Xlf_Exception
     */
    public function handleGTagOpener(string $tag, array $attributes, int $key): void
    {
        //in the translate5 internal tag map everything is mapped by the opener only:
        $fromTagMap = $this->getSingleTag($key);
        if (empty($fromTagMap)) {
            parent::handleGTagOpener($tag, $attributes, $key);
        } else {
            $this->result[] = $fromTagMap[0];
        }
    }

    /**
     * Handler for G tags
     * @param string $tag
     * @param int $key
     * @param array $opener
     * @throws editor_Models_Import_FileParser_Xlf_Exception
     */
    public function handleGTagCloser(string $tag, int $key, array $opener): void
    {
        if ($opener['isSingle']) {
            return; // the tag was already handled in the opener
        }
        $openerKey = $opener['openerKey'];
        $fromTagMap = $this->getSingleTag($openerKey);
        if (empty($fromTagMap)) {
            parent::handleGTagCloser($tag, $key, $opener);
        } else {
            $this->result[] = $fromTagMap[1];
        }
    }
}