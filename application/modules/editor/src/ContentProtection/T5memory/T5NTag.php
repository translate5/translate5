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

class T5NTag
{
    public const TAG = 't5:n';

    public function __construct(
        public readonly int $id,
        public readonly string $rule,
        public readonly string $content,
    ) {
    }

    public static function fromMatch(array $match): self
    {
        $protectedContent = html_entity_decode($match[3], ENT_XML1);
        // return < and > from special chars that was used to avoid error in t5memory
        $protectedContent = str_replace(['*≺*', '*≻*'], ['<', '>'], $protectedContent);

        return new self(
            (int) $match[1],
            $match[2],
            $protectedContent,
        );
    }

    public function getRegex(): string
    {
        return gzinflate(base64_decode($this->rule));
    }

    public static function fullTagRegex(): string
    {
        return sprintf('/<%s id="(\d+)" r="(.+)" n="(.+)"\s?\/>/Uu', self::TAG);
    }

    public function toString(): string
    {
        // replace < and > with special chars to avoid error in t5memory
        // simple htmlentities or rawurlencode would not work
        $protectedContent = str_replace(['<', '>'], ['*≺*', '*≻*'], $this->content);
        $protectedContent = htmlentities($protectedContent, ENT_XML1);

        return sprintf(
            '<%s id="%s" r="%s" n="%s"/>',
            self::TAG,
            $this->id,
            $this->rule,
            $protectedContent,
        );
    }
}
