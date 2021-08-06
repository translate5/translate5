
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
 * @class Editor.view.preferences.UserWindow
 * @extends Ext.window.Window
 */
Ext.define('Editor.view.admin.preferences.User', {
    extend: 'Ext.panel.Panel',
    itemId: 'preferencesUserWindow',
    requires: [
        'Editor.view.admin.preferences.UserViewController'
    ],
    controller: 'preferencesUser',
    alias: 'widget.preferencesUser',
    title: '#UT#Meine Einstellungen',
    //glyph: 'xf007@FontAwesome',
    glyph: 'xf4fe@FontAwesome5FreeSolid',
    strings: {
        editPassword: '#UT#Passwort ändern',
        password: '#UT#Passwort',
        password_check: '#UT#Passwort Kontrolle',
        passwordMisMatch: '#UT#Die Passwörter stimmern nicht überein!',
        saveBtn: '#UT#speichern',
        cancelBtn: '#UT#Abbrechen'
    },
    //layout: 'fit',

    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                title: me.title, //see EXT6UPD-9
                items: [{
                    xtype: 'form',
                    //frame: true,
                    width: 400,
                    //ui: 'default-framed',
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
                            //anchor: '100%'
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
                            glyph: 'f0c7@FontAwesome5FreeSolid',
                            text: me.strings.saveBtn
                        },{
                            xtype: 'button',
                            itemId: 'cancelBtn',
                            glyph: 'f00d@FontAwesome5FreeSolid',
                            text: me.strings.cancelBtn
                        }]
                    }]
                }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});