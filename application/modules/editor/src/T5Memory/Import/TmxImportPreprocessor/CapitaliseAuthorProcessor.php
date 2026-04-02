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

namespace MittagQI\Translate5\T5Memory\Import\TmxImportPreprocessor;

use editor_Models_Languages as Language;
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\TMX\BrokenTranslationUnitLogger\Contract\BrokenTranslationUnitLoggerInterface;

class CapitaliseAuthorProcessor extends Processor
{
    public function __construct(
    ) {
    }

    public static function create(): self
    {
        return new self(
        );
    }

    public function supports(Language $sourceLang, Language $targetLang, ImportOptions $importOptions): bool
    {
        return true;
    }

    public function order(): int
    {
        return 400;
    }

    protected function processTu(
        string $tu,
        Language $sourceLang,
        Language $targetLang,
        ImportOptions $importOptions,
        BrokenTranslationUnitLoggerInterface $brokenTranslationUnitIndicator,
    ): iterable {
        // <tu tuid="1" creationdate="20000101T120000Z" creationid="manager">
        return yield preg_replace_callback(
            '#<tu.+creationid="(.+)".*>#Uu',
            fn (array $matches) => str_replace(
                'creationid="' . $matches[1] . '"',
                'creationid="' . strtoupper($matches[1]) . '"',
                $matches[0],
            ),
            $tu,
            1,
        );
    }
}
