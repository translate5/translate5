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

namespace MittagQI\Translate5\Plugins\TermImport\DTO;

use editor_Models_Customer_Customer as Customer;
use MittagQI\Translate5\Plugins\TermImport\Exception\InvalidInstructionsIniFileException;
use MittagQI\Translate5\Plugins\TermImport\Service\LoggerService;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class InstructionsDTO
{
    /**
     * File mapping group name inside ini-file
     *
     * @var string
     */
    public const FILE_MAPPING_GROUP = "FileMapping";

    /**
     * Collection mapping group name inside ini-file
     *
     * @var string
     */
    public const COLLECTION_MAPPING_GROUP = "CollectionMapping";

    public bool $mergeTerms = true;

    public string $deleteTermsLastTouchedOlderThan = '2018-05-01';

    public bool $deleteTermsOlderThanCurrentImport = true;

    public string $deleteProposalsLastTouchedOlderThan = '2018-05-01';

    public bool $deleteProposalsOlderThanCurrentImport = false;

    /**
     * Array of [Tbx-filename => Term collection name] pairs.
     * Initially, it's filled with the data from instructions file, but some pairs can be excluded
     * due to no customer found in db with number mapped for such term collection name
     */
    public array $FileMapping = [];

    /**
     * Array of [Term collection name => Customer number]
     * Filled with the data from instructions file
     */
    public array $CollectionMapping = [];

    /**
     * InstructionsDTO constructor.
     *
     * @throws InvalidInstructionsIniFileException
     * @throws \ReflectionException
     */
    public function __construct(array $instructions, LoggerService $logger)
    {
        // Validate ini-file contents
        $errors = $this->validateInstructions($instructions);

        // If errors detected - throw exception
        if (! empty($errors)) {
            throw new InvalidInstructionsIniFileException($errors);
        }

        // Get customer model
        $customerM = ZfExtended_Factory::get(Customer::class);

        // Pick values from ini-file and apply as public props
        foreach ([
            'mergeTerms',
            'deleteTermsLastTouchedOlderThan',
            'deleteTermsOlderThanCurrentImport',
            'deleteProposalsLastTouchedOlderThan',
            'deleteProposalsOlderThanCurrentImport',
            'FileMapping',
            'CollectionMapping',
        ] as $prop) {
            $this->$prop = $instructions[$prop] ?? $this->$prop;
        }

        // Check whether there are customers having given numbers in our database
        foreach ($instructions[self::FILE_MAPPING_GROUP] as $tbxFileName => $termCollectionName) {
            if ($customerNumber = $instructions[self::COLLECTION_MAPPING_GROUP][$termCollectionName] ?? false) {
                try {
                    $customerM->loadByNumber($customerNumber);
                } catch (ZfExtended_Models_Entity_NotFoundException) {
                    $logger->customerNotFound($customerNumber);
                    unset($this->FileMapping[$tbxFileName]);
                }
            } else {
                unset($this->FileMapping[$tbxFileName]);
            }
        }
    }

    private function validateInstructions(array $instructions): array
    {
        // Errors will be collected here
        $errors = [];

        // Check file mapping group presence
        if (! array_key_exists($fGroup = self::FILE_MAPPING_GROUP, $instructions)
            || ! is_array($instructions[$fGroup])
            || ! count($instructions[$fGroup])
        ) {
            $errors[] = "[$fGroup] section is missing or empty in ini-file";
        }

        // Check collection mapping group presence
        if (
            ! array_key_exists($cGroup = self::COLLECTION_MAPPING_GROUP, $instructions)
            || ! is_array($instructions[$cGroup])
            || ! count($instructions[$cGroup])
        ) {
            $errors[] = "[$cGroup] section is missing or empty in ini-file";
        }

        // If both are there - make sure customer numbers are not missing
        if (count($errors) === 0) {
            foreach (array_unique($instructions[$fGroup]) as $termCollection) {
                if (! trim((string) ($instructions[$cGroup][$termCollection] ?? ''))) {
                    $errors[] = "Client number is missing for TermCollection '$termCollection' in [$cGroup] section of ini-file";
                }
            }
        }

        return $errors;
    }
}
