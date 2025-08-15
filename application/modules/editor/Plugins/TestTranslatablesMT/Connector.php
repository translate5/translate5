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

use MittagQI\Translate5\LanguageResource\Status;

class editor_Plugins_TestTranslatablesMT_Connector extends editor_Services_Connector_Abstract
{
    protected $tagHandlerClass = editor_Services_Connector_TagHandler_Xliff::class;

    public function __construct()
    {
        parent::__construct();
        $this->defaultMatchRate = 100;
    }

    public function query(editor_Models_Segment $segment)
    {
        $queryString = $this->getQueryStringAndSetAsDefault($segment);
        $queryString = $this->tagHandler->prepareQuery($queryString);
        $results = [$this->replaceCharacters($queryString)];

        if (empty($results)) {
            return $this->resultList;
        }

        foreach ($results as $result) {
            $this->resultList->addResult($this->tagHandler->restoreInResult($result), $this->defaultMatchRate);
            $this->resultList->setSource($this->tagHandler->restoreInResult($queryString));
        }

        return $this->resultList;
    }

    public function translate(string $searchString)
    {
        if ($searchString === '') {
            return $this->resultList;
        }

        $allResults = [$this->replaceCharacters($searchString)];

        if (empty($allResults)) {
            return $this->resultList;
        }

        $this->resultList->setDefaultSource($searchString);

        foreach ($allResults as $result) {
            $this->resultList->addResult($result, $this->defaultMatchRate);
        }

        return $this->resultList;
    }

    private function replaceCharacters(string $searchString): string
    {
        $tokens = preg_split('/(<[^>]+>)/i', $searchString, flags: PREG_SPLIT_DELIM_CAPTURE);

        foreach ($tokens as &$token) {
            if (str_starts_with($token, '<')) {
                continue;
            }

            $chars = mb_str_split(html_entity_decode($token));

            foreach ($chars as &$char) {
                if (ctype_space($char) || ctype_punct($char)) {
                    continue;
                }

                $char = '$';
            }

            $token = htmlentities(implode('', $chars));
        }

        return implode('', $tokens);
    }

    public function getStatus(
        editor_Models_LanguageResources_Resource $resource,
        editor_Models_LanguageResources_LanguageResource $languageResource = null
    ): string {
        return Status::AVAILABLE;
    }
}
