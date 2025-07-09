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

namespace MittagQI\Translate5\T5Memory\Contract;

use editor_Models_Languages as Language;
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\TMX\BrokenTranslationUnitLogger;

interface TmxImportProcessor
{
    public function supports(Language $sourceLang, Language $targetLang, ImportOptions $importOptions): bool;

    public function next(): ?TmxImportProcessor;

    public function setNext(TmxImportProcessor $processor): void;

    /**
     * Higher value will be processed first.
     */
    public function order(): int;

    /**
     * Processes a translation unit (tu) for import into a TMX file.
     *
     * @param string $tu An iterable collection of translation units to process.
     * @return iterable<string> An iterable collection of processed translation units.
     */
    public function process(
        string $tu,
        Language $sourceLang,
        Language $targetLang,
        ImportOptions $importOptions,
        BrokenTranslationUnitLogger $brokenTranslationUnitLogger,
    ): iterable;
}
