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

useCustomParser.b=true
factoryClass=com.ctc.wstx.stax.WstxInputFactory
fallbackToID.b=false
escapeGT.b=false
addTargetLanguage.b=true
overrideTargetLanguage.b=false
outputSegmentationType.i=0
ignoreInputSegmentation.b=false
addAltTrans.b=false
addAltTransGMode.b=true
editAltTrans.b=false
includeExtensions.b=true
includeIts.b=true
allowEmptyTargets.b=false
targetStateMode.i=0
targetStateValue=needs-translation
useTranslationTargetState.b=true
alwaysUseSegSource.b=false
quoteModeDefined.b=true
quoteMode.i=0
useSdlXliffWriter.b=false
preserveSpaceByDefault.b=false
useSegsForSdlProps.b=false
useIwsXliffWriter.b=false
iwsBlockFinished.b=true
iwsTransStatusValue=finished
iwsTransTypeValue=manual_translation
iwsRemoveTmOrigin.b=false
iwsBlockLockStatus.b=false
iwsBlockTmScore.b=false
iwsBlockTmScoreValue=100.00
iwsIncludeMultipleExact.b=false
iwsBlockMultipleExact.b=false
inlineCdata.b=false
skipNoMrkSegSource.b=false
useCodeFinder.b=false
subAsTextUnit.b=false
alwaysAddTargets.b=false
forceUniqueIds.b=false
codeFinderRules.count.i=1
codeFinderRules.rule0=</?([A-Z0-9a-z]*)\b[^>]*>
codeFinderRules.sample=&name; <tag></at><tag/> <tag attr='val'> </tag="val">
codeFinderRules.useAllRulesWhenTesting.b=true
cdataSubfilter=
pcdataSubfilter=

 see /okapi/filters/xliff/src/main/java/net/sf/okapi/filters/xliff/Parameters.java
  */
Ext.define('Editor.plugins.Okapi.view.fprm.Xliff', {
    extend: 'Editor.plugins.Okapi.view.fprm.Properties',
    width: 900,
    fieldDefinitions: {
        /* Options */
        'tabOptions': { type: 'tab', icon: 'fa-cog', children: {
            "fallbackToID.b": {},
            "ignoreInputSegmentation.b": {},
            "alwaysUseSegSource.b": {},
            "useSdlXliffWriter.b": {},
            "escapeGT.b": {},
            "addTargetLanguage.b": {},
            "overrideTargetLanguage.b": {},
            "allowEmptyTargets.b": {},
            "preserveSpaceByDefault.b": {},
            "inlineCdata.b": {},
            "skipNoMrkSegSource.b": {},
            "outputSegmentationType.i": { type: 'radio', children: {
                'outputSegmentationType0': {},
                'outputSegmentationType1': {},
                'outputSegmentationType2': {},
                'outputSegmentationType3': {}
            }},
            "includeIts.b": {},
            "addAltTrans.b": {},
            "useCustomParser.b": {},
            "factoryClass": {},
            "useTranslationTargetState.b": {},
            "alwaysAddTargets.b": {},
            "forceUniqueIds.b": {},
            "useIwsXliffWriter.b": {},
            "iwsBlockFinished.b": {},
            "iwsTransStatusValue": {},
            "iwsTransTypeValue": {},
            "iwsRemoveTmOrigin.b": {},
            "iwsBlockLockStatus.b": {},
            "iwsBlockTmScore.b": {},
            "iwsBlockTmScoreValue": {},
            "iwsIncludeMultipleExact.b": {},
            "iwsBlockMultipleExact.b": {},
            // absent in UI
            'targetStateMode.i': { config: { hidden: true, valueDefault: 0 }},
            'targetStateValue': { config: { hidden: true, valueDefault: 'needs-translation' }},
            'quoteModeDefined.b': { config: { hidden: true, valueDefault: true }},
            'quoteMode.i': { config: { hidden: true, valueDefault: 0 }},
            'useSegsForSdlProps.b': { config: { hidden: true, valueDefault: false }},
            'subAsTextUnit.b': { config: { hidden: true, valueDefault: false }},
            // marked as deprecated in filters/xliff/ui/Res.properties
            'addAltTransGMode.b': { config: { hidden: true, valueDefault: true }},
            'editAltTrans.b': { config: { hidden: true, valueDefault: false }},
            'includeExtensions.b': { config: { hidden: true, valueDefault: true }}
        }},
        /* Content Processing */
        'tabContentProcessing': { type: 'tab', icon: 'fa-cogs', children: {
            'cdataSubfilter': {},
            'pcdataSubfilter': {},
            // 4 params below are hidden until "Use codefinder" functionality is implemented
            'useCodeFinder.b': { config: { hidden: true, valueDefault: false }},
            'codeFinderRules.count.i': { config: { hidden: true, valueDefault: 1 }},
            'codeFinderRules.rule0': { config: { hidden: true, valueDefault: '</?([A-Z0-9a-z]*)\b[^>]*>' }},
            'codeFinderRules.sample': { config: { hidden: true, valueDefault: '&name; <tag></at><tag/> <tag attr=\'val\'> </tag="val">' }},
            'codeFinderRules.useAllRulesWhenTesting.b': { config: { hidden: true, valueDefault: true }},
        }}
    }
});