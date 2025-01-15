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

use MittagQI\Translate5\Plugins\SpellCheck\Exception\DownException;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\RequestException;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\TimeOutException;
use MittagQI\Translate5\Plugins\SpellCheck\LanguageTool\AdapterConfigDTO;
use MittagQI\Translate5\Plugins\SpellCheck\LanguageTool\Service;
use MittagQI\Translate5\Task\Current\NoAccessException;
use MittagQI\Translate5\Task\TaskContextTrait;

/**
 * Controller for the Plugin SpellCheck
 */
class editor_Plugins_SpellCheck_SpellCheckQueryController extends ZfExtended_RestController
{
    use TaskContextTrait;

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     * @throws NoAccessException
     */
    public function init()
    {
        $this->initRestControllerSpecific();
        $this->initCurrentTask();
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__);
    }

    /**
     * Get the languages that are supported by the tool we use (currently: LanguageTool).
     * @throws ZfExtended_Exception
     */
    public function languagesAction()
    {
        if ($this->getParam('targetLangCode') && $this->getParam('targetLangCode') != "") {
            $targetLangCode = $this->getParam('targetLangCode');
        }
        if (! $targetLangCode) {
            $this->view->rows = [];

            return;
        }
        $service = editor_Plugins_SpellCheck_Init::createService('languagetool');
        // no config needed here for adapter
        $this->view->rows = $service->getAdapter(AdapterConfigDTO::create())->getSupportedLanguage($targetLangCode);
    }

    /**
     * The matches that our tool finds (currently: LanguageTool).
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws DownException
     * @throws RequestException
     * @throws TimeOutException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     * @throws editor_Models_ConfigException
     */
    public function matchesAction()
    {
        $text = $this->getParam('text', '');
        $language = $this->getParam('language', '');
        if (empty($text) || empty($language)) {
            $this->view->rows = [];

            return;
        }
        $service = editor_Plugins_SpellCheck_Init::createService('languagetool');

        $this->view->rows = $service
            ->getAdapter(AdapterConfigDTO::create(config: $this->getCurrentTask()->getConfig()))
            ->getMatches($text, $language);
    }

    public function getAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->get');
    }

    public function putAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->put');
    }

    public function deleteAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->delete');
    }

    public function postAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->post');
    }
}
