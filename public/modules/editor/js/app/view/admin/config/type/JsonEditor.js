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
 */
Ext.define('Editor.view.admin.config.type.JsonEditor', {
    extend: 'Ext.window.Window',
    alias: 'widget.jsonEditor',
    controller: 'jsonEditor',
    requires: [
        'Editor.view.admin.config.type.JsonEditorController'
    ],

    record: null,
    hideTbar: false,
    readonlyIndex: false,
    valueMaxLength: Number.MAX_VALUE,
    preventSave: false,

    viewModel: {
        data:{
            isJsonValid: true
        }
    },

    /**
     * This statics must be implemented in classes used as custom config editors
     */
    statics: {
        getConfigEditor: function (record) {
            var win = new this(record.isModel ? {record: record} : record);
            win.show();

            //prevent cell editing:
            return null;
        },
        getJsonFieldEditor: function (config) {
            var win = new this(config);
            win.show();

            //prevent cell editing:
            return null;
        },
        renderer: function (value) {
            if(!Ext.isEmpty(value)){
                return Ext.encode(value, null, 4);
            }
            return value;
        }
    },

    initConfig: function (instanceConfig) {
        var me = this,
            config,
            value = '';

        if (instanceConfig.record) {
            try {
                value = JSON.stringify(instanceConfig.record.get('value'), null, 4);
            }catch (e) {
                value = '';
            }
        }

        config = {
            bind: {
                title: '{l10n.configuration.title}'
            },
            height: '50%',
            modal: true,
            width: '50%',
            layout: 'fit',
            bbar: ['->', {
                bind: {
                    text: '{l10n.configuration.save}',
                    disabled: '{!isJsonValid}'
                },
                glyph: 'f00c@FontAwesome5FreeSolid',
                handler: 'onSave'
            }, {
                bind: {
                    text: '{l10n.configuration.cancel}'
                },
                glyph: 'f00d@FontAwesome5FreeSolid',
                handler: 'onCancel'
            }],
            items: {
                xtype: 'textarea',
                value: value,
                enableKeyEvents: true,
                bind:{
                    fieldStyle: '{isJsonValid ? "color:black" : "color:red"}'
                },
                listeners:{
                    keyup: 'validateJsonValue'
                }
            }
        };
        if (instanceConfig) {
            config = me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});
