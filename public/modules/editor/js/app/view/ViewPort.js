
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
 * @class Editor.view.ViewPort
 * @extends Ext.container.Viewport
 */
Ext.define('Editor.view.ViewPort', {
    extend: 'Ext.container.Viewport',
    requires: [
        'Editor.view.ViewPortViewModel',
        'Editor.view.MaintenancePanel',
        'Editor.view.admin.user.Grid',
        'Editor.view.admin.TaskGrid',
        'Editor.view.HeadPanel',
        'Editor.view.admin.customer.Panel',
        'Editor.view.LanguageResources.TmOverviewPanel',
        'Editor.view.admin.preferences.OverviewPanel',
        'Editor.view.project.ProjectPanel'
    ],
    viewModel: {
        type: 'viewport'
    },
    layout: 'border',
    initComponent: function() {
        var me = this,
            user = Editor.app.authenticatedUser,
            mainSections = [],
            items = [{
                xtype: 'headPanel',
                region: 'north'
            },{
                region: 'center',
                xtype: 'tabpanel',
                itemId: 'adminMainSection',
                /**
                 * returns the configured default route of the active tab (if any configured)
                 * @returns {string}
                 */
                getActiveTabDefaultRoute: function() {
                    var tab = this.getActiveTab(),
                        ctrl = tab.getController(),
                        conf = ctrl && ctrl.defaultConfig;
                    if(conf && conf.routes) {
                        return Object.keys(conf.routes)[0];
                    }
                    return '';
                },
                //ui: 'navigation', → eigene UI benötigt eigenes CSS! Im Beispiel ist das ja SCSS was noch gerendert werden müsste!
                tabBar: {
                    // turn off borders for classic theme.  neptune and crisp don't need this
                    // because they are borderless by default
                    border: false
                },

                defaults: {
                    iconAlign: 'left',
                },
                
                layout: {
                    type: 'fit'
                },
                items: mainSections
            }];
        
        if(user.isAllowed('editorProjectTask')) {
            mainSections.push({xtype: 'projectPanel'});
        }
        if(user.isAllowed('taskOverviewFrontendController')) {
            mainSections.push({xtype: 'adminTaskGrid'});
        }
        if(user.isAllowed('languageResourcesOverview')) {
            mainSections.push({xtype: 'tmOverviewPanel'});
        }
        if(user.isAllowed('userAdministration')) {
            mainSections.push({xtype: 'adminUserGrid'});
        }
        if(user.isAllowed('customerAdministration')) {
            mainSections.push({xtype: 'customerPanel'});
        }
        //the preferences panel is responsible for itself if it is visible or not!
        mainSections.push({xtype: 'preferencesOverviewPanel'});

        Ext.applyIf(me, {
            items: items
        });
        me.callParent(arguments);
    }
  });