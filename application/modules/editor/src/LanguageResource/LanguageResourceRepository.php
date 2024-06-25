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

namespace MittagQI\Translate5\LanguageResource;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class LanguageResourceRepository
{
    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function get(int $id): LanguageResource
    {
        $languageResource = ZfExtended_Factory::get(LanguageResource::class);
        $languageResource->load($id);

        return $languageResource;
    }

    /**
     * @return iterable<LanguageResource>
     */
    public function getRelatedByLanguageCombinationsAndCustomers(
        LanguageResource $languageResource,
        ?string $serviceName = null
    ): iterable {
        $adapter = $languageResource->db->getAdapter();

        $sql = 'SELECT distinct (lr.id) as lr_id FROM LEK_languageresources lr
                INNER JOIN LEK_languageresources_languages ll ON lr.id = ll.languageResourceId
                INNER JOIN LEK_languageresources_customerassoc ca ON lr.id = ca.languageResourceId
                WHERE (
                    ll.sourceLang IN (' . implode(',', (array) $languageResource->getSourceLang()) . ')
                    OR ll.targetLang IN (' . implode(',', (array) $languageResource->getTargetLang()) . ')
                )
                AND ca.customerId IN (' . implode(',', $languageResource->getCustomers()) . ')';

        if ($serviceName) {
            $sql .= ' AND lr.serviceName = ' . $adapter->quote($serviceName);
        }

        foreach ($adapter->query($sql)->fetchAll() as $row) {
            yield $this->get((int) $row['lr_id']);
        }
    }
}
