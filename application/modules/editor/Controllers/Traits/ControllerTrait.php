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

use MittagQI\ZfExtended\MismatchException;

trait editor_Controllers_Traits_ControllerTrait
{
    protected ?ZfExtended_Models_User $user;

    /**
     * Alias for editor_Utils::jcheck(), except that if $data arg is not given - request params will be used by default
     *
     * @param array|null|ZfExtended_Models_Entity_Abstract $data
     * @return array
     * @throws MismatchException
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @see editor_Utils::jcheck
     */
    public function jcheck(array $ruleA, $data = null)
    {
        return editor_Utils::jcheck($ruleA, $data ?? $this->getRequest()->getParams());
    }

    /**
     * Show confirmation prompt if current request is an XMLHttpRequest
     *
     * @param string $buttons OKCANCEL, YESNO, YESNOCANCEL
     * @param string|null $cancelMsg Msg, that will be shown in case if 'Cancel'
     *                    button was pressed or confirmation window was closed
     */
    public function confirm(string|array $msg, $buttons = 'OKCANCEL', $cancelMsg = null): string
    {
        // If current request is not a XMLHttpRequest - imitate the answer=ok to be given on
        // confirmation prompt so there will be no need to add '?answer=ok' into query string to proceed
        // This is useful when this method is used during handling of 'DELETE /some/resource'-requests
        // initiated by external REST API clients rather than by the browser window with translate5 app opened
        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
            return 'ok';
        }

        // Get answer index
        $answerIdx = editor_Utils::rif(editor_Utils::$answer, count(editor_Utils::$answer) + 1);

        // Get answer
        $answer = $this->getParam('answer' . $answerIdx);

        // Get translate instance
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();

        // If no answer, flush confirmation prompt
        if (! $answer) {
            // If $msg arg is not array - convert
            if (! is_array($msg)) {
                $msg = [$msg];
            }

            // Foreach phrase in $msg - translate
            foreach ($msg as &$phrase) {
                $phrase = $translate->_($phrase);
            }

            // Show confirmation prompt
            editor_Utils::jconfirm(join('<br>', $msg), $buttons);
        }

        // If answer is 'cancel' - stop request processing
        elseif ($answer == 'cancel') {
            $this->jflush(false, $translate->_($cancelMsg));
        }

        // Return answer
        return editor_Utils::$answer[count(editor_Utils::$answer)] = $answer;
    }

    /**
     * Alias for editor_Utils::jflush()
     *
     * @param string $msg
     * @return mixed
     */
    public function jflush(bool|array $success, $msg = '')
    {
        return forward_static_call_array(['editor_Utils', 'jflush'], func_get_args());
    }

    /**
     * If request contains json-encoded 'data'-param, decode it and append to request params
     * This may happen while running tests
     *
     * @throws Zend_Db_Statement_Exception
     * @throws MismatchException
     * @throws Zend_Db_Statement_Exception
     */
    public function handleData()
    {
        // If request contains json-encoded 'data'-param, decode it and append to request params
        if ($data = $this->jcheck([
            'data' => [
                'rex' => 'json',
            ],
        ])['data'] ?? 0) {
            $this->getRequest()->setParams(
                $this->getRequest()->getParams() + (array) $data
            );
        }
    }

    /**
     * lazy-load and return current user
     *
     * @return ZfExtended_Models_User|null
     */
    public function user()
    {
        return $this->user = $this->user ?? ZfExtended_Authentication::getInstance()->getUser();
    }
}
