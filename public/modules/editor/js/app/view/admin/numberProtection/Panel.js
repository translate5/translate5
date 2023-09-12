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
 * Lists and manages the available pricing presets to choose from when creating a task
 */
Ext.define('Editor.view.admin.numberProtection.Panel', {
    extend: 'Ext.panel.Panel',
    requires: [
        'Editor.view.admin.numberProtection.PanelViewModel',
        'Editor.view.admin.numberProtection.PanelController',
        'Editor.view.admin.numberProtection.numberRecognition.Grid',
        'Editor.view.admin.numberProtection.inputMapping.Grid',
        'Editor.view.admin.numberProtection.outputMapping.Grid'
    ],
    alias: 'widget.NumberRecognitionPanel',
    viewModel:{
        type: 'numberProtection.PanelViewModel'
    },
    itemId: 'NumberRecognitionPanel',
    reference: 'NumberRecognitionPanel',
    controller: 'Editor.view.admin.numberProtection.PanelController',
    stateful: true,
    stateId: 'adminNumberRecognitionPanel',
    bind: {
        title: '{l10n.numberProtection.title}'
    },
    glyph: 'f292@FontAwesome5FreeSolid',
    /** @property {string} routePrefix Used to setup routes on different view instances */
    routePrefix: '',
    layout: {
        type: 'fit'
    },

    initConfig: function (instanceConfig) {
        var me = this,
            config = {};

        config.title = me.title;
        config.items = [
            {
                xtype: 'tabpanel',
                region: 'east',
                split: true,
                layout: 'fit',
                itemId: 'numberProtectionTabPanel',
                reference: 'display',
                items:[
                    {
                        xtype: 'NumberRecognitionGrid'
                    },
                    {
                        xtype: 'InputMappingGrid'
                    },
                    {
                        xtype: 'OutputMappingGrid'
                    }
                ]
            }
        ];
        if (instanceConfig) {
            config=me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});