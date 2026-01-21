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

namespace MittagQI\Translate5\LanguageResource\CustomerAssoc;

use editor_Models_LanguageResources_CustomerAssoc;
use ZfExtended_Factory;
use ZfExtended_UnprocessableEntity;
use ZfExtended_Zendoverwrites_Translate;

/**
 * Service to check and resolve TQE and TQE instant translate customer assignment conflicts across resources with same
 * language combinations.
 *
 * Business Rule: Only one resource per customer per language combination can be marked as TQE default or TQE instant
 * translate default. When a conflict is detected, the user must choose which resource should keep the default
 * assignment.
 */
class TqeConflictChecker
{
    public const FLAG_TQE = 'tqeAsDefault';

    public const FLAG_TQE_INSTANT_TRANSLATE = 'tqeInstantTranslateAsDefault';

    public function __construct(
    ) {
    }

    public static function create()
    {
        return new self();
    }

    /**
     * Check for TQE customer conflicts with other resources having the same language combinations
     *
     * @param int|null $currentResourceId Resource being created/edited (null for new resources)
     * @param array $tqeCustomerIds Customer IDs to be marked as TQE default
     * @param array $languagePairs Array of ['sourceLang' => int, 'targetLang' => int] pairs
     * @param string $flagType Type of flag to check (tqeAsDefault or tqeInstantTranslateAsDefault)
     * @throws ZfExtended_UnprocessableEntity if conflicts found
     */
    public function checkConflicts(?int $currentResourceId, array $tqeCustomerIds, array $languagePairs, string $flagType = self::FLAG_TQE): void
    {
        if (empty($tqeCustomerIds) || empty($languagePairs)) {
            return;
        }

        $conflicts = $this->findConflicts($currentResourceId, $tqeCustomerIds, $languagePairs, $flagType);

        if (! empty($conflicts)) {
            $this->throwConflictException($conflicts, $flagType);
        }
    }

    /**
     * Find conflicting resources that have the same customer as TQE/TQE default for the same language combination
     *
     * @param string $flagType Type of flag to check (tqeAsDefault or tqeInstantTranslateAsDefault)
     * @return array Array of conflicts with structure: [customerId => [resourceId => resourceData]]
     */
    public function findConflicts(?int $currentResourceId, array $tqeCustomerIds, array $languagePairs, string $flagType = self::FLAG_TQE): array
    {
        $customerAssoc = ZfExtended_Factory::get(editor_Models_LanguageResources_CustomerAssoc::class);
        $db = $customerAssoc->db;

        $select = $db->select()
            ->setIntegrityCheck(false)
            ->from(
                [
                    'ca' => 'LEK_languageresources_customerassoc',
                ],
                ['customerId', 'languageResourceId']
            )
            ->join(
                [
                    'lr' => 'LEK_languageresources',
                ],
                'ca.languageResourceId = lr.id',
                [
                    'resourceName' => 'name',
                    'serviceName',
                ]
            )
            ->join(
                [
                    'lrl' => 'LEK_languageresources_languages',
                ],
                'lr.id = lrl.languageResourceId',
                ['sourceLang', 'targetLang', 'sourceLangCode', 'targetLangCode']
            )
            ->where('ca.' . $flagType . ' = ?', 1)
            ->where('ca.customerId IN (?)', $tqeCustomerIds);

        // exclude current resource if editing
        if ($currentResourceId !== null) {
            $select->where('ca.languageResourceId != ?', $currentResourceId);
        }

        $results = $db->fetchAll($select)->toArray();

        if (empty($results)) {
            return [];
        }

        // group conflicts by customer and check for language combination overlaps
        $conflicts = [];
        foreach ($results as $row) {
            $customerId = (int) $row['customerId'];
            $resourceId = (int) $row['languageResourceId'];

            if ($this->hasLanguageOverlap($languagePairs, (int) $row['sourceLang'], (int) $row['targetLang'])) {
                if (! isset($conflicts[$customerId])) {
                    $conflicts[$customerId] = [];
                }

                if (! isset($conflicts[$customerId][$resourceId])) {
                    $conflicts[$customerId][$resourceId] = [
                        'resourceId' => $resourceId,
                        'resourceName' => $row['resourceName'],
                        'serviceName' => $row['serviceName'],
                        'languagePairs' => [],
                    ];
                }

                $conflicts[$customerId][$resourceId]['languagePairs'][] = [
                    'sourceLang' => (int) $row['sourceLang'],
                    'targetLang' => (int) $row['targetLang'],
                    'sourceLangCode' => $row['sourceLangCode'],
                    'targetLangCode' => $row['targetLangCode'],
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Check if the given language pair overlaps with any of the provided language pairs
     *
     * @param array $languagePairs Array of language pairs to check against
     * @param int $sourceLang Source language ID
     * @param int $targetLang Target language ID
     */
    protected function hasLanguageOverlap(array $languagePairs, int $sourceLang, int $targetLang): bool
    {
        // TODO: should we implement fuzzy logic ?
        foreach ($languagePairs as $pair) {
            if (isset($pair['sourceLang']) && isset($pair['targetLang']) &&
                (int) $pair['sourceLang'] === $sourceLang &&
                (int) $pair['targetLang'] === $targetLang) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve conflicts by removing default flag from specified resources for specified customers
     *
     * @param array $resolutionMap Map of [customerId => [resourceIds to remove flag from]]
     * @param string $flagType Type of flag to remove (tqeAsDefault or tqeInstantTranslateAsDefault)
     */
    public function resolveConflicts(array $resolutionMap, string $flagType = self::FLAG_TQE): void
    {
        $customerAssoc = ZfExtended_Factory::get(editor_Models_LanguageResources_CustomerAssoc::class);

        foreach ($resolutionMap as $customerId => $resourceIds) {
            foreach ($resourceIds as $resourceId) {
                $assoc = $customerAssoc->loadRowByCustomerIdAndResourceId($customerId, $resourceId);

                if ($assoc) {
                    if ($flagType === self::FLAG_TQE) {
                        $customerAssoc->setTqeAsDefault(false);
                    } elseif ($flagType === self::FLAG_TQE_INSTANT_TRANSLATE) {
                        $customerAssoc->setTqeInstantTranslateAsDefault(false);
                    }
                    $customerAssoc->save();
                }
            }
        }
    }

    /**
     * Throw exception with conflict details
     *
     * @param string $flagType Type of flag causing conflict
     * @throws ZfExtended_UnprocessableEntity
     */
    protected function throwConflictException(array $conflicts, string $flagType = self::FLAG_TQE): void
    {
        $isTqe = ($flagType === self::FLAG_TQE);
        $errorCode = $isTqe ? 'E1500' : 'E1501';

        ZfExtended_UnprocessableEntity::addCodes([
            'E1500' => 'TQE customer assignment conflict: The customer is already assigned as TQE default to another resource with the same language combination.',
            'E1501' => 'TQE instant-translate customer assignment conflict: The customer is already assigned as TQE instant-translate default to another resource with the same language combination.',
        ], 'languageresource');

        $conflictList = [];
        foreach ($conflicts as $customerId => $resources) {
            foreach ($resources as $resourceData) {
                $languagePairsText = implode(', ', array_map(function ($pair) {
                    return $pair['sourceLangCode'] . ' → ' . $pair['targetLangCode'];
                }, $resourceData['languagePairs']));

                $conflictList[] = [
                    'customerId' => $customerId,
                    'resourceId' => $resourceData['resourceId'],
                    'resourceName' => $resourceData['resourceName'],
                    'serviceName' => $resourceData['serviceName'],
                    'languagePairs' => $languagePairsText,
                    'languagePairsData' => $resourceData['languagePairs'],
                ];
            }
        }

        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $errorMessages = $isTqe ?
            [
                $translate->_('The selected customers are already assigned as TQE default for other language resources with the same language combinations.'),
                $translate->_('Would you like to remove the TQE assignments from the other resources and assign them to this resource?'),
            ] : [
                $translate->_('The selected customers are already assigned as TQE Instant-Translate default for other language resources with the same language combinations.'),
                $translate->_('Would you like to remove the TQE Instant-Translate assignments from the other resources and assign them to this resource?'),
            ];

        throw ZfExtended_UnprocessableEntity::createResponse($errorCode, [
            'errorMessages' => $errorMessages,
        ], extraData: [
            'conflicts' => $conflictList,
            'conflictsByCustomer' => $conflicts,
            'flagType' => $flagType,
        ]);
    }
}
