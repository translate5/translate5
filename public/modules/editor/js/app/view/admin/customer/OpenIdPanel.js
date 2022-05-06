
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

Ext.define('Editor.view.admin.customer.OpenIdPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.openIdPanel',
    itemId:'openIdPanel',

    strings:{
        save:'#UT#Speichern',
        cancel:'#UT#Abbrechen',
        saveCustomerMsg:'#UT#Kunde wird gespeichert...',
        customerSavedMsg:'#UT#Kunde gespeichert!',
        openIdServer:'#UT#OpenID server',
        openIdIssuer:'#UT#OpenID Issuer',
        openIdClientId:'#UT#OpenID Benutzername',
        openIdClientSecret:'#UT#OpenID Passwort',
        openIdAuth2Url:'#UT#OpenID OAuth URL',
        defaultRolesGroupLabel: '#UT#Standardrollen',
        serverRolesGroupLabel: '#UT#Erlaubte Rollen',
        openIdRedirectLabel:'#UT#Verlinkter Text Loginseite',
        openIdRedirectCheckbox:'#UT#Anmeldeseite nicht anzeigen: Automatisch zum OpenID Connect-Server umleiten, wenn keine Benutzersitzung in translate5 vorhanden ist. Wenn diese Checkbox nicht aktiviert ist, wird der im untenstehenden Textfeld definierte Text auf der Loginseite von translate5 mit dem OpenID Connect Server verlinkt.',
        defaultRolesGroupLabelTooltip: '#UT#Standardsystemrollen werden verwendet, wenn der OpenID-Server keine Systemrollen für den Benutzer übergibt, der sich anmeldet.',
        serverRolesGroupLabelTooltip: '#UT#Systemrollen, die der OpenID-Server in translate5 festlegen darf.',
        helpButtonText:'#UT#Hilfe',
        ssoText:'#UT#Single Sign-on',
        thisFieldIsRequiredText:'#UT#Dieses Feld darf nicht leer sein'
    },

    initConfig: function (instanceConfig) {
        var me = this,
            roles=[];

        Ext.Object.each(Editor.data.app.roles, function(key, value) {
            //if the role is not settable for the user, do not create check box for it
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
        var config = {
                bodyPadding: 10,
                scrollable:true,
                disabled:true,
                title: 'OpenID Connect',
                defaults:{
                    width: '100%'
                },
                dockedItems:[{
                    xtype: 'toolbar',
                    flex: 1,
                    dock: 'bottom',
                    ui: 'footer',
                    layout: {
                        pack: 'start',
                        type: 'hbox'
                    },
                    bind:{
                        disabled:'{!record}'
                    },
                    items:[{
                        xtype: 'button',
                        itemId: 'saveOpenIdButton',
                        text: me.strings.save,
                        glyph: 'f00c@FontAwesome5FreeSolid'
                    },{
                        xtype: 'button',
                        itemId: 'cancelOpenIdButton',
                        text: me.strings.cancel,
                        glyph: 'f00d@FontAwesome5FreeSolid'
                    },{
                        xtype: 'button',
                        glyph: 'f059@FontAwesome5FreeSolid',
                        text: me.strings.helpButtonText,
                        handler: function() {
                            window.open('https://confluence.translate5.net/display/BUS/OpenID+connect+in+translate5', '_blank');
                        }
                    }]
                }],
                items: [{
                    xtype: 'textfield',
                    fieldLabel: me.strings.openIdServer,
                    vtype: 'url',
                    name: 'openIdServer',
                    listeners: {
                        change: me.onOpenIdFieldChange,
                        scope: me
                    },
                    bind: {
                        value:'{record.openIdServer}'
                    }
                }, {
                    xtype: 'textfield',
                    fieldLabel: me.strings.openIdIssuer,
                    vtype: 'url',
                    name: 'openIdIssuer',
                    listeners: {
                        change: me.onOpenIdFieldChange,
                        scope: me
                    },
                    bind: {
                        value:'{record.openIdIssuer}'
                    }
                }, {
                    xtype: 'textfield',
                    fieldLabel: me.strings.openIdClientId,
                    name: 'openIdClientId',
                    listeners: {
                        change: me.onOpenIdFieldChange,
                        scope: me
                    },
                    bind: {
                        value:'{record.openIdClientId}'
                    }
                },
                {
                    xtype: 'textfield',
                    fieldLabel: me.strings.openIdClientSecret,
                    name: 'openIdClientSecret',
                    listeners: {
                        change: me.onOpenIdFieldChange,
                        scope: me
                    },
                    bind: {
                        value:'{record.openIdClientSecret}'
                    }
                },
                {
                    xtype: 'textfield',
                    fieldLabel: me.strings.openIdAuth2Url,
                    vtype: 'url',
                    name: 'openIdAuth2Url',
                    listeners: {
                        change: me.onOpenIdFieldChange,
                        scope: me
                    },
                    bind: {
                        value:'{record.openIdAuth2Url}'
                    }
                }, {
                    xtype: 'hidden',
                    name: 'openIdDefaultServerRoles',
                    bind: {
                        value:'{record.openIdDefaultServerRoles}'
                    }
                }, {
                    xtype: 'checkboxgroup',
                    itemId: 'defaultRolesGroup',
                    cls: 'x-check-group-alt',
                    labelClsExtra: 'checkBoxLableInfoIconDefault',
                    fieldLabel: me.strings.defaultRolesGroupLabel,
                    autoEl: {
                        tag: 'span',
                        'data-qtip': me.strings.defaultRolesGroupLabelTooltip
                    },
                    items: roles,
                    columns: 3
                }, {
                    xtype: 'hidden',
                    name: 'openIdServerRoles',
                    bind: {
                        value:'{record.openIdServerRoles}'
                    }
                }, {
                    xtype: 'checkboxgroup',
                    name: 'serverRolesGroup',
                    itemId: 'serverRolesGroup',
                    cls: 'x-check-group-alt',
                    labelClsExtra: 'checkBoxLableInfoIconDefault',
                    fieldLabel: me.strings.serverRolesGroupLabel,
                    autoEl: {
                        tag: 'span',
                        'data-qtip': me.strings.serverRolesGroupLabelTooltip
                    },
                    items: roles,
                    columns: 3
                }, {
                    xtype: 'textfield',
                    fieldLabel: me.strings.openIdRedirectLabel,
                    name: 'openIdRedirectLabel',
                    itemId: 'openIdRedirectLabel',
                    validator: me.openIdRedirectLabelValidator,
                    emptyText:me.strings.ssoText,
                    bind:{
                        value:'{record.openIdRedirectLabel}'
                    },
                    listeners: {
                        //change: 'checkFieldsValid',
                        change: 'onOpenIdRedirectLabelChange',
                        scope:me
                    }

                }, {
                    xtype: 'checkbox',
                    boxLabel: me.strings.openIdRedirectCheckbox,
                    name: 'openIdRedirectCheckbox',
                    itemId: 'openIdRedirectCheckbox',
                    bind:{
                        value:'{record.openIdRedirectCheckbox}'
                    },
                    listeners: {
                        //change: 'checkFieldsValid',
                        change: 'onOpenIdRedirectCheckboxChange',
                        scope:me
                    }
                }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

    /***
     * Redirect lable text change event handler.
     * Redirect lable and redirect checkbox requirements are combined.
     * When the checkbox is checked, no need for lable text, otherwise the lable text is required.
     * @param field
     * @param newValue
     */
    onOpenIdRedirectLabelChange: function (field, newValue){
        var me=this;

        if(newValue !== ""){
            field.clearInvalid();
        }else{
            field.setActiveError(me.strings.thisFieldIsRequiredText);
        }

        me.onOpenIdFieldChange(field, newValue);
    },

    /***
     * Redirect checkbox change event handler.
     * Redirect lable and redirect checkbox requirements are combined.
     * When the checkbox is checked, no need for lable text, otherwise the lable text is required.
     * @param field
     * @param newValue
     */
    onOpenIdRedirectCheckboxChange: function (field,newValue){
        var me=this,
            openIdRedirectLabel=me.down('#openIdRedirectLabel');

        // if checked or
        if(newValue || openIdRedirectLabel.getValue() !== ""){
            openIdRedirectLabel.clearInvalid();
        }else{
            openIdRedirectLabel.setActiveError(me.strings.thisFieldIsRequiredText);
        }

        me.onOpenIdFieldChange(openIdRedirectLabel, openIdRedirectLabel.getValue());
    },


    /***
     * Disable the save button if one of the required fields is invalid or the main customers form is invalid
     */
    checkFieldsValid:function (){
        var me=this,
            mainSaveButton = me.up('customerPanel').down('#saveButton'),
            fields=['openIdServer','openIdIssuer','openIdAuth2Url','openIdClientId','openIdClientSecret','openIdRedirectLabel'],
            field,
            isValid = true;

        // if required field is changed, mark all as invalid and force the user to enter value
        Ext.Array.forEach(fields, function(f) {
            field = me.down('field[name="'+f+'"]');
            if(field.hasActiveError()){
                isValid = false;
            }
        });

        me.down('#saveOpenIdButton').setDisabled(!isValid || mainSaveButton.isDisabled());
    },

    /***
     * Run validation and set allowBlank for all required fields defined in the fields list
     * @param field
     * @param newValue
     */
    onOpenIdFieldChange:function(field,newValue){
        var me=this,
            fields=['openIdServer','openIdIssuer','openIdAuth2Url','openIdClientId','openIdClientSecret','openIdRedirectLabel'],
            isRequired = Ext.Array.contains(fields,field.name) && newValue !== "",
            openIdField;

        // if required field is changed, mark all as invalid and force the user to enter value
        Ext.Array.forEach(fields, function(f) {
            openIdField = me.down('field[name="'+f+'"]');
            openIdField.allowBlank = !isRequired;
            openIdField.isValid();
        });

        me.checkFieldsValid();
    },

    /***
     * Redirect lable validator. It is valid when the checkbox is checked or when there is some text provided
     * @param val
     * @returns {boolean|string}
     */
    openIdRedirectLabelValidator: function (val) {
        var me=this,
            view = me.up('#openIdPanel'),
            openIdRedirectCheckbox = view.down('#openIdRedirectCheckbox');
        return (openIdRedirectCheckbox.checked || val !== "") ? true : view.strings.thisFieldIsRequiredText;
    },

    /**
     * merge and save the checked roles into the hidden roles field
     * @param {Ext.form.field.Checkbox} box
     * @param {Boolean} checked
     */
    roleCheckChange: function (box, checked) {
        var roles = [],
            holder = box.up('checkboxgroup'),
            boxes = holder.query('checkbox[checked=true]'),
            holderMap = {
                serverRolesGroup: 'openIdServerRoles',
                defaultRolesGroup: 'openIdDefaultServerRoles'
            };
        Ext.Array.forEach(boxes, function (box) {
            roles.push(box.initialConfig.value);
        });
        box.up('#openIdPanel').down('hidden[name="' + holderMap[holder.getItemId()] + '"]').setValue(roles.join(','));
    }
});