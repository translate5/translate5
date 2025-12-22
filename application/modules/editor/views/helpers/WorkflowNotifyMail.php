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

/**
 * SECTION TO INCLUDE PROGRAMMATIC LOCALIZATION
 * ============================================
 * $translate->_('Nachname');
 * $translate->_('Vorname');
 * $translate->_('E-Mail Adresse');
 * $translate->_('Rolle');
 * $translate->_('Status');
 * $translate->_('Deadline Datum');
 */

/**
 * Utility functions usable in workflow notification E-Mails.
 *
 * @property ZfExtended_View $view
 */
class View_Helper_WorkflowNotifyMail extends Zend_View_Helper_Abstract
{
    public function workflowNotifyMail()
    {
        return $this;
    }

    /**
     * render the HTML user list table
     * @return string
     */
    public function renderUserList(array $users, string $receiverUserGuid = null)
    {
        // anonymize users?
        /** @var editor_Models_Task $task */
        $task = $this->view->task;

        $notifyConfig = $task->getConfig()->runtimeOptions->editor->notification;
        $columns = $notifyConfig->userListColumns->toArray();

        /** @var string|null $receiverLocale */
        // @phpstan-ignore-next-line
        $receiverLocale = $this->view->receiver?->locale ?? null;

        $taskGuid = $task->getTaskGuid();

        /** @var string[] $receiverLocale */
        // @phpstan-ignore-next-line
        $rolesOfReceiver = is_string($this->view->receiver->roles) ? explode(',', $this->view->receiver->roles) : $this->view->receiver->roles;
        if ($task->anonymizeUsers(true, $rolesOfReceiver)) {
            // = anonymize $users for task without taking the addressed user into account
            // (the receiver of the mail might not be the currently authenticated user)
            $workflowAnonymize = ZfExtended_Factory::get(editor_Workflow_Anonymize::class);
            foreach ($users as &$user) {
                $user = $workflowAnonymize->anonymizeUserdata($taskGuid, $user['userGuid'], $user, $receiverUserGuid);
            }
        }
        //reset the tmp user variable
        unset($user);

        $firstUser = reset($users);
        $hasState = ! empty($firstUser) && array_key_exists('state', $firstUser);
        $hasRole = ! empty($firstUser) && array_key_exists('role', $firstUser);
        /** @var ZfExtended_Zendoverwrites_Translate $t */
        $t = $this->view->translate;
        $result = ['<table cellpadding="4">'];
        $th = '<th align="left">';
        $result[] = '<tr>';

        $colHeads = [
            'surName' => 'Nachname',
            'firstName' => 'Vorname',
            'email' => 'E-Mail Adresse',
            'role' => 'Rolle',
            'state' => 'Status',
            'deadlineDate' => 'Deadline Datum',
        ];

        if (! $hasRole) {
            //remove 'role' from $columns;
            $columns = array_diff($columns, ['role']);
        }
        if (! $hasState) {
            //remove 'state' from $columns;
            $columns = array_diff($columns, ['state']);
        }

        foreach ($columns as $col) {
            $result[] = $th . $t->_($colHeads[$col]) . '</th>';
        }
        $result[] = '</tr>';

        //fields to be translated for the receiver
        $translateFieldValues = ['state', 'role'];

        foreach ($users as $user) {
            $result[] = "\n" . '<tr>';
            foreach ($columns as $col) {
                $val = $user[$col] ?? '';
                if (in_array($col, $translateFieldValues) && ! empty($val)) {
                    //translate the value for the receiver locale
                    $val = $t->_($user[$col], $receiverLocale);
                }
                $result[] = '<td>' . $val . '</td>';
            }
            $result[] = '</tr>';
        }
        $result[] = '</table>';

        return join('', $result);
    }

    /**
     * returns an array with translated language names used in the given task
     * The result is ready to be used in mail templates
     *
     * @return array
     */
    public function getTaskLanguages(editor_Models_Task $task)
    {
        $lang = ZfExtended_Factory::get(editor_Models_Languages::class);
        $params = [];

        /** @var ZfExtended_Zendoverwrites_Translate $t */
        $t = $this->view->translate;

        try {
            $lang->load($task->getSourceLang());
            $params['sourceLanguageTranslated'] = $t->_($lang->getLangName());
        } catch (Exception $e) {
            $params['sourceLanguageTranslated'] = 'unknown';
        }

        try {
            $lang->load($task->getTargetLang());
            $params['targetLanguageTranslated'] = $t->_($lang->getLangName());
        } catch (Exception $e) {
            $params['targetLanguageTranslated'] = 'unknown';
        }

        $relais = $task->getRelaisLang();
        if (! empty($relais)) {
            try {
                $lang->load((int) $task->getRelaisLang());
                $params['relaisLanguageTranslated'] = $t->_($lang->getLangName());
            } catch (Exception $e) {
                $params['relaisLanguageTranslated'] = 'unknown';
            }
            $params['relaisLanguageFragment'] = $t->_('<b>Relaissprache:</b> {relaisLanguageTranslated}<br />');
        } else {
            $params['relaisLanguageFragment'] = '';
        }

        return $params;
    }

    /***
     * returns a date in the locale of the receiver
     * @param string/int $date
     * @param boolean $isDateTime: set to true to include the time
     * @return string
     */
    public function dateFormat($date, $isDateTime = false)
    {
        // @phpstan-ignore-next-line
        if (empty($this->view->receiver->locale)) {
            // @phpstan-ignore-next-line
            $locale = $this->view->config->runtimeOptions->translation->fallbackLocale;
        } else {
            $locale = $this->view->receiver->locale;
        }
        $format = $isDateTime ? Zend_Locale_Format::getDateTimeFormat($locale) : Zend_Locale_Format::getDateFormat($locale);
        $date = new Zend_Date($date, Zend_Date::ISO_8601);

        return $date->toString($format);
    }
}
