
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

Ext.define('Editor.view.admin.task.PreferencesWindow', {
    extend : 'Ext.window.Window',
    alias : 'widget.adminTaskPreferencesWindow',
    requires: ['Editor.view.admin.task.UserAssoc','Editor.view.admin.task.Preferences'],
    itemId : 'adminTaskPreferencesWindow',
    title : '#UT#Einstellungen zu Aufgabe "{0}"',
    strings: {
        close: '#UT#Fenster schließen'
    },
    height : 600,
    width : 800,
    loadingMask: null,
    layout: 'fit',
    modal : true,
    initComponent : function() {
        var me = this,
            auth = Editor.app.authenticatedUser,
            tabs = [];
        if(auth.isAllowed('editorChangeUserAssocTask')) {
            tabs.push({
                actualTask: me.actualTask,
                xtype: 'adminTaskUserAssoc'
            });
        }
        if(auth.isAllowed('editorUserPrefsTask')) {
            tabs.push({
                actualTask: me.actualTask,
                xtype: 'editorAdminTaskPreferences'
            });
        }
        me.title = Ext.String.format(me.title, me.actualTask.get('taskName'));
        Ext.applyIf(me, {
            items : [{
                xtype: 'tabpanel',
                activeTab: 0,
                items: tabs
            }],
            dockedItems: [{
                xtype : 'toolbar',
                dock : 'bottom',
                layout: {
                    type: 'hbox',
                    pack: 'end'
                },
                items : [{
                    xtype : 'button',
                    iconCls : 'ico-cancel',
                    itemId : 'close-btn',
                    text : me.strings.close
                }]
            }]
        });

        me.callParent(arguments);
    },
    /**
     * setting a loading mask for the window / grid is not possible, using savingShow / savingHide instead.
     * perhaps because of bug for ext-4.0.7 (see http://www.sencha.com/forum/showthread.php?157954)
     * This Fix is better as in {Editor.view.changealike.Window} because of useing body as LoadMask el.
     */
    loadingShow: function() {
        var me = this;
        if(!me.loadingMask) {
            me.loadingMask = new Ext.LoadMask(Ext.getBody(), {store: false});
            me.on('destroy', function(){
                me.loadingMask.destroy();
            });
        }
        me.loadingMask.show();
    },
    loadingHide: function() {
        this.loadingMask.hide();
    }
});
