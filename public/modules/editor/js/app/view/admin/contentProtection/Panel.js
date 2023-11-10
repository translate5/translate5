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
Ext.define('Editor.view.admin.contentProtection.Panel', {
    extend: 'Ext.panel.Panel',
    requires: [
        'Editor.view.admin.contentProtection.PanelViewModel',
        'Editor.view.admin.contentProtection.PanelController',
        'Editor.view.admin.contentProtection.contentRecognition.Grid',
        'Editor.view.admin.contentProtection.inputMapping.Grid',
        'Editor.view.admin.contentProtection.outputMapping.Grid'
    ],
    alias: 'widget.ContentRecognitionPanel',
    viewModel:{
        type: 'contentProtection.PanelViewModel'
    },
    itemId: 'ContentRecognitionPanel',
    reference: 'ContentRecognitionPanel',
    controller: 'Editor.view.admin.contentProtection.PanelController',
    stateful: true,
    stateId: 'adminContentRecognitionPanel',
    bind: {
        title: '{l10n.contentProtection.title}'
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

        // hack to achieve filled values in OutputMapping::name combobox
        Ext.getStore('admin.contentProtection.InputMappingStore').load();

        config.title = me.title;
        config.items = [
            {
                xtype: 'tabpanel',
                region: 'east',
                split: true,
                layout: 'fit',
                itemId: 'contentProtectionTabPanel',
                reference: 'display',
                items:[
                    {
                        xtype: 'ContentRecognitionGrid'
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