<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace Translate5\MaintenanceCli\Command\T5Memory\Traits;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_OpenTM2_Connector as Connector;
use editor_Services_OpenTM2_Service as Service;
use Generator;
use ZfExtended_Factory as Factory;

trait T5MemoryLocalTmsTrait
{
    protected function getLocalTms(?string $nameFilter = null): Generator
    {
        $languageResource = Factory::get(LanguageResource::class);
        $languageResourcesData = $languageResource->loadByService(Service::NAME);
        $connector = new Connector();

        foreach ($languageResourcesData as $languageResourceData) {
            if ($nameFilter
                && !str_contains(mb_strtolower($languageResourceData['name']), mb_strtolower($nameFilter))
            ) {
                continue;
            }

            $languageResource->load($languageResourceData['id']);

            try {
                $connector->connectTo(
                    $languageResource,
                    $languageResource->getSourceLang(),
                    $languageResource->getTargetLang()
                );

                $status = $connector->getStatus($languageResource->getResource());
            } catch (\Throwable) {
                $status = 'Language resource service is not available';
            }

            $tmName = $connector->getApi()->getTmName();
            $url = rtrim($languageResource->getResource()->getUrl(), '/') . '/';

            yield $url . ' - ' . $tmName => [
                'name' => $tmName,
                'uuid' => $languageResource->getLangResUuid(),
                'url' => $url,
                'status' => $status,
            ];
        }
    }

    /**
     * Returns array of local TMs in format [uuid => name]
     *
     * @param string|null $nameFilter
     * @return array<string, string>
     */
    protected function getLocalTmsList(?string $nameFilter = null): array
    {
        $list = [];

        foreach ($this->getLocalTms($nameFilter) as $item) {
            $list[$item['uuid']] = $item['name'];
        }

        return $list;
    }
}
