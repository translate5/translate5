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
Ext.define('Editor.view.admin.UserAddWindow', {
    extend : 'Ext.window.Window',
    alias : 'widget.adminUserAddWindow',
    itemId : 'adminUserAddWindow',
    cls : 'adminUserAddWindow',
    title : '#UT#Benutzer erstellen',
    titleEdit : '#UT#Benutzer bearbeiten',
    strings: {
        userInfo: '#UT#Benutzerinformationen',
        loginInfo: '#UT#Anmeldeinformationen',
        nameTip: '#UT#Name (Angabe notwendig)',
        nameLabel: '#UT#Name¹',
        firstNameTip: '#UT#Vorname (Angabe notwendig)',
        firstNameLabel: '#UT#Vorname¹',
        surNameTip: '#UT#Nachname (Angabe notwendig)',
        surNameLabel: '#UT#Nachname¹',
        genderLabel: '#UT#Anrede',
        genderMale: '#UT#Herr',
        genderFemale: '#UT#Frau',
        loginTip: '#UT#Anmeldename (Angabe notwendig)',
        loginLabel: '#UT#Anmeldename¹',
        emailTip: '#UT#E-Mail Adresse (Angabe notwendig)',
        emailLabel: '#UT#E-Mail Adresse¹',
        rolesLabel: '#UT#Systemrollen²',
        setPassword: '#UT#Passwort setzen³',
        editPassword: '#UT#Passwort ändern',
        password: '#UT#Passwort',
        password_check: '#UT#Passwort Kontrolle',
        passwordMisMatch: '#UT#Die Passwörter stimmern nicht überein!',
        bottomInfo: '#UT# ¹ Diese Angaben werden zwingend benötigt.',
        bottomRoleInfo: '#UT# ² Systemrollen bestimmen darüber, welche Rechte im System ein Nutzer hat. Die Rolle Editor darf auf alle Komponenten des Editors zugreifen, sowie auf die Komponenten der Aufgabenübersicht, die für das Editieren von Aufgaben nötig sind. Die Rolle PM darf Benutzer verwalten und Benutzer Aufgaben im Workflow zuweisen oder entziehen. Achtung: Systemrollen dürfen nicht mit Workflowrollen wie Lektor oder Übersetzer verwechselt werden. Benutzer die im Workflow Aufgaben übernehmen sollen müssen mindestens die Systemrolle Editor erhalten.',
        bottomPwInfo: '#UT# ³ Wird kein Passwort gesetzt bekommt der Benutzer automatisch eine E-Mail zur Erstellung eines Passworts.',
        feedbackText: "#UT# Fehler beim Speichern!",
        feedbackTip: '#UT#Fehler beim Speichern des Benutzers: Bitte wenden Sie sich an den Support!',
        addBtn: '#UT#Benutzer hinzufügen',
        saveBtn: '#UT#Benutzer speichern',
        cancelBtn: '#UT#Abbrechen'
    },
    height : 600,
    width : 500,
    loadingMask: null,
    modal : true,
    layout:'fit',
    initComponent : function() {
        var me = this,
            roles = [],
            defaults = {
                labelWidth: 160,
                anchor: '100%'
            },
            bottomInfo = [me.strings.bottomInfo];
        bottomInfo.push(me.strings.bottomRoleInfo);
        if(!me.editMode) {
            bottomInfo.push(me.strings.bottomPwInfo);
        }

        me.on('beforeshow', function(){
            me.down('fieldset#passwords').setDisablePasswords(true);
        });
        
        Ext.Object.each(Editor.data.app.roles, function(key, value) {
            roles.push({
                boxLabel: value, 
                name: 'roles_helper', 
                value: key,
                handler: me.roleCheckChange
            });
        });
        
        Ext.applyIf(me, {
            items : [{
                xtype: 'form',
                padding: 5,
                ui: 'default-frame',
                defaults: defaults,
                items: [{
                    xtype: 'fieldset',
                    defaults: defaults,
                    title: me.strings.userInfo,
                    items:[{
                        xtype: 'fieldcontainer',
                        fieldLabel: me.strings.nameLabel,
                        toolTip: me.strings.nameTip,
                        layout: 'hbox',
                        combineErrors: true,
                        defaultType: 'textfield',
                        defaults: {
                            hideLabel: 'true'
                        },
                        items: [{
                            name: 'firstName',
                            maxLength: 255,
                            fieldLabel: me.strings.firstNameLabel,
                            toolTip: me.strings.firstNameTip,
                            flex: 2,
                            emptyText: me.strings.firstNameLabel,
                            allowBlank: false
                        }, {
                            name: 'surName',
                            maxLength: 255,
                            fieldLabel: me.strings.surNameLabel,
                            toolTip: me.strings.surNameTip,
                            flex: 3,
                            margins: '0 0 0 6',
                            emptyText: me.strings.surNameLabel,
                            allowBlank: false
                        }]
                    },{
                        xtype: 'radiogroup',
                        fieldLabel: me.strings.genderLabel,
                        columns: 1,
                        items: [
                            {boxLabel: me.strings.genderFemale, name: 'gender', inputValue: 'f'},
                            {boxLabel: me.strings.genderMale, name: 'gender', inputValue: 'm'}
                        ]
                    },{
                        xtype: 'textfield',
                        name: 'email',
                        maxLength: 255,
                        allowBlank: false,
                        vtype: 'email',
                        toolTip: me.strings.emailTip,
                        fieldLabel: me.strings.emailLabel
                    }]
                },{
                    xtype: 'fieldset',
                    defaults: defaults,
                    title: me.strings.loginInfo,
                    items:[{
                        xtype: 'textfield',
                        name: 'login',
                        maxLength: 255,
                        minLength: 6,
                        allowBlank: false,
                        toolTip: me.strings.loginTip,
                        fieldLabel: me.strings.loginLabel
                    },{
                        xtype: 'hidden',
                        name: 'roles'
                    },{
                        xtype: 'checkboxgroup',
                        itemId: 'rolesGroup',
                        fieldLabel: me.strings.rolesLabel,
                        items: roles
                    }]
                },{
                    xtype: 'fieldset',
                    itemId: 'passwords',
                    defaults: defaults,
                    defaultType: 'textfield',
                    title: me.strings.password,
                    setDisablePasswords: function(disable) {
                        Ext.Array.forEach(this.query('textfield'), function(field) {
                            field.setDisabled(disable);
                            if(disable) {
                                field.reset();
                            }
                        });
                    },
                    items: [{
                        xtype: 'checkbox',
                        hideLabel: true,
                        boxLabel: me.editMode ? me.strings.editPassword : me.strings.setPassword,
                        style: 'margin-bottom:10px',
                        handler: function(me, checked) {
                            var fieldset = me.ownerCt;
                            fieldset.setDisablePasswords(!checked);
                        }
                    },{
                        inputType: 'password',
                        name: 'passwd',
                        minLength: 8,
                        allowBlank: false,
                        disabled: true,
                        fieldLabel: me.strings.password
                    },{
                        inputType: 'password',
                        name: 'passwd_check',
                        minLength: 8,
                        allowBlank: false,
                        disabled: true,
                        validator: function(value) {
                            var pwd = this.previousSibling('[name=passwd]');
                            return (value === pwd.getValue()) ? true : me.strings.passwordMisMatch;
                        },
                        fieldLabel: me.strings.password_check
                    }]
                },{
                    xtype: 'container',
                    html: '<p>'+bottomInfo.join('</p><p style="margin-top:5px;">')+'</p>',
                    dock : 'bottom'
                }]
            }],
            dockedItems : [{
                xtype : 'toolbar',
                dock : 'bottom',
                ui: 'footer',
                layout: {
                    type: 'hbox',
                    pack: 'start'
                },
                items : [{
                    xtype: 'button',
                    hidden: true,
                    itemId: 'feedbackBtn',
                    text: me.strings.feedbackText,
                    tooltip: me.strings.feedbackTip,
                    iconCls: 'ico-error',
                    ui: 'default-toolbar'
                },{
                    xtype: 'tbfill'
                },{
                    xtype : 'button',
                    iconCls : me.editMode ? 'ico-user-save' : 'ico-user-add',
                    itemId : 'save-user-btn',
                    text : me.editMode ? me.strings.saveBtn : me.strings.addBtn
                }, {
                    xtype : 'button',
                    iconCls : 'ico-cancel',
                    itemId : 'cancel-user-btn',
                    text : me.strings.cancelBtn
                }]
            }]
        });
        
        if(me.editMode) {
            me.title = me.titleEdit;
        }

        me.callParent(arguments);
    },
    /**
     * merge and save the checked roles into the hidden roles field
     * @param {Ext.form.field.Checkbox} box
     * @param {Boolean} checked
     */
    roleCheckChange: function(box, checked) {
        var roles = [],
            boxes = box.up('#rolesGroup').query('checkbox[checked=true]');
        Ext.Array.forEach(boxes, function(box){
            roles.push(box.initialConfig.value);
        });
        box.up('form').down('hidden[name="roles"]').setValue(roles.join(','));
    },
    /**
     * loads the record into the form, does set the role checkboxes according to the roles value
     * @param record
     */
    loadRecord: function(record) {
        var roles = record.get('roles').split(',');
        this.down('form').loadRecord(record);
        Ext.Array.forEach(this.query('#rolesGroup checkbox'), function(item) {
            item.setValue(Ext.Array.indexOf(roles, item.initialConfig.value) >= 0);
        });
    }
});