<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/
declare(strict_types=1);

namespace MittagQI\Translate5\Segment\TagProtection\Protector\Number;

use editor_Models_Languages;
use MittagQI\Translate5\Repository\LanguageNumberFormatRepository;
use MittagQI\Translate5\Segment\TagProtection\Protector\ChunkDto;
use MittagQI\Translate5\Segment\TagProtection\Protector\RatingInterface;

abstract class AbstractProtector implements NumberProtectorInterface, RatingInterface
{
    public const TYPE = 'invalid';

    protected const TAG_FORMAT = '<number type="%s" name="%s" source="%s" iso="%s" target="%s" />';

    private array $formatsCache = [];

    public function __construct(protected LanguageNumberFormatRepository $formatRepository)
    {
    }

    public function hasEntityToProtect(string $textNode, ?editor_Models_Languages $sourceLang): bool
    {
        if (preg_match($this->getJoinedRegex(), $textNode)) {
            return true;
        }

        foreach (array_column($this->getFormatsByLang($sourceLang), 'regex') as $regex) {
            if (preg_match($regex, $textNode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function protect(
        iterable $chunks,
        ?editor_Models_Languages $sourceLang,
        ?editor_Models_Languages $targetLang
    ): iterable
    {
        $regexes = $this->getSplitRegexes($sourceLang);

        foreach ($chunks as $chunk) {
            if ($chunk->protected) {
                yield $chunk;

                continue;
            }

            foreach ($regexes as $regex) {
                preg_match_all($regex, $chunk->text, $matches, PREG_SPLIT_DELIM_CAPTURE);

                yield from $this->processSplitNodes(
                    preg_split($regex, $chunk->text),
                    $matches,
                    $sourceLang,
                    $targetLang
                );
            }
        }
    }

    protected function getSplitRegexes(?editor_Models_Languages $sourceLang): array
    {
        // try custom regexes and only then default
        $regexes = array_column($this->getFormatsByLang($sourceLang), 'regex');
        $regexes[] = $this->getJoinedRegex();

        return $regexes;
    }

    protected function getNodeToProtect(array $matches): string
    {
        return $matches[0];
    }

    /**
     * @return array<array{regex: string, format: string}>
     */
    abstract protected function getDefaultFormats(): array;

    abstract protected function composeNumberTag(
        string $number,
        array $sourceFormat,
        ?editor_Models_Languages $targetLang,
        ?string $targetFormat
    ): string;

    abstract protected function composeRegex(string ...$parts): string;

    /**
     * @param string[] $splitNodes
     * @param string[][] $nodesToProtect
     * @return iterable<ChunkDto>
     */
    protected function processSplitNodes(
        array $splitNodes,
        array $nodesToProtect,
        ?editor_Models_Languages $sourceLang,
        ?editor_Models_Languages $targetLang
    ): iterable {
        $matchCount = count($nodesToProtect);
        for ($i = 0; $i <= $matchCount; $i++) {
            if (!empty($splitNodes[$i])) {
                // yield not protected part of text node
                yield new ChunkDto($splitNodes[$i]);
            }

            if (!isset($nodesToProtect[$i])) {
                continue;
            }

            // this node is a part of regex match. so we need to concat trimmed parts in chuck
            // example:
            // match: ⎵123,456.789⎵
            // node: 123,456.789
            // chunk: ⎵<number type="float" ...>⎵
            $node = $this->getNodeToProtect($nodesToProtect[$i]);

            $parts = explode($node, $nodesToProtect[$i][0]);

            try {
                $protected = $this->protectNode($node, $sourceLang, $targetLang);

                yield new ChunkDto($parts[0] . $protected . $parts[1], true);
            } catch (\LogicException) {
                yield new ChunkDto($node, false);
            }
        }
    }

    protected function protectNode(
        string $node,
        ?editor_Models_Languages $sourceLang,
        ?editor_Models_Languages $targetLang
    ): string {
        $targetFormat = null;

        // if source lang provided we'll firstly try to check existing user provided formats
        if (null !== $sourceLang) {
            $protected = $this->protectNodeOfCustomFormats($node, $sourceLang, $targetLang);

            if (null !== $protected) {
                // yield protected date of format provided by user and continue with next text part
                return $protected;
            }
        }

        if ($targetLang) {
            $targetFormat = $this->formatRepository->findDateFormat($targetLang->getId(), 'default')?->getFormat();
        }

        foreach ($this->getDefaultFormats() as $sourceFormat) {
            if (!$this->nodeMatchesRegex($node, $sourceFormat['regex'])) {
                continue;
            }

            return $this->composeNumberTag($node, $sourceFormat, $targetLang, $targetFormat);
        }

        throw new \LogicException(
            sprintf('None of regex matches current date "%s" that should not be possible', $node)
        );
    }

    protected function getJoinedRegex(): string
    {
        return $this->composeRegex(...array_column($this->getDefaultFormats(), 'regex'));
    }

    protected function getFormatsByLang(?editor_Models_Languages $sourceLang): array
    {
        if (null === $sourceLang) {
            return [];
        }

        if (!isset($this->formatsCache[$sourceLang->getRfc5646()])) {
            $this->formatsCache[$sourceLang->getRfc5646()] = $this->formatRepository
                ->findByLanguageIdAndType($sourceLang->getId(), static::TYPE);
        }

        return $this->formatsCache[$sourceLang->getRfc5646()];
    }

    protected function nodeMatchesRegex(string $nodeToProtect, string ...$regex): bool
    {
        return (bool) preg_match($this->composeRegex(...$regex), $nodeToProtect);
    }

    protected function protectNodeOfCustomFormats(
        string $node,
        editor_Models_Languages $sourceLang,
        ?editor_Models_Languages $targetLang,
    ): ?string {
        $formats = $this->getFormatsByLang($sourceLang);

        if (!$this->nodeMatchesRegex($node, ...array_column($formats, 'regex'))) {
            return null;
        }

        $targetFormat = null;

        foreach ($formats as $sourceFormat) {
            if ($targetLang) {
                $targetFormat = $this->formatRepository
                    ->findDateFormat($targetLang->getId(), $sourceFormat['name'])
                    ?->getFormat();
            }

            if (!$this->nodeMatchesRegex($node, $sourceFormat['regex'])) {
                continue;
            }

            return $this->composeNumberTag(
                $node,
                $sourceFormat,
                $targetLang,
                $targetFormat
            );
        }

        return null;
    }
}