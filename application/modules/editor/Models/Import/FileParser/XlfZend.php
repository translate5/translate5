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

use MittagQI\Translate5\Task\Import\FileParser\Xlf\NamespaceRegistry;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Namespaces\ZendXlf;

/**
 * Fileparsing for the Zend XLIFF files used for internal translation of translate5
 *
 * The name is just ZendXliff/ZendXlf to distinguish it between regular Xliff
 *  and the xliff used in our Zend based application.
 * This name should not provide any connection between Zend and Xliff in general, only in the context of translate5!
 */
class editor_Models_Import_FileParser_XlfZend extends editor_Models_Import_FileParser_Xlf
{
    //since we replace chunks in the XML the content converter has to reparse it
    public const XML_REPARSE_CONTENT = true;

    /**
     * Storing the original source chunks containing the original HTML and Mail Placeholders for the skeleton
     */
    protected array $originalSourceChunks = [];

    protected array $tagMaps;

    public function __construct(string $path, string $fileName, int $fileId, editor_Models_Task $task)
    {
        NamespaceRegistry::registerNamespace('zxliff', ZendXlf::class);
        parent::__construct($path, $fileName, $fileId, $task);
    }

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::getFileExtensions()
     */
    public static function getFileExtensions()
    {
        return ['xlfzend', 'zxliff'];
    }

    protected function calculateMid(array $opener, $source)
    {
        //our zend xliff uses just ids instead mids, so we generate them out of the very long ids:
        //override completly the mid calculation, since we dont use subs or mrks!
        $transUnit = $this->xmlparser->getParent('trans-unit');

        return $this->shortenMid($this->xmlparser->getAttribute($transUnit['attributes'], 'id'));
    }

    private function shortenMid(string $mid): string
    {
        return md5($mid);
    }

    /**
     * override to deal with the base64 long mids
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser::setMid()
     */
    protected function setMid($mid)
    {
        $mid = explode('_', $mid);
        //remove the segment count part from MID.
        // 1. Not needed since we don't have MRKs
        // 2. can not be used otherwise relais matching won't work since our de.xliff and en.xliff
        // are not aligned in segment position.
        // therefore there would be different MIDs for same content then.
        array_pop($mid);
        $mid = $this->shortenMid(join('_', $mid));
        parent::setMid($mid);
    }

    /**
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Models_ConfigException
     */
    protected function setTaskConfig(editor_Models_Task $task): void
    {
        // we need to force several import options that cannot be supported for Zend-XLFs
        $this->config = new Zend_Config($task->getConfig()->toArray(), true);
        // force preserve Whitespace
        $this->config->runtimeOptions->import->xlf->preserveWhitespace = true;
        // prevent remove framing tags as these would end up as real XLIFF-Tags in the skeleton ...
        $this->config->runtimeOptions->import->xlf->ignoreFramingTags = 'none';
        $this->config->setReadOnly();
    }

    protected function parse()
    {
        $this->_origFile = preg_replace("/id='([^']+)'/", "id=\"$1\"", $this->_origFile);

        parent::parse();
    }

    /**
     * Convert the internal HTML tags to <ph> tags
     * @param array<string, string> $transUnit
     * @return int[]
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws editor_Models_Import_FileParser_Exception
     * @throws editor_Models_Import_MetaData_Exception
     */
    protected function extractSegment($transUnit): array
    {
        $this->tagMaps = []; // will ensure sync of tag-ids in source/target
        foreach ($this->currentSource as $mid => $source) {
            $this->tagMaps[$mid] = [];
            $this->protectHtml($source, true, $mid);
        }

        foreach ($this->currentTarget as $mid => $target) {
            if (! array_key_exists($mid, $this->tagMaps)) {
                $this->tagMaps[$mid] = [];
            }
            $this->protectHtml($target, false, $mid);
        }

        $result = parent::extractSegment($transUnit);
        $this->unprotectHtmlInSource();

        return $result;
    }

    /**
     * protect the HTML content inside the trans unit as ph tag with content
     * @param array{opener:int, closer:int} $nodeInfo
     * @throws ReflectionException
     * @throws ZfExtended_Exception
     */
    private function protectHtml(array $nodeInfo, bool $isSource, string $mid): void
    {
        $j = 0;
        $start = $nodeInfo['opener'] + 1; // we have to exclude the <source>|<target> tags themselves
        $end = $nodeInfo['closer'] - 1;
        $parser = ZfExtended_Factory::get(editor_Models_Import_FileParser_XmlParser::class);
        // register other content to replace the mail template placeholders there
        $parser->registerOther(function ($other, $i) use (&$j, $start, $isSource, $mid) {
            $origKey = $start + $i;
            // mask {variables} as tags
            $other = preg_replace_callback(
                '#({[a-zA-Z0-9_-]+})#',
                function ($matches) use ($other, &$j, $origKey, $isSource, $mid) {
                    if ($isSource) {
                        $j++;
                        $this->tagMaps[$mid][$j] = $matches[1];
                        $this->originalSourceChunks[$origKey] = $other;
                    } else {
                        $j = $this->calculateTargetTagId($mid, $j, $matches[1]);
                    }

                    return '<ph id="' . $j . '">' . htmlspecialchars($matches[1]) . '</ph>';
                },
                $other
            );
            $this->xmlparser->replaceChunk($origKey, $other);
        });

        // register to all XML nodes to replace html tags, this is currently the only valid tag content
        // between <source></source> and target tags
        $parser->registerElement(
            '*',
            null,
            function ($tag, $key, $opener) use (&$j, $start, $parser, $isSource, $mid) {
                $origOpenerKey = $start + $opener['openerKey'];
                $origEndKey = $start + $key;
                $chunk = $parser->getChunk($opener['openerKey']);
                // calculate id of opener/single
                if ($isSource) {
                    $j++;
                    $this->tagMaps[$mid][$j] = $chunk;
                    $this->originalSourceChunks[$origOpenerKey] = $chunk;
                } else {
                    $j = $this->calculateTargetTagId($mid, $j, $chunk);
                }

                if ($opener['isSingle']) {
                    $chunk = '<ph id="' . $j . '">' . htmlspecialchars($chunk) . '</ph>';
                } else {
                    $chunk = '<bpt id="' . $j . '" rid="' . $j . '">' . htmlspecialchars($chunk) . '</bpt>';
                }
                $this->xmlparser->replaceChunk($origOpenerKey, $chunk);

                // we have a closer as well
                if (! $opener['isSingle']) {
                    $rid = $j; // the RID is the id of the opener
                    $chunk = $parser->getChunk($key);
                    // calculate id of closer
                    if ($isSource) {
                        $j++;
                        $this->tagMaps[$mid][$j] = $chunk;
                        $this->originalSourceChunks[$origEndKey] = $chunk;
                    } else {
                        $j = $this->calculateTargetTagId($mid, $j, $chunk);
                    }
                    $chunk = '<ept rid="' . $rid . '" id="' . $j . '">' . htmlspecialchars($chunk) . '</ept>';
                    $this->xmlparser->replaceChunk($origEndKey, $chunk);
                }
            }
        );
        $parser->parseList($this->xmlparser->getRange($start, $end));
    }

    /**
     * Calculates the tag-id for a tag in the target segment
     */
    private function calculateTargetTagId(string $mid, int $currentIndex, string $currentHash): int
    {
        $idx = 0;
        foreach ($this->tagMaps[$mid] as $idx => $hash) {
            if ($hash === $currentHash) {
                $this->tagMaps[$mid][$idx] = null; // invalidate entry so it cannot be reused

                return $idx;
            }
        }

        // when we could not find the tag in the source-tags, we have to make sure, to use a non-used tag-id
        // we achieve that by creating one bigger than the existing biggest $idx
        if ($currentIndex > $idx) {
            return $currentIndex + 1;
        }

        return $idx + 1;
    }

    /**
     * Before source is stored in skeleton, we have to unprotect the HTML tags,
     * since we need the original content in the skeleton
     */
    private function unprotectHtmlInSource(): void
    {
        foreach ($this->originalSourceChunks as $i => $chunk) {
            $this->xmlparser->replaceChunk($i, $chunk);
        }
    }

    /**
     * Just override this check to check nothing, since the zend xliff is not valid xliff here
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser_Xlf::checkXliffVersion()
     */
    protected function checkXliffVersion($attributes, $key)
    {
    }
}
