
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

Ext.define('Editor.view.admin.preferences.OverviewPanel', {
    extend: 'Ext.tab.Panel',
    requires: [
        'Editor.view.admin.preferences.OverviewPanelViewController',
        'Editor.view.admin.preferences.User',
        'Editor.view.admin.config.Grid',
        'Editor.view.admin.log.SystemGrid'
    ],
    alias: 'widget.preferencesOverviewPanel',
    itemId: 'preferencesOverviewPanel',
    controller: 'preferencesOverviewPanel',
    stateId: 'preferencesOverviewPanel',
    helpSection: 'preferences',
    stateful: true,
    title: '#UT#Einstellungen',
    defaults: {
        iconAlign: 'left',
        textAlign: 'left'
    },
    tabPosition: 'left',
    tabRotation: 0,
    glyph: 'xf085@FontAwesome5FreeSolid',
    listeners:{
        tabchange: 'onTabChange',
        beforeshow: function(view, opts){
            this.forwardActiveTabEvent('beforeshow');
        },
        show: function(view, opts){
            this.forwardActiveTabEvent('show');
        },
        activate: function(view, opts){
            this.forwardActiveTabEvent('activate');
            this.handleConfigPanelReadOnly();
        },
        beforehide: function(view, opts){
            this.forwardActiveTabEvent('beforehide');
        },
        hide: function(view, opts){
            this.forwardActiveTabEvent('hide');
        },
        deactivate: function(view, opts){
            this.forwardActiveTabEvent('deactivate');
        }
    },
    forwardActiveTabEvent: function(name){
        var at = this.getActiveTab();
        if(at){
            at.fireEvent('tab'+name, at);
        }
    },
    initConfig: function(instanceConfig) {
        var me = this,
            user = Editor.app.authenticatedUser,
            configSections = [],
            config = {
                title: me.title, //see EXT6UPD-9
                items: configSections,
                hidden: true //is enabled if there are children
            };
        
        if(user.isAllowed('userPrefFrontendController')) {
            configSections.push({xtype: 'preferencesUser'});
        }
        
        if(user.isAllowed('configOverwriteGrid')) {
            configSections.push({
                xtype: 'adminConfigGrid'
            });
        }

        if(user.isAllowed('systemLog')) {
            configSections.push({
                xtype: 'editorSystemLogGrid'
            });
        }

        /**
         * Other planned config sections:
         * [{
                xtype: 'panel',
                title: 'System',
                glyph: 'xf013@FontAwesome',
            }]
         */
        
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    initComponent: function() {
        var me = this;
        me.callParent(arguments);
        me.setVisible(me.items.length > 0); //if there are any sub modules
    },
    
    /***
     * Trigger the viewmodel binding for hasReadOnly propertie
     */
    handleConfigPanelReadOnly:function(){
        var me=this,
            configPanel = me.down('adminConfigGrid');
        configPanel && configPanel.getController().handleHasReadOnly();
    }
});