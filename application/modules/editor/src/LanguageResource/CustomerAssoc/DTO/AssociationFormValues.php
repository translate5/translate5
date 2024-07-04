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

namespace MittagQI\Translate5\LanguageResource\CustomerAssoc\DTO;

final class AssociationFormValues
{
    /**
     * @var int[]
     */
    public readonly array $customers;

    /**
     * @var int[]
     */
    public readonly array $useAsDefaultCustomers;

    /**
     * @var int[]
     */
    public readonly array $writeAsDefaultCustomers;

    /**
     * @var int[]
     */
    public readonly array $pivotAsDefaultCustomers;

    public function __construct(
        public readonly int $languageResourceId,
        array $customers,
        array $useAsDefaultCustomers,
        array $writeAsDefaultCustomers,
        array $pivotAsDefaultCustomers,
    ) {
        $this->customers = array_map(fn ($i) => (int) $i, $customers);
        $useAsDefaultCustomers = array_map(fn ($i) => (int) $i, $useAsDefaultCustomers);
        $writeAsDefaultCustomers = array_map(fn ($i) => (int) $i, $writeAsDefaultCustomers);
        $pivotAsDefaultCustomers = array_map(fn ($i) => (int) $i, $pivotAsDefaultCustomers);

        // ensure that only useAsDefault customers are used, which are added also as customers
        $this->useAsDefaultCustomers = array_intersect($useAsDefaultCustomers, $customers);

        // ensure that only writeAsDefault customers are used, which are added also as useAsDefault(read as default)
        $this->writeAsDefaultCustomers = array_intersect($writeAsDefaultCustomers, $this->useAsDefaultCustomers);

        // ensure that only pivotAsDefault customers are used, which are added also as customers
        $this->pivotAsDefaultCustomers = array_intersect($pivotAsDefaultCustomers, $customers);
    }

    public static function fromArray(int $languageResourceId, array $data): self
    {
        return new self(
            $languageResourceId,
            $data['customerIds'] ?? [],
            $data['customerUseAsDefaultIds'] ?? [],
            $data['customerWriteAsDefaultIds'] ?? [],
            $data['customerPivotAsDefaultIds'] ?? []
        );
    }
}