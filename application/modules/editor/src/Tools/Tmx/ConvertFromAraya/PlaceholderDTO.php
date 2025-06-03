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
class PlaceholderDTO
{
    public int $startId = 0;

    public string $phTag = '';

    protected string $orgTag = '';

    protected string $orgTagName = '';

    public bool $isSelfCLosing = false;

    public bool $isOpeningTag = false;

    public bool $isClosingTag = false;

    public bool $isPlaceholder = false;

    protected int $iAttributCounter = 0;

    protected string $usedInLanguages = ':';

    protected bool $isInvalid = false;

    public function setOrgTag(string $orgTag): void
    {
        $this->orgTag = $orgTag;

        $pattern = '#</?\s*([a-zA-Z0-9:_-]+)#';
        if (preg_match($pattern, $orgTag, $matches)) {
            $this->orgTagName = $matches[1];
        }

        $this->isSelfCLosing = (str_contains($orgTag, '/>'));
        $this->isClosingTag = (str_contains($orgTag, '</'));
        $this->isOpeningTag = (str_contains($orgTag, '<') && ! $this->isSelfCLosing && ! $this->isClosingTag);

        // tags where none of the 3 isXyz is set, will be marked as "isPlaceholder"
        // Sample: <ph>|L|</ph>
        if (! $this->isSelfCLosing && ! $this->isOpeningTag && ! $this->isClosingTag) {
            $this->isPlaceholder = true;
        }
    }

    public function getOrgTagName(): string
    {
        return $this->orgTagName;
    }

    public function setIAttributCounter(int $iAttributCounter): void
    {
        $this->iAttributCounter = $iAttributCounter;
    }

    public function getIAttributCounter(): int
    {
        return $this->iAttributCounter;
    }

    public function setUsedInLanguages(string $languages): void
    {
        $this->usedInLanguages .= $languages . ':';
    }

    public function isUsedInLanguages(string $language): bool
    {
        return str_contains($this->usedInLanguages, ':' . $language . ':');
    }

    public function setIsInvalid(bool $isInvalid = true): void
    {
        $this->isInvalid = $isInvalid;
    }

    public function isInvalid(): bool
    {
        return $this->isInvalid;
    }
}
