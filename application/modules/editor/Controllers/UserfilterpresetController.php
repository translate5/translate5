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
use editor_Models_UserConfig as UserConfig;
use MittagQI\Translate5\User\FilterPreset as FilterPreset;
use MittagQI\ZfExtended\MismatchException;
use ZfExtended_Factory as Factory;

class editor_UserfilterpresetController extends ZfExtended_RestController
{
    /**
     * Use termportal trait
     */
    use editor_Controllers_Traits_ControllerTrait;

    /**
     * @var string
     */
    protected $entityClass = FilterPreset::class;

    /**
     * @var FilterPreset
     */
    protected $entity;

    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $_translate;

    /**
     * @throws MismatchException
     * @throws Zend_Db_Statement_Exception
     */
    public function init()
    {
        // Call parent
        parent::init();

        // If request contains json-encoded 'data'-param, decode it and append to request params
        $this->handleData();

        // Setup translator
        $this->_translate = ZfExtended_Zendoverwrites_Translate::getInstance();
    }

    public function indexAction()
    {
        // Term attributes are currently not listable via REST API
        throw new BadMethodCallException();
    }

    public function getAction()
    {
        throw new BadMethodCallException();
    }

    /**
     * Create new filter preset
     *
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function postAction()
    {
        // Prepare variables
        $panel = $this->getParam('panel');
        $userGuid = $this->user()->getUserGuid();
        $configName = "runtimeOptions.frontend.defaultState.editor.$panel";
        $state = Factory::get(UserConfig::class)->getCurrentValue($userGuid, $configName);

        // Check params
        try {
            $this->jcheck([
                'title' => [
                    'req' => 'true:Please specify preset name',
                    'rex' => 'varchar255',
                ],
                'panel' => [
                    'fis' => 'projectGrid,adminTaskGrid,tmOverviewPanel,adminUserGrid,customerPanelGrid',
                ],
            ]);

            // Catch mismatch-exception
        } catch (MismatchException $e) {
            // Flush msg
            $this->jflush(false, $e->getMessage());
        }

        // Init new preset
        $this->entity->init([
            'title' => $this->getParam('title'),
            'userId' => $this->user()->getId(),
            'panel' => $panel,
            'state' => $state,
        ]);

        // Save preset
        try {
            $this->entity->save();
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
            $this->jflush(false, 'Filter preset having such name already exists for this panel');
        }

        // Flush success with preset details
        $this->jflush([
            'success' => true,
            'created' => $this->entity->toArray(),
        ]);
    }

    /**
     * @throws ZfExtended_Models_Entity_NoAccessException
     */
    public function deleteAction()
    {
        // Show confirmation prompt, but for XHR-requests only
        $this->confirm('Sind Sie sicher?');

        // Load entity
        $this->entityLoad();

        // If non-owner is trying to delete the preset - throw an exception
        if ($this->entity->getUserId() !== $this->user()->getId()) {
            throw new ZfExtended_Models_Entity_NoAccessException();
        }

        // Do delete
        parent::deleteAction();

        // Flush success
        $this->jflush(true);
    }
}
