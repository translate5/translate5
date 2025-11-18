<?php

/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

namespace MittagQI\Translate5\Segment\Tag;

use editor_Segment_Internal_Tag;
use editor_Segment_Tag;

/**
 * This Class mimics in PHP what happens in Editor.util.HtmlCleanup cleanAndSplitInternalTags
 * It removes trackchanges, terminology and all internal tags, replaces them with strip-placeholders,
 * removes the whitespace-tags if wanted,  * and splits the content where the internal tags have been
 */
class TagCleanup
{
    public function __construct(
        private bool $stripWhitespace = false
    ) {
    }

    /**
     * @throws \ZfExtended_Exception
     */
    public function clean(string $segmentMarkup): array
    {
        $tags = new SegmentTagSequence($segmentMarkup);
        // remove trackchanges and all types but internal tags
        $tags = $tags->cloneWithoutTrackChanges([editor_Segment_Tag::TYPE_INTERNAL]);
        // replace or strip whitespace-tags
        $numTags = $tags->numTags();
        for ($i = 0; $i < $numTags; $i++) {
            $tag = $tags->getAt($i); /** @var editor_Segment_Internal_Tag $tag */
            $tags->toPlaceholderAt($i, $this->createPlaceHolder($tag));
        }
        // generate the replaced Markup
        $markup = $tags->render();
        // condense the splits & split the string
        // crucial: open/close sequences may contain just internal single tags and will be replaced as a whole
        $markup = preg_replace('/<t5open>(<t5single>)*<t5close>/im', '<t5split>', $markup);
        // neighbouring open-split or split-close construct are also replaced, only open/close combinations with
        // real content in between (or no = empty real content) shall be kept
        $markup = preg_replace('/<t5close>(<t5split>)+/im', '<t5close>', $markup);
        $markup = preg_replace('/(<t5split>)+<t5open>/im', '<t5open>', $markup);
        // replace remaining open / close (and to make sure single as well)
        $markup = preg_replace('/<t5open>/im', '<t5split>', $markup);
        $markup = preg_replace('/<t5close>/im', '<t5split>', $markup);
        $markup = preg_replace('/<t5single>/im', '<t5split>', $markup);
        $chunks = [];
        foreach (explode('<t5split>', $markup) as $chunk) {
            if ($chunk !== '') {
                $chunks[] = $chunk;
            }
        }

        return $chunks;
    }

    private function createPlaceHolder(editor_Segment_Internal_Tag $tag): string
    {
        if ($tag->isPlaceable() || $tag->isNumber() || $tag->isSpecialCharacter()) {
            return $tag->getReplacedContent();
        }
        if ($tag->isWhitespace()) {
            if ($this->stripWhitespace) {
                return '<t5split>';
            }
            if ($tag->isNbsp()) {
                return '&nbsp;<t5split>';
            }
            if ($tag->isTab()) {
                return ' &emsp;<t5split>';
            }
            if ($tag->isNewline()) {
                return '<br/><t5split>';
            }
        }
        if ($tag->isSingle()) {
            return '<t5single>';
        }
        if ($tag->isOpening()) {
            return '<t5open>';
        }
        if ($tag->isClosing()) {
            return '<t5close>';
        }

        return '<t5split>';
    }
}
