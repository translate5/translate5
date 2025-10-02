<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\T5Memory\Api;

use MittagQI\Translate5\T5Memory\Api\Exception\SegmentTooLongException;
use ZfExtended_Zendoverwrites_Translate;

class SegmentLengthValidator
{
    public const MAX_STR_LENGTH = 2048;

    public function __construct(
        private readonly ZfExtended_Zendoverwrites_Translate $translate,
    ) {
    }

    public static function create(): self
    {
        return new self(
            ZfExtended_Zendoverwrites_Translate::getInstance(),
        );
    }

    /**
     * Current max segment length for t5memory is MAX_STR_LENGTH characters, but:
     * 1,2 and 3 Byte long characters are counting as 1 character, while 4Byte Characters are counting as 2 Characters.
     * Therefor the below special count is needed.
     *
     * @throws SegmentTooLongException
     */
    public function isValid(string $string): bool
    {
        $realCharLength = mb_strlen($string);
        if ($realCharLength < (self::MAX_STR_LENGTH / 2)) {
            return true;
        }

        //since for t5memory 4Byte characters seems to count 2 characters,
        // we have to count and add them to get the real count
        $smileyCount = preg_match_all('/[\x{10000}-\x{10FFFF}]/mu', $string);

        return ($realCharLength + $smileyCount) <= self::MAX_STR_LENGTH;
    }

    public function validate(string $string): void
    {
        if (! $this->isValid($string)) {
            throw new SegmentTooLongException(
                str_replace(
                    '2048',
                    (string) self::MAX_STR_LENGTH,
                    $this->translate->_(
                        'Das Segment konnte nur in der Aufgabe, nicht aber ins TM gespeichert werden. Segmente länger als 2048 Bytes sind nicht im TM speicherbar.'
                    )
                )
            );
        }
    }
}
