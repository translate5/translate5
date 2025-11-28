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

namespace MittagQI\Translate5\ContentProtection\T5memory;

use editor_Models_Segment_Whitespace as Whitespace;
use MittagQI\Translate5\ContentProtection\ContentProtector;
use MittagQI\Translate5\ContentProtection\NumberProtector;
use MittagQI\Translate5\Segment\EntityHandlingMode;

class ConvertT5MemoryTagService implements ConvertT5MemoryTagServiceInterface
{
    public function __construct(
        private readonly ContentProtector $contentProtector,
        private readonly \ZfExtended_Logger $logger,
    ) {
    }

    public static function create(?Whitespace $whitespace = null)
    {
        return new self(
            ContentProtector::create($whitespace ?: \ZfExtended_Factory::get(Whitespace::class)),
            \Zend_Registry::get('logger')->cloneMe('editor.content_protection'),
        );
    }

    public function convertT5MemoryTagToContent(string $string): string
    {
        preg_match_all(T5NTag::fullTagRegex(), $string, $tags, PREG_SET_ORDER);

        if (empty($tags)) {
            return $string;
        }

        foreach ($tags as $tagWithProps) {
            $tag = $tagWithProps[0];
            // make sure not escape already escaped HTML entities
            $protectedContent = $this->protectHtmlEntities(T5NTag::fromMatch($tagWithProps)->content);

            $string = str_replace($tag, htmlentities($protectedContent, ENT_XML1), $string);
        }

        return $this->unprotectHtmlEntities($string);
    }

    public function convertContentTagToT5MemoryTag(string $queryString, bool $isSource, &$numberTagMap = []): string
    {
        $queryString = $this->contentProtector->unprotect($queryString, $isSource, NumberProtector::alias());
        $regex = NumberProtector::fullTagRegex();

        if (! preg_match($regex, $queryString)) {
            return $queryString;
        }

        $currentId = 1;
        $queryString = preg_replace_callback(
            $regex,
            function (array $tagProps) use (&$currentId, &$numberTagMap, $isSource) {
                $tag = array_shift($tagProps);

                if (count($tagProps) < 7) {
                    $this->logger->warn(
                        'E1625',
                        "Protection Tag doesn't has required meta info. Fuzzy searches may return worse match rate"
                    );
                    $tagProps = array_pad($tagProps, 7, '');
                }

                unset($tagProps[5]);

                $tagProps = array_combine(['type', 'name', 'source', 'iso', 'target', 'regex'], $tagProps);

                if (empty($tagProps['regex'])) {
                    // for BC reasons, we use the name as regex
                    $tagProps['regex'] = base64_encode($tagProps['name']);
                }

                $protectedContent = $isSource ? $tagProps['source'] : $tagProps['target'];

                $protectedContent = html_entity_decode($protectedContent);

                if ($isSource) {
                    if (! isset($numberTagMap[$tagProps['regex']][$tag]['ids'])) {
                        $numberTagMap[$tagProps['regex']][$tag]['ids'] = new \SplQueue();
                    }
                    $numberTagMap[$tagProps['regex']][$tag]['ids']->enqueue($currentId);
                } else {
                    $ids = $numberTagMap[$tagProps['regex']][$tag]['ids'] ?? null;

                    $currentId = null !== $ids && ! $ids->isEmpty() ? $ids->dequeue() : $currentId;
                }

                $numberTagMap[$tagProps['regex']][$tag]['protectedContent'][$protectedContent][] = $currentId;

                $t5nTag = new T5NTag($currentId, $tagProps['regex'], $protectedContent);

                $currentId++;

                return $t5nTag->toString();
            },
            $queryString
        );

        return $queryString;
    }

    public function convertPair(string $source, string $target, int $sourceLang, int $targetLang): array
    {
        $source = $this->convertT5MemoryTagToContent($source);
        $target = $this->convertT5MemoryTagToContent($target);

        $source = $this->collapseTmxTags($source);
        $target = $this->collapseTmxTags($target);

        $protectedSource = $this->contentProtector->protect(
            $source,
            true,
            $sourceLang,
            $targetLang,
            EntityHandlingMode::Off
        );

        $protectedTarget = $this->contentProtector->protect(
            $target,
            false,
            $sourceLang,
            $targetLang,
            EntityHandlingMode::Off
        );

        [$source, $target] = $this->contentProtector->filterTags($protectedSource, $protectedTarget);

        $tagMap = [];

        return [
            $this->convertContentTagToT5MemoryTag($source, true, $tagMap),
            $this->convertContentTagToT5MemoryTag($target, false, $tagMap),
        ];
    }

    private function collapseTmxTags(string $segment): string
    {
        return preg_replace('#<(ph|bpt|ept) ([^>]*)>(.*)</\1>#U', '<$1 $2/>', $segment);
    }

    private function protectHtmlEntities(string $text): string
    {
        return preg_replace('/&(\w{2,8});/', '¿¿¿\1¿¿¿', $text);
    }

    private function unprotectHtmlEntities(string $text): string
    {
        return preg_replace('/¿¿¿(\w{2,8})¿¿¿/', '&\1;', $text);
    }
}
