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

/**

maxValidation.b=true
forceUniqueIds.b=false
ignoreTagTypeMatch.b=false
discardInvalidTargets.b=false
writeOriginalData.b=true
simplifyTags.b=false
needsSegmentation.b=false
useCodeFinder.b=false
codeFinderRules.count.i=1
codeFinderRules.rule0=</?([A-Z0-9a-z]*)\b[^>]*>
codeFinderRules.sample=&name; <tag></at><tag/> <tag attr='val'> </tag="val">
codeFinderRules.useAllRulesWhenTesting.b=true
subfilter=
subfilterOverwriteTarget.b=false

 see /okapi/filters/xliff2/src/main/java/net/sf/okapi/filters/xliff2/Parameters.java
 */
Ext.define('Editor.plugins.Okapi.view.fprm.Xliff2', {
    extend: 'Editor.plugins.Okapi.view.fprm.Properties',
    width: 700,
    fieldDefinitions: {
        'maxValidation.b': {},
        'forceUniqueIds.b': {},
        'ignoreTagTypeMatch.b': {},
        'discardInvalidTargets.b': {},
        'writeOriginalData.b': {},
        'subfilter': { config: { hasTooltip: true }},
        'subfilterOverwriteTarget.b': { config: { hasTooltip: true }},
        'simplifyTags.b': { config: { hidden: true, valueDefault: false }},
        'needsSegmentation.b': { config: { hidden: true, valueDefault: false }},
        // 4 params below are hidden until "Use codefinder" functionality is implemented
        'useCodeFinder.b': { config: { hidden: true, valueDefault: false }},
        'codeFinderRules.count.i': { config: { hidden: true, valueDefault: 1 }},
        'codeFinderRules.rule0': { config: { hidden: true, valueDefault: '</?([A-Z0-9a-z]*)\b[^>]*>' }},
        'codeFinderRules.sample': { config: { hidden: true, valueDefault: '&name; <tag></at><tag/> <tag attr=\'val\'> </tag="val">' }},
        'codeFinderRules.useAllRulesWhenTesting.b': { config: { hidden: true, valueDefault: true }}
    }
});