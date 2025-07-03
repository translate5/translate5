/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

 maxAttributeSize.i=4194304
 untagXmlStructures.b=true
 extractNotes.b=false
 extractMasterSpreads.b=true
 extractHiddenLayers.b=false
 extractHiddenPasteboardItems.b=false
 skipDiscretionaryHyphens.b=false
 extractBreaksInline.b=false
 ignoreCharacterKerning.b=true
 ignoreCharacterTracking.b=true
 ignoreCharacterLeading.b=true
 ignoreCharacterBaselineShift.b=true
 extractHyperlinkTextSourcesInline.b=false
 extractCustomTextVariables.b=false
 extractIndexTopics.b=false
 extractExternalHyperlinks.b=false
 specialCharacterPattern= | | | | | | | | | |​|‌|­|‑|﻿
 codeFinderRules.count.i=0
 codeFinderRules.sample=
 codeFinderRules.useAllRulesWhenTesting.b=true

 // optional string-params that actually must be validated as floats
 characterTrackingMaxIgnoranceThreshold=63636.0
 characterBaselineShiftMinIgnoranceThreshold=1.345
 characterKerningMaxIgnoranceThreshold=44.44
 characterLeadingMaxIgnoranceThreshold=66.0
 characterBaselineShiftMaxIgnoranceThreshold=753475.0
 characterKerningMinIgnoranceThreshold=33.33
 characterLeadingMinIgnoranceThreshold=75375.0
 characterTrackingMinIgnoranceThreshold=6364.0

 see /okapi/filters/idml/src/main/java/net/sf/okapi/filters/idml/Parameters.java
 */
Ext.define('Editor.plugins.Okapi.view.fprm.Idml', {
    extend: 'Editor.plugins.Okapi.view.fprm.Properties',
    requires: [
        'Editor.plugins.Okapi.view.fprm.component.Grid',
        'Editor.plugins.Okapi.view.fprm.component.Codefinder'
    ],
    width: 900,
    formPanelLayout: 'fit',
    formPanelPadding: 2,
    grid: null,
    codefinder: null,
    fieldDefinitions: {
        'tabGeneral': { type: 'tab', icon: 'fa-cog', children: {
            'maxAttributeSize.i': {},
            'untagXmlStructures.b': {},
            'extractNotes.b': {},
            'extractMasterSpreads.b': {},
            'extractHiddenLayers.b': {},
            'extractHiddenPasteboardItems.b': {},
            'skipDiscretionaryHyphens.b': {},
            'extractBreaksInline.b': {},
            'extractCustomTextVariables.b': {},
            'extractIndexTopics.b': {},
            'extractHyperlinkTextSourcesInline.b': {},
            'extractExternalHyperlinks.b': {},
            'specialCharacterPattern': { config: { hasTooltip: true }}
        }},
        'tabCodeFinder': { type: 'tab', icon: 'fa-tags', children: {
            'codeFinderRules': { type: 'codefinder' }
        }},
        'tabFormatting': { type: 'tab', icon: 'fa-cogs', children: {
            'ignoreCharacterKerning.b': { type: 'boolset', children: {
                'characterKerningMinIgnoranceThreshold': { config: { valueType: 'float', ignoreEmpty: true }},
                'characterKerningMaxIgnoranceThreshold': { config: { valueType: 'float', ignoreEmpty: true }}
            }},
            'ignoreCharacterTracking.b': { type: 'boolset', children: {
                'characterTrackingMinIgnoranceThreshold': { config: { valueType: 'float', ignoreEmpty: true }},
                'characterTrackingMaxIgnoranceThreshold': { config: { valueType: 'float', ignoreEmpty: true }}
            }},

            'ignoreCharacterLeading.b': { type: 'boolset', children: {
                'characterLeadingMinIgnoranceThreshold': { config: { valueType: 'float', ignoreEmpty: true }},
                'characterLeadingMaxIgnoranceThreshold': { config: { valueType: 'float', ignoreEmpty: true }}
            }},

            'ignoreCharacterBaselineShift.b': { type: 'boolset', children: {
                'characterBaselineShiftMinIgnoranceThreshold': { config: { valueType: 'float', ignoreEmpty: true }},
                'characterBaselineShiftMaxIgnoranceThreshold': { config: { valueType: 'float', ignoreEmpty: true }}
            }}
        }},
        'tabExcludedStyles': { type: 'tab', icon: 'fa-minus-circle', children: {
            'excludedStyleConfigurations': { type: 'grid', config: {
                cols: [{
                    itemId: 'namePattern',
                    editor: {
                        allowBlank: false
                    }
                }]
            }}
        }},
        'tabFontMappings': { type: 'tab', icon: 'fa-font', children: {
            'fontMappings': { type: 'grid', config: {
                cols: [{
                    itemId: 'sourceLocalePattern'
                },{
                    itemId: 'targetLocalePattern'
                },{
                    itemId: 'sourceFontPattern'
                },{
                    itemId: 'targetFont',
                    flex: 0.75
                }]
            }}
        }}
    },
    /**
     * Overridden to resolve our dependencies
     */
    resolveFieldDependencies: function(){
        // if the checkbox is not set we need to remove the related Kerning-Values
        if(this.form.findField('ignoreCharacterKerning.b').getValue() === false){
            this.form.findField('characterKerningMinIgnoranceThreshold').setRawValue(null);
            this.form.findField('characterKerningMaxIgnoranceThreshold').setRawValue(null);
        }
        if(this.form.findField('ignoreCharacterTracking.b').getValue() === false){
            this.form.findField('characterTrackingMinIgnoranceThreshold').setRawValue(null);
            this.form.findField('characterTrackingMaxIgnoranceThreshold').setRawValue(null);
        }
        if(this.form.findField('ignoreCharacterLeading.b').getValue() === false){
            this.form.findField('characterLeadingMinIgnoranceThreshold').setRawValue(null);
            this.form.findField('characterLeadingMaxIgnoranceThreshold').setRawValue(null);
        }
        if(this.form.findField('ignoreCharacterBaselineShift.b').getValue() === false){
            this.form.findField('characterBaselineShiftMinIgnoranceThreshold').setRawValue(null);
            this.form.findField('characterBaselineShiftMaxIgnoranceThreshold').setRawValue(null);
        }
    },

    addCustomFieldControl: function (data, id, name, config, holder, disabled) {
        if (data.type === 'grid') {
            if (this.grid === null) {
                this.grid = Ext.create('Editor.plugins.Okapi.view.fprm.component.Grid', this);
            }
            return this.grid.addCustomFieldControl(data, id, name, config, holder, disabled);
        }
        if (data.type === 'codefinder') {
            if (this.codefinder === null) {
                this.codefinder = Ext.create('Editor.plugins.Okapi.view.fprm.component.Codefinder', this);
            }
            return this.codefinder.addCustomFieldControl(data, id, name, config, holder, disabled);
        }
        throw new Error('addCustomFieldControl: unknown field type "'+data.type+'"');
    },

    getFormValues: function () {
        let vals = this.callParent();
        if (this.grid !== null) {
            vals = this.grid.getFormValues(vals);
        }
        if (this.codefinder !== null) {
            vals = this.codefinder.getFormValues(vals);
        }
        return vals;
    }

});