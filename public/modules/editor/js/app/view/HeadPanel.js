
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
 * @class Editor.view.HeadPanel
 * @extends Ext.Container
 */
Ext.define('Editor.view.HeadPanel', {
    extend: 'Ext.container.Container',
    requires: [
        'Editor.view.HeadPanelViewController',
        'Editor.view.help.HelpButton'
    ],
    alias: 'widget.headPanel',
    controller: 'headPanel',
    region: 'north',
    id: 'head-panel',
    height: 150,
    layout: {
        type: 'fit'
    },
    strings: {
        logout: '#UT# Abmelden',
        readonly: '#UT# - [LESEMODUS]',
        erpModuleButtonText:'#UT#Zurück zum ERP'
    },
    
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                items: [{
                    xtype: 'container',
                    cls: 'head-panel-brand',
                    html: Editor.data.app.branding,
                    flex: 1
                },{
                    xtype: 'applicationInfoPanel'
                },{
                    xtype: 'toolbar',
                    itemId: 'top-menu',
                    cls: 'head-panel-toolbar',
                    ui: 'footer',
                    items: [{
                        xtype: 'tbfill'
                    },{
                        xtype: 'button',
                        hidden: !Editor.app.authenticatedUser.hasRoles(['erp']),
                        text:me.strings.erpModuleButtonText,
                        handler:function (){
                            window.location = '/erp';
                        }
                    },{
                        xtype: 'helpButton',
                        bind: {
                            tooltip: '{l10n.helpButton.tooltip}'
                        }
                    },{
                        xtype: 'button',
                        itemId: 'logoutSingle',
                        text: me.strings.logout
                    }]
                }]
            };
        
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([ config ]);
    }
});