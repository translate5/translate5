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

use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;

class editor_Services_OpenTM2_Resource extends editor_Models_LanguageResources_Resource
{
    public function __construct(string $id, string $name, string $url)
    {
        parent::__construct($id, $name, $url);

        $this->supportsStrippingFramingTags = true;
    }

    public function getStrippingFramingTagsConfig(): array
    {
        return [
            self::STRIP_FRAMING_TAGS_VALUES => [
                [StripFramingTags::None->value, 'Entfernt keine'],
                [StripFramingTags::All->value, 'Alle'],
                [StripFramingTags::Paired->value, 'Tagpaare'],
            ],
            self::STRIP_FRAMING_TAGS_FILE_EXTENSIONS => ['.tmx', '.zip'],
        ];
    }

    public function supportsInternalFuzzy(): bool
    {
        return true;
    }
}
