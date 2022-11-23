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

trait editor_Controllers_Traits_TermportalTrait {

    /**
     * @var ZfExtended_Models_User|null
     */
    protected ?ZfExtended_Models_User $user;

    /**
     * Alias for editor_Utils::jcheck(), except that if $data arg is not given - request params will be used by default
     *
     * @param $ruleA
     * @param array|null|ZfExtended_Models_Entity_Abstract $data
     * @return array
     * @throws ZfExtended_Mismatch
     * @throws Zend_Db_Statement_Exception
     * @see editor_Utils::jcheck
     */
    public function jcheck($ruleA, $data = null) {
        return editor_Utils::jcheck($ruleA, $data ?? $this->getRequest()->getParams());
    }

    /**
     * Show confirmation prompt
     *
     * @param $msg
     * @param string $buttons OKCANCEL, YESNO, YESNOCANCEL
     * @param string|null $cancelMsg Msg, that will be shown in case if 'Cancel'
     *                    button was pressed or confirmation window was closed
     * @return
     */
    public function confirm($msg, $buttons = 'OKCANCEL', $cancelMsg = null) {

        // Get answer index
        $answerIdx = editor_Utils::rif(editor_Utils::$answer, count(editor_Utils::$answer) + 1);

        // Get answer
        $answer = $this->getParam('answer' . $answerIdx);

        // If no answer, flush confirmation prompt
        if (!$answer) editor_Utils::jconfirm(is_array($msg) ? join('<br>', $msg) : $msg, $buttons);

        // If answer is 'cancel' - stop request processing
        else if ($answer == 'cancel') $this->jflush(false, $cancelMsg);

        // Return answer
        return editor_Utils::$answer[count(editor_Utils::$answer)] = $answer;
    }

    /**
     * Alias for editor_Utils::jflush()
     *
     * @param $success
     * @param string $msg
     * @return mixed
     */
    public function jflush($success, $msg = '') {
        return forward_static_call_array(['editor_Utils', 'jflush'], func_get_args());
    }

    /**
     * If request contains json-encoded 'data'-param, decode it and append to request params
     * This may happen while running tests
     *
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Mismatch
     * @throws Zend_Db_Statement_Exception
     */
    public function handleData() {

        // If request contains json-encoded 'data'-param, decode it and append to request params
        if ($data = $this->jcheck(['data' => ['rex' => 'json']])['data'] ?? 0) {
            $this->getRequest()->setParams(
                $this->getRequest()->getParams() + (array) $data
            );
        }
    }

    /**
     * Get [attrId => readonly] pairs for the current user
     *
     * @param array $attrIds
     * @param bool $canDeleteOwn Flag indicating whether current user can't delete any attributes, but can delete own ones
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getReadonlyFlags(array $attrIds, bool $canDeleteOwn = true) : array {

        // Collect rights
        $rights = [];
        foreach (['propose', 'review', 'finalize'] as $right) {
            if ($this->isAllowed('editor_term', $right)) {
                $rights []= $right;
            }
        }

        // Get [attrId => readonly] pairs
        return ZfExtended_Factory
            ::get('editor_Models_Terminology_Models_AttributeModel')
            ->getReadonlyByIds(
                $attrIds,
                $canDeleteOwn ? $this->user()->getId() : false,
                $rights
            );
    }

    /**
     * lazy-load and return current user
     *
     * @return ZfExtended_Models_User|null
     */
    public function user() {
        return $this->user = $this->user ?? ZfExtended_Authentication::getInstance()->getUser();
    }
}
