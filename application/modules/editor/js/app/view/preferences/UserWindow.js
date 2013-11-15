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
 * @class Editor.view.preferences.UserWindow
 * @extends Ext.window.Window
 */
Ext.define('Editor.view.preferences.UserWindow', {
    extend: 'Ext.window.Window',
    height: 274,
    itemId: 'preferencesUserWindow',
    width: 460,
    title: '#UT#Meine Einstellungen',
    modal: true,
    strings: {
        editPassword: '#UT#Passwort ändern',
        password: '#UT#Passwort',
        password_check: '#UT#Passwort Kontrolle',
        passwordMisMatch: '#UT#Die Passwörter stimmern nicht überein!',
        saveBtn: '#UT#speichern',
        cancelBtn: '#UT#Abbrechen'
    },
    layout: 'fit',
    initComponent: function() {
        var me = this;
        Ext.applyIf(me, {
            items: [{
                xtype: 'form',
                frame: true,
                ui: 'default-framed',
                bodyPadding: 10,
                items:[{
                    xtype: 'fieldset',
                    title: me.strings.editPassword,
                    defaultType: 'textfield',
                    defaults: {
                        labelWidth: 160,
                        inputType: 'password',
                        minLength: 8,
                        allowBlank: false,
                        anchor: '100%'
                    },
                    items: [{
                        name: 'passwd',
                        fieldLabel: me.strings.password
                    },{
                        name: 'passwd_check',
                        validator: function(value) {
                            var pwd = this.previousSibling('[name=passwd]');
                            return (value === pwd.getValue()) ? true : me.strings.passwordMisMatch;
                        },
                        fieldLabel: me.strings.password_check
                    }]
                }]
            }],
            dockedItems: [{
                xtype: 'toolbar',
                ui: 'footer',
                dock: 'bottom',
                layout: {
                    pack: 'end',
                    type: 'hbox'
                },
                items: [{
                    xtype: 'button',
                    itemId: 'saveBtn',
                    iconCls: 'ico-setting-save',
                    text: me.strings.saveBtn
                },{
                    xtype: 'button',
                    itemId: 'cancelBtn',
                    iconCls: 'ico-cancel',
                    text: me.strings.cancelBtn
                }]
            }]
        });
        me.callParent(arguments);
    }
});