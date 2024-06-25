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

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\CrossSynchronizationConnection;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\SyncConnectionService;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\LanguagePair;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\SynchronizationType;
use MittagQI\Translate5\Terminology\TermCollectionRepository;
use MittagQI\Translate5\Tools\CharCleanup;

class editor_Services_TermCollection_Service extends editor_Services_ServiceAbstract implements SyncConnectionService
{
    public const DEFAULT_COLOR = '19737d';

    /**
     * URL to confluence-page
     * @var string
     */
    protected static $helpPage = "https://confluence.translate5.net/display/TAD/Term+Collection";

    protected $resourceClass = 'editor_Services_TermCollection_Resource';

    private TermCollectionRepository $termCollectionRepository;

    public function __construct()
    {
        parent::__construct();

        $this->termCollectionRepository = new TermCollectionRepository();
    }

    /**
     * {@inheritDoc}
     */
    public function syncSourceOf(): array
    {
        return [SynchronizationType::Glossary];
    }

    /**
     * {@inheritDoc}
     */
    public function syncTargetFor(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getSyncData(
        LanguageResource $languageResource,
        LanguagePair $languagePair,
        ?int $customerId,
        SynchronizationType $synchronizationType
    ): Generator {
        $terms = $this->termCollectionRepository
            ->getTermTranslationsForLanguageCombo(
                (int) $languageResource->getId(),
                $languagePair->sourceId,
                $languagePair->targetId
            );

        foreach ($terms as $item) {
            $item['source'] = CharCleanup::cleanTermForMT($item['source']);
            $item['target'] = CharCleanup::cleanTermForMT($item['target']);

            if (empty($item['source']) || empty($item['target'])) {
                continue;
            }

            yield [
                'source' => $item['source'],
                'target' => $item['target'],
            ];
        }
    }

    public function isOneToOne(): bool
    {
        return false;
    }

    /**
     * @see editor_Services_ServiceAbstract::isConfigured()
     */
    public function isConfigured(): bool
    {
        return true;
    }

    /**
     * @see editor_Services_ServiceAbstract::embedService()
     */
    protected function embedService()
    {
        $this->addResource([$this->getServiceNamespace(), $this->getName()]);
    }

    /**
     * (non-PHPdoc)
     * @see editor_Services_ServiceAbstract::getName()
     */
    public function getName()
    {
        return 'TermCollection';
    }
}
