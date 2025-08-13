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
 * Transfer translated terms back into their TermCollections
 */
class editor_Models_Export_Exported_TransferWorker extends editor_Models_Export_Exported_Worker
{
    protected function validateParameters(array $parameters): bool
    {
        // Get logger
        $logger = Zend_Registry::get('logger')->cloneMe('editor.export');
        /* @var $logger ZfExtended_Logger */

        // If no folderToBeZipped-param given - log error and return false
        if (empty($parameters['folderToGetTbx'])) {
            $logger->error('E1144', 'Exported_Worker: No Parameter "folderToGetTbx" given for worker.');

            return false;
        }

        // Return true
        return true;
    }

    public function setup($taskGuid = null, $parameters = [])
    {
        // Get config runtimeOptions
        $rop = Zend_Registry::get('config')->runtimeOptions;

        // Get worker server origin
        $workerServer = $rop->server->internalURL ?: $rop->server->protocol . $rop->server->name;

        // Init worker
        $this->init($taskGuid, [
            'folderToGetTbx' => $parameters['exportFolder'],
            'cookie' => $parameters['cookie'],
            'url' => $workerServer . APPLICATION_RUNDIR . '/editor/',
            'userId' => $parameters['userId'],
        ]);
    }

    /**
     * Get translated tbx file(s) for the task and import to the corresponsing TermCollection(s)
     */
    protected function doWork(editor_Models_Task $task): void
    {
        // Get params
        $parameters = $this->workerModel->getParameters();

        // Get all exported tbx files
        $tbxA = glob($parameters['folderToGetTbx'] . DIRECTORY_SEPARATOR . 'TermCollection*.tbx');

        $url = $parameters['url'];

        // Api request data
        $data = [
            'format' => 'jsontext',
            'deleteTermsLastTouchedOlderThan' => '',
            'deleteProposalsLastTouchedOlderThan' => '',
        ];

        // Responses
        $json = [];

        // Get target language rfc5646-code
        $targetLangId = $task->getTargetLang();
        $targetLangRfc = ZfExtended_Factory::get('editor_Models_Languages')->load($targetLangId)->rfc5646;

        // Prepare params to spoof/amend inside tbx
        $date = date('Y-10-03 H:i:s');
        $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
        $user->load($parameters['userId']);
        $userGuid = $user->getUserGuid();
        $userName = $user->getUserName();
        $userEmail = $user->getEmail();
        $userRoles = join(',', $user->getRoles());

        // Foreach exported tbx file
        foreach ($tbxA as $idx => $tbx) {
            // If exported tbx file name does not match the pattern - skip
            if (! preg_match('~TermCollection_([0-9]+)_[0-9]+\.tbx$~', $tbx, $m)) {
                continue;
            }

            // Get raw tbx contents
            $raw = file_get_contents($tbx);

            // Update <transacGrp>-nodes with current date and user info who it doing re-import
            $raw = $this->updateTransacGrp($raw, '<termEntry .*?<langSet ', $date, $userName, $userGuid, 'modification');
            $raw = $this->updateTransacGrp($raw, '<langSet .*?<tig', $date, $userName, $userGuid);
            $raw = $this->updateTransacGrp($raw, '<tig.*?</tig>', $date, $userName, $userGuid);

            // Append <refObject id="$userGuid">-node if need, inside <refObjectList type="respPerson">-node if exists
            $raw = $this->appendRefObject($raw, $userGuid, $userName, $userEmail, $userRoles);

            // Spoof rfc5646-code of source language with target language's one
            $raw = preg_replace('~(<langSet.+?xml:lang=")([^"]+)(".*?>)~', '$1' . $targetLangRfc . '$3', $raw);

            // TODO FIXME: use MittagQI\ZfExtended\ApiRequest

            try {
                $client = new ZfExtended_ApiClient($parameters['url'] . 'languageresourceinstance/' . $m[1] . '/import/', $parameters['cookie']);
                $client->setHeaders('Accept', 'application/json');
                $client->setFileUpload($m[0], 'tmUpload', $raw, 'text/xml');
                foreach ($data as $name => $val) {
                    $client->setParameterPost($name, $val);
                }
                $response = $client->request('POST');
                $result = json_decode($response->getBody());
                if (property_exists($result, 'rows')) {
                    $result = $result->rows;
                }
                $json[$idx] = $result;
            } catch (Throwable $e) {
                throw new ZfExtended_Exception('Could not request the translate5 API within translate5, error was: ' . $e->getMessage());
            }
        }
    }

    /**
     * Update transacGrp-nodes found within given $tbx with new date and user info
     *
     * @param string $type Can be 'origination', 'modification' or empty string (by default)
     */
    public function updateTransacGrp(string $tbx, string $wrap, string $date, string $userName, string $userGuid, string $type = ''): string
    {
        $type = $type ? "\s*?<transac type=\"transactionType\">$type</transac>" : '';

        return preg_replace_callback(
            "~$wrap~s",
            fn ($w) => preg_replace_callback(
                "~<transacGrp>$type.*?</transacGrp>~s",
                fn ($m) => preg_replace(
                    '~(target=")[^"]*?("[^>]*?>).*?(</transacNote>.*?<date>).*?(</date>)~s',
                    '${1}' . $userGuid . '${2}' . $userName . '${3}' . $date . '${4}',
                    $m[0]
                ),
                $w[0]
            ),
            $tbx
        );
    }

    /**
     * Append <refObject id="$userGuid">-node if need, inside <refObjectList type="respPerson">-node if exists
     *
     * @param string $tbx Raw tbx contents
     */
    public function appendRefObject(string $tbx, string $userGuid, string $userName, string $userEmail, string $userRole): string
    {
        return preg_replace_callback('~(<refObjectList type="respPerson">)(.*?)(</refObjectList>)~s', fn ($r) =>
            preg_match('~<refObject id="' . preg_quote($userGuid, '~') . '">~', $r[2])
                ? $r[0]
                : $r[1] . $r[2] . "    <refObject id=\"$userGuid\">
                        <item type=\"fn\">$userName</item>
                        <item type=\"email\">$userEmail</item>
                        <item type=\"role\">$userRole</item>
                    </refObject>
                " . $r[3], $tbx);
    }
}
