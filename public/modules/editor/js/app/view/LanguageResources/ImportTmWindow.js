
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

Ext.define('Editor.view.LanguageResources.ImportTmWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.importTmWindow',
    itemId: 'importTmWindow',
    requries: [
        'Editor.view.LanguageResources.TmWindowViewController',
    ],
    controller: 'tmwindowviewcontroller',
    strings: {
        file: '#UT#TMX file',
        title: '#UT#Import TMX file',
        importTmx: '#UT#Import additional TM data as TMX file and add the data to the already existing TM',
        importTmxType: '#UT#Please use a TMX file!',
        importSuccess: '#UT#Additional TM data successfully imported!',
        save: '#UT#Save',
        cancel: '#UT#Cancel',
        stripFramingTags: '#UT#Strip framing tags at import',
        stripFramingTagsTooltip: '#UT#Works analogous to system configuration runtimeOptions.import.xlf.ignoreFramingTags, but for TMX import. It strips all (or only paired) tags from the start and end of an imported segment, if activated. The system configuration should therefore have the same setting for the same client for task imports. If you need to convert existing TMs, please ask the translate5 support for help.',
        resegmentTmxTooltip: '#UT#Segmentation rules for the respective language are applied to the source and target text of the segments. If the same number of segments is found, the segments are re-segmented (i.e. further segmented). The segmentation rules that are stored as standard for the selected client are used. If several clients are assigned to the language resource, the rules of the first assigned client are used.',
    },
    height : 300,
    width : 500,
    modal : true,
    layout:'fit',
    viewModel: {
        data: {
            resourceId: null,
            strippingFramingTagsSupported: false,
            resegmentationSupported: false
        },
        formulas: {
            isStrippingFramingTagsSupported: function(get) {
                return get('strippingFramingTagsSupported');
            },
            isResegmentingTmxSupported: function(get) {
                return get('resegmentationSupported');
            }
        }
    },
    initConfig : function(instanceConfig) {
        var me = this,
            defaults = {
                labelWidth: 160,
                anchor: '100%'
            },
            config = {
                title:me.strings.title,
                items : [{
                    xtype: 'form',
                    padding: 5,
                    ui: 'default-frame',
                    defaults: defaults,
                    items: [{
                        ui: 'default-frame',
                        html: me.strings.importTmx,
                        padding: 5
                    },{
                        xtype: 'hiddenfield',
                        name: 'CsrfToken',
                        value: Editor.data.csrfToken
                    },{
                        xtype: 'filefield',
                        fieldLabel: me.strings.file,
                        toolTip: me.strings.importTmx,
                        regex: /(\.tmx|\.zip)$/i,
                        regexText: me.strings.importTmxType,
                        labelWidth: 160,
                        anchor: '100%',
                        vtype:'tmFileUploadSize',
                        name: 'tmUpload'
                    },
                    {
                        xtype: 'combo',
                        itemId: 'stripFramingTags',
                        name: 'stripFramingTags',
                        fieldLabel: me.strings.stripFramingTags,
                        store: new Ext.data.ArrayStore({
                            fields: ['id', 'value'],
                        }),
                        queryMode: 'local',
                        displayField: 'value',
                        valueField: 'id',
                        value: 'none',
                        bind: {
                            disabled: '{!isStrippingFramingTagsSupported}',
                            hidden: '{!isStrippingFramingTagsSupported}'
                        },
                        labelClsExtra: 'lableInfoIcon',
                        autoEl: {
                            tag: 'div',
                            'data-qtip': me.strings.stripFramingTagsTooltip
                        }
                    },
                    {
                        xtype: 'checkbox',
                        bind: {
                            hidden: '{!isResegmentingTmxSupported}',
                            disabled: '{!isResegmentingTmxSupported}',
                            fieldLabel: '{l10n.languageResources.resegmentTmx}'
                        },
                        itemId: 'resegmentTmx',
                        name: 'resegmentTmx',
                        value: false,
                        labelClsExtra: 'lableInfoIcon',
                        autoEl: {
                            tag: 'div',
                            'data-qtip': me.strings.resegmentTmxTooltip
                        }
                    },
                    ]
                }],
                dockedItems : [{
                    xtype : 'toolbar',
                    dock : 'bottom',
                    ui: 'footer',
                    layout: {
                        type: 'hbox',
                        pack: 'start'
                    },
                    items : [{
                        xtype: 'tbfill'
                    },{
                        xtype: 'button',
                        glyph: 'f00c@FontAwesome5FreeSolid',
                        itemId: 'save-tm-btn',
                        text: me.strings.save
                    }, {
                        xtype : 'button',
                        glyph: 'f00d@FontAwesome5FreeSolid',
                        itemId : 'cancel-tm-btn',
                        text : me.strings.cancel
                    }]
                }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    /**
     * loads the record into the form, does set the role checkboxes according to the roles value
     * @param record
     */
    loadRecord: function (record) {
        const me = this;
        me.setTitle(me.strings.title + ': ' + Ext.String.htmlEncode(record.get('name')));
        me.languageResourceRecord = record;
        this.getViewModel().set('resourceId', record.get('resourceId'));
        this.getController().updateStrippingFramingTagsSupport(true);
        this.getController().updateResegmentationSupport(true);
    }
});
