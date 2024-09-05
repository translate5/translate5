
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
        file: '#UT#TMX-Datei',
        title: '#UT#TMX Datei importieren',
        importTmx: '#UT#Weitere TM Daten in Form einer TMX Datei importieren und dem bestehenden TM hinzufügen',
        importTmxType: '#UT#Bitte verwenden Sie eine TMX Datei!',
        importSuccess: '#UT#Weitere TM Daten erfolgreich importiert!',
        save: '#UT#Speichern',
        cancel: '#UT#Abbrechen',
        stripFramingTags: '#UT#Umschließende Tags beim Import löschen',
        stripFramingTagsTooltip: '#UT#Arbeitet analog zur Systemkonfiguration runtimeOptions.import.xlf.ignoreFramingTags, aber für TMX-Import. Sie entfernt alle (oder nur gepaarte) Tags vom Anfang und Ende eines importierten Segments, falls aktiviert. Die Systemkonfiguration sollte daher für denselben Kunden die gleiche Einstellung für zu importierende Aufgaben haben. Wenn Sie bestehende TMs konvertieren müssen, bitten Sie den translate5-Support um Hilfe.',
    },
    height : 300,
    width : 500,
    modal : true,
    layout:'fit',
    viewModel: {
        data: {
            resourceId: null,
            strippingFramingTagsSupported: false
        },
        formulas: {
            isStrippingFramingTagsSupported: function(get) {
                return get('strippingFramingTagsSupported');
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
                    }]
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
    }
});
