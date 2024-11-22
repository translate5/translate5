<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Plugins\Okapi\Bconf\Filter;

/**
 * Class representing updates to v1.47
 * Supported FPRMs: okf_idml, okf_openxml, okf_html
 */
final class FprmUpdaterTo147
{
    private const ADDED_PROPERTIES = [
        'okf_idml' => 'extractHyperlinkTextSourcesInline.b=false
extractExternalHyperlinks.b=false
specialCharacterPattern= | | | | | | | | | |​|‌|­|‑|﻿
codeFinderRules.count.i=0
codeFinderRules.sample=
codeFinderRules.useAllRulesWhenTesting.b=true',
        'okf_openxml' => 'translatePowerpointDocProperties.b=true
translatePowerpointDiagramData.b=true
translatePowerpointCharts.b=true
translatePowerpointComments.b=true
reorderPowerpointDocProperties.b=false
reorderPowerpointDiagramData.b=false
reorderPowerpointCharts.b=false
bPreferenceReorderPowerpointNotes.b=false
reorderPowerpointComments.b=false
reorderPowerpointRelationships.b=false
translateWordNumberingLevelText.b=false
allowWordStyleOptimisation.b=true
ignoreWordFontColors.b=false
translateExcelCellsCopied.b=true
preserveExcelStylesInTargetColumns.b=false',
    ];

    private array $addPropertyData = [];

    private const removePropertyData = [
        'okf_openxml' => ['bPreferenceTranslateExcelExcludeColumns.b', 'tsExcelExcludedColumns.i'],
    ];

    private const replaceYamlData = [
        'okf_html' => [
            "[keywords, description]" => "[keywords, description, 'twitter:title', 'twitter:description', 'og:title', 'og:description', 'og:site_name']",
            ".*:
    ruleTypes: [EXCLUDE]
    conditions: [translate, EQUALS, 'no']" => ".*:
    ruleTypes: [EXCLUDE]
    conditions: [translate, EQUALS, no]
  .+:
    ruleTypes: [INCLUDE]
    conditions: [translate, EQUALS, yes]",
        ],
    ];

    public function __construct()
    {
        foreach (array_keys(self::ADDED_PROPERTIES) as $okfType) {
            $this->addPropertyData[$okfType] = [];
            foreach (preg_split("/[\r\n]+/", self::ADDED_PROPERTIES[$okfType]) as $line) {
                $line = explode('=', $line);
                $this->addPropertyData[$okfType][$line[0]] = $line[1];
            }
        }
    }

    public function updateInDir(string $dir): void
    {
        $json = json_decode(file_get_contents($dir . '/content.json'), true);
        if (empty($json['fprm'])) {
            return;
        }
        foreach ($json['fprm'] as $fprmEntry) {
            [$okfType] = explode('@', $fprmEntry);

            if (! isset($this->addPropertyData[$okfType]) && ! isset(self::replaceYamlData[$okfType]) && ! isset(self::removePropertyData[$okfType])) {
                continue;
            }
            if (! file_exists($fprmFile = $dir . '/' . $fprmEntry . '.fprm')) {
                continue;
            }
            $fileContents = rtrim(file_get_contents($fprmFile));
            $fileContentsNew = $fileContents;
            if (! empty($this->addPropertyData[$okfType])) {
                foreach ($this->addPropertyData[$okfType] as $propName => $propValue) {
                    if (! preg_match('/^' . preg_quote($propName) . '=/m', $fileContentsNew)) {
                        $fileContentsNew .= "\n$propName=$propValue";
                    }
                }
            }
            if (! empty(self::removePropertyData[$okfType])) {
                foreach (self::removePropertyData[$okfType] as $propName) {
                    $fileContentsNew = preg_replace('/^' . preg_quote($propName) . '=.*?(\n|$)/m', '', $fileContentsNew);
                }
            }
            if (! empty(self::replaceYamlData[$okfType])) {
                foreach (self::replaceYamlData[$okfType] as $str1 => $str2) {
                    $fileContentsNew = str_replace($str1, $str2, $fileContentsNew);
                }
            }
            if ($fileContentsNew !== $fileContents) {
                // temp file for Fprm:validate() only
                $fprmTempFile = $fprmFile . '.tmp';
                file_put_contents($fprmTempFile, $fileContentsNew);

                $fprmNameWithinData = substr($dir, strpos($dir, '/data/') + 1);
                $fprm = new Fprm($fprmTempFile);
                if ($fprm->validate()) {
                    // replace old file
                    file_put_contents($fprmFile, $fileContentsNew);
                    $errMsg = "Successfully converted FPRM to OKAPI 1.47: $fprmNameWithinData";
                } else {
                    $errMsg = "Error converting FPRM to OKAPI 1.47: " . $fprm->getValidationError() . " [$fprmNameWithinData]";
                }
                error_log($errMsg);
                unlink($fprmTempFile);
            }
        }
    }
}
