/*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor Javascript GUI and build on ExtJs 4 lib
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
 
 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty
 for any legal issue, that may arise, if you use these FLOSS exceptions and recommend
 to stick to GPL 3. For further information regarding this topic please see the attached 
 license.txt of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
