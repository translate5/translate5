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

namespace MittagQI\Translate5\Segment\ChunkProtection;

use editor_Models_Segment_Utility as SegmentUtility;
use editor_Models_Segment_Whitespace as Whitespace;

class ChunkProtection
{
    /**
     * All entities are restored to their applicable characters (&_szlig; => ß),
     * only the XML relevant &<> are encoded (ready for GUI)
     */
    public const ENTITY_MODE_RESTORE = 'restore';

    /**
     * Nothing is restored, but encoded (&_szlig; => &_amp;szlig;),
     * only the XML relevant &<> are encoded (ready for GUI)
     */
    public const ENTITY_MODE_KEEP = 'keep';

    /**
     * Entity handling is disabled, entities must be handled elsewhere!
     */
    public const ENTITY_MODE_OFF = 'off';

    public function __construct(private NumberProtection $number, private Whitespace $whitespace)
    {
    }

    public function protect(
        string $textNode,
        ?int $sourceLang,
        ?int $targetLang,
        string $entityHandling = self::ENTITY_MODE_RESTORE
    ): string {
        if ($entityHandling !== self::ENTITY_MODE_OFF) {
            $textNode = SegmentUtility::entityCleanup($textNode, $entityHandling === self::ENTITY_MODE_RESTORE);
        }

        $textNode = $this->number->protect($textNode, $sourceLang, $targetLang);
        $textNode = $this->whitespace->protectWhitespace($textNode, self::ENTITY_MODE_OFF);

        return $textNode;
    }
}