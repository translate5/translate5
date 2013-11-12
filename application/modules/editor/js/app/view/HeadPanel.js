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
/**
 * @class Editor.view.HeadPanel
 * @extends Ext.Container
 */
Ext.define('Editor.view.HeadPanel', {
    extend: 'Ext.container.Container',
    alias: 'widget.headPanel',
    region: 'north',
    id: 'head-panel',
    strings: {
        task: '#UT#Aufgabe',
        logout: '#UT# Abmelden',
        tasks: '#UT#Aufgaben',
        settings: '#UT# Meine Einstellungen',
        loggedinAs: '#UT# Eingeloggter Benutzer',
        loginName: '#UT# Loginname',
        back: '#UT#zurück zur Aufgabenliste',
        finishBtn: '#UT#Aufgabe abschließen',
        endBtn: '#UT#Aufgabe beenden',
        readonly: '#UT# - schreibgeschützt'
    },
    infoTpl: [
                  '<div class="info-line"><span class="user-label">{userLabel}:</span> <span class="user-name">{user.firstName} {user.surName}</span></div>',
                  '<div class="info-line"><span class="login-label">{loginLabel}:</span> <span class="user-login">{user.login}</span></div>',
                  '<tpl if="task">',
                  '<div class="info-line"><span class="task-label">{taskLabel}:</span> <span class="task-name">{task.taskName}</span>',
                  '</tpl>',
                  '<tpl if="isReadonly">',
                  '<span class="task-readonly">{readonlyLabel}</span>',
                  '</tpl>',
                  '<tpl if="task">',
                  '</div>',
                  '</tpl>'
                  ],
    initComponent: function() {
        var me = this,
            isEditor = false; //FIXME Thomas initial value differs for example for ITL

        Ext.applyIf(me, {
            items: [{
                    xtype: 'container',
                    cls: 'head-panel-brand',
                    html: Editor.data.app.branding,
                    flex: 1
                },{
                    xtype: 'container',
                    cls: 'head-panel-info-panel',
                    tpl: me.infoTpl,
                    itemId: 'infoPanel'
                },{
                    xtype: 'toolbar',
                    itemId: 'top-menu',
                    cls: 'head-panel-toolbar',
                    ui: 'footer',
                    items: [{
                        xtype: 'tbfill'
                    },{
                        xtype: 'button',
                        hidden: true, //FIXME nextRelease Thomas diesen Button erst im zweiten Schritt
                        text: me.strings.settings
                    },{
                        xtype: 'button',
                        text: me.strings.tasks,
                        itemId: 'tasksMenu',
                        hidden: isEditor,
                        menu: {
                            xtype: 'menu',
                            items: [{
                                xtype: 'menuitem',
                                iconCls: 'ico-task-back',
                                itemId: 'backBtn',
                                text: me.strings.back
                            },{
                                xtype: 'menuitem',
                                iconCls: 'ico-task-finish',
                                hidden: true,
                                itemId: 'finishBtn',
                                text: me.strings.finishBtn
                            },{
                                xtype: 'menuitem',
                                hidden: true,
                                iconCls: 'ico-task-end',
                                itemId: 'closeBtn',
                                text: me.strings.endBtn
                            }]
                        }
                    },{
                        xtype: 'button',
                        itemId: 'logoutSingle',
                        text: me.strings.logout
                    }]
                }
            ]
        });
        me.callParent(arguments);
    }
});