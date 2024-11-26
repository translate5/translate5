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

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\Okapi\Bconf\Filter;

/**
 * Class representing updates to v1.47
 */
final class FprmUpdaterTo147
{
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

    public function updateInDir(string $bconfDir, string $bconfId, string $bconfName): void
    {
        $json = json_decode(file_get_contents($bconfDir . '/content.json'), true);
        if (empty($json['fprm'])) {
            return;
        }
        $errors = [];
        foreach ($json['fprm'] as $fprmEntry) {
            [$okfType] = explode('@', $fprmEntry);

            if (! file_exists($fprmFile = $bconfDir . '/' . $fprmEntry . '.fprm')) {
                continue;
            }

            $fprm = new Fprm($fprmFile);
            // x-properties: upgrade according to the new defaults
            if ($fprm->getType() === Fprm::TYPE_XPROPERTIES) {
                $validation = new PropertiesValidation($fprmFile);
                $validation->upgrade();
                if ($validation->validate()) {
                    $validation->flush();
                } else {
                    $errors[] = 'Failed to upgrade x-properties FPRM "' . basename($fprmFile) . '"';
                }
            } else {
                // amend selected Yaml-based FPRMs
                if ($fprm->getType() === Fprm::TYPE_YAML && ! empty(self::replaceYamlData[$okfType])) {
                    $fileContents = rtrim(file_get_contents($fprmFile));
                    foreach (self::replaceYamlData[$okfType] as $str1 => $str2) {
                        $fileContents = str_replace($str1, $str2, $fileContents);
                    }
                    $fprm = new Fprm($fprmFile, $fileContents);
                    $fprm->flush();
                }
                // we validate every FPRM
                if (! $fprm->validate()) {
                    $errors[] = 'Invalid FPRM "' . basename($fprmFile) . '"';
                }
            }
        }
        if (! empty($errors)) {
            error_log(
                'ERROR: BCONF "' . $bconfName . '" (id: ' . $bconfId . ') could not be upgraded'
                . ' to OKAPI 1.47, the following errors occured: ' . "\n    " . implode("\n    ", $errors)
            );
        } else {
            // TODO: for production we should remove/comment out this
            error_log('Successfully converted FPRMs to OKAPI 1.47 for BCONF ' . $bconfName);
        }
    }
}
