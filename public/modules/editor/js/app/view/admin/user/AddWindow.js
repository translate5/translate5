
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

Ext.define('Editor.view.admin.user.AddWindow', {
    extend : 'Ext.window.Window',
    alias : 'widget.adminUserAddWindow',
    itemId : 'adminUserAddWindow',
    cls : 'adminUserAddWindow',
    title : '#UT#Benutzer erstellen',
    titleEdit : '#UT#Benutzer bearbeiten',
    requires: [
        'Editor.view.admin.user.AddWindowViewController'
    ],
    controller: 'adminUserAddWindow',
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
        genderNeutral: '#UT#Ohne',
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
        cancelBtn: '#UT#Abbrechen',
        languagesLabel:'#UT#Automatische Zuweisung',
        sourceLangageLabel:'#UT#Quellsprache(n)',
        sourceLangageTip:'#UT#Quellsprache(n)',
        targetLangageLabel:'#UT#Zielsprache(n)',
        targetLangageTip:'#UT#Zielsprache(n)',
        languageInfo: '#UT#Beim Import von Aufgaben werden "Editor" Benutzer mit den passenden Sprachen <a href="http://confluence.translate5.net/pages/viewpage.action?pageId=557164" target="_blank" title="mehr Info">automatisch der Aufgabe zugewiesen</a>.',
        localeLabel:'#UT#Benutzersprache',
        parentUserLabel: '#UT#Übergeordneter Benutzer',
        bottomOpenIdNoEditInfo: '#UT# ⁴ Der Benutzer kann nicht bearbeitet werden. Dieser Benutzer wird von translate5 nach der OpenID-Authentifizierung automatisch erstellt.'
    },
    modal : true,
    layout:'fit',
    initComponent: function() {
        var me = this;

        me.callParent(arguments);
        me.on('beforeshow', function(){
            me.down('fieldset#passwords').setDisablePasswords(true);
        });
    },
    initConfig : function(instanceConfig) {
        var me = this,
            roles = [],
            config = {},
            defaults = {
                labelWidth: 160,
                anchor: '100%'
            },
            bottomInfo = [me.strings.bottomInfo],
            translations = [];

        if(!instanceConfig.editMode) {
            bottomInfo.push(me.strings.bottomPwInfo);
        }

        Ext.Object.each(Editor.data.app.roles, function(key, value) {
            //if the role is not setable for the user, do not create an check box for it
            if(!value.setable){
                return;
            }
            roles.push({
                boxLabel: value.label,
                name: 'roles_helper',
                value: key,
                handler: me.roleCheckChange
            });
        });
        Ext.Object.each(Editor.data.l10n.translations, function(id,value) {
            translations.push([id,value]);
        });
        config = {
            title: me.title, //see EXT6UPD-9
            height: Math.min(750, parseInt(Ext.getBody().getViewSize().height * 0.9)),
            width : 900,
            flex:1,
            items : [{
                xtype: 'form',
                //padding: 5,
                ui: 'default-frame',
                scrollable: 'vertical',
                defaults: defaults,
                items: [{
                    layout: {
                        type: 'hbox',
                        pack: 'start',
                        align: 'stretch'
                    },
                    xtype:'container',
                    items:[{
                        //first column
                        xtype: 'fieldset',
                        margin:5,
                        defaults: defaults,
                        title: me.strings.userInfo,
                        flex:1,
                        items:[{
                            xtype: 'radiogroup',
                            fieldLabel: me.strings.genderLabel,
                            //columns: 1,
                            items: [
                                {boxLabel: me.strings.genderFemale, name: 'gender', inputValue: 'f'},
                                {boxLabel: me.strings.genderMale, name: 'gender', inputValue: 'm'},
                                {boxLabel: me.strings.genderNeutral, name: 'gender', inputValue: 'n'}
                            ]
                        },{
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
                            xtype: 'textfield',
                            name: 'email',
                            maxLength: 255,
                            allowBlank: false,
                            vtype: 'email',
                            toolTip: me.strings.emailTip,
                            fieldLabel: me.strings.emailLabel
                        }]
                    }]
                },{
                    layout: {
                        type: 'hbox',
                        pack: 'start',
                        align: 'stretch'
                    },
                    xtype:'container',
                    items:[
                        {
                            xtype: 'fieldset',
                            itemId:'loginDetailsFieldset',
                            margin:5,
                            flex:1,
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
                                cls: 'x-check-group-alt',
                                fieldLabel: me.strings.rolesLabel + ' &#8505;',
                                labelAlign: 'top',
                                items: roles,
                                columns: 2,
                                autoEl: {
                                    tag: 'div',
                                    'data-qtip': Ext.String.htmlEncode(me.strings.bottomRoleInfo)
                                }
                            },{
                                xtype: 'combo',
                                itemId: 'locale',
                                name:'locale',
                                width:110,
                                allowBlank: false,
                                editable: false,
                                forceSelection: true,
                                store: translations,
                                queryMode: 'local',
                                fieldLabel: me.strings.localeLabel
                            },{
                                xtype: 'combo',
                                itemId: 'parentIds',
                                name:'parentIds',
                                width:110,
                                allowBlank: true,
                                typeAhead: true,
                                anyMatch: true,
                                forceSelection: true,
                                displayField: 'longUserName',
                                valueField: 'id',
                                store: {
                                    type: 'store',
                                    model: 'Editor.model.admin.User',
                                    autoLoad: true,
                                    remoteFilter: false,
                                    pageSize: 0
                                },
                                queryMode: 'local',
                                fieldLabel: me.strings.parentUserLabel
                            }]
                            // + item for assigning customers to the user
                            // (added dynamically by Editor.controller.admin.Customer)
                        },{
                            xtype: 'fieldset',
                            margin:5,
                            flex:1,
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
                                boxLabel: instanceConfig.editMode ? me.strings.editPassword : me.strings.setPassword,
                                style: 'margin-bottom:10px',
                                handler: function(me, checked) {
                                    var fieldset = me.ownerCt;
                                    fieldset.setDisablePasswords(!checked);
                                }
                            },{
                                inputType: 'password',
                                name: 'passwd',
                                itemId:'password',
                                minLength: 12,
                                allowBlank: false,
                                disabled: true,
                                fieldLabel: me.strings.password
                            },{
                                inputType: 'password',
                                name: 'passwd_check',
                                itemId:'passwd_check',
                                minLength: 12,
                                allowBlank: false,
                                disabled: true,
                                validator: function(value) {
                                    var pwd = this.previousSibling('[name=passwd]');
                                    return (value === pwd.getValue()) ? true : me.strings.passwordMisMatch;
                                },
                                fieldLabel: me.strings.password_check
                            }]
                        }
                    ]
                },{
                    xtype: 'container',
                    html: '<p>'+bottomInfo.join('</p><p style="margin-top:5px;margin-left:5px;">')+'</p>',
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
                    glyph:instanceConfig.editMode ?  'f0c7@FontAwesome5FreeSolid' :  'f234@FontAwesome5FreeSolid',
                    itemId : 'save-user-btn',
                    text : instanceConfig.editMode ? me.strings.saveBtn : me.strings.addBtn
                }, {
                    xtype : 'button',
                    glyph: 'f00d@FontAwesome5FreeSolid',
                    itemId : 'cancel-user-btn',
                    text : me.strings.cancelBtn
                }]
            }]
        };

        if(instanceConfig.editMode) {
            config.title = me.titleEdit;
        }

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
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
        var me=this,
            form=me.down('form'),
            roles = record.get('roles').split(',');
        form.loadRecord(record);
        Ext.Array.forEach(this.query('#rolesGroup checkbox'), function(item) {
            item.setValue(Ext.Array.indexOf(roles, item.initialConfig.value) >= 0);
        });

        if(form.isDisabled() && record.get('openIdIssuer')!=''){
            form.add({
                xtype: 'container',
                html: '<p>'+me.strings.bottomOpenIdNoEditInfo+'</p><p style="margin-top:5px;margin-left:5px;"></p>',
                dock : 'bottom'
            });
        }
    }
});