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
    extend: 'Ext.window.Window',
    alias: 'widget.adminUserAddWindow',
    itemId: 'adminUserAddWindow',
    cls: 'adminUserAddWindow',
    title: '#UT#Benutzer erstellen',
    titleEdit: '#UT#Benutzer bearbeiten',
    requires: [
        'Editor.view.admin.user.AddWindowViewController',
        'Editor.store.admin.LspStore'
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
        languagesLabel: '#UT#Automatische Zuweisung',
        sourceLangageLabel: '#UT#Quellsprache(n)',
        sourceLangageTip: '#UT#Quellsprache(n)',
        targetLangageLabel: '#UT#Zielsprache(n)',
        targetLangageTip: '#UT#Zielsprache(n)',
        languageInfo: '#UT#Beim Import von Aufgaben werden "Editor" Benutzer mit den passenden Sprachen <a href="http://confluence.translate5.net/pages/viewpage.action?pageId=557164" target="_blank" title="mehr Info">automatisch der Aufgabe zugewiesen</a>.',
        localeLabel: '#UT#Benutzersprache',
        parentUserLabel: '#UT#Übergeordneter Benutzer',
        bottomOpenIdNoEditInfo: '#UT# ⁴ Der Benutzer kann nicht bearbeitet werden. Dieser Benutzer wird von translate5 nach der OpenID-Authentifizierung automatisch erstellt.',
        clientPmSubRoles: '#UT#Zugängliche Managementübersichten für Rolle PM',
    },
    modal: true,
    layout: 'fit',
    initComponent: function () {
        var me = this;

        me.callParent(arguments);
        me.on('beforeshow', function () {
            me.down('fieldset#passwords').setDisablePasswords(true);
        });
    },
    initConfig: function (instanceConfig) {
        var me = this,
            config = {},
            defaults = {
                labelWidth: 160,
                anchor: '100%'
            },
            bottomInfo = [me.strings.bottomInfo],
            translations = [];

        if (!instanceConfig.editMode) {
            bottomInfo.push(me.strings.bottomPwInfo);
        }

        Ext.Object.each(Editor.data.l10n.translations, function (id, value) {
            translations.push([id, value]);
        });
        config = {
            title: me.title, //see EXT6UPD-9
            height: Math.min(750, parseInt(Ext.getBody().getViewSize().height * 0.9)),
            width: 900,
            flex: 1,
            items: [
                {
                    xtype: 'form',
                    //padding: 5,
                    ui: 'default-frame',
                    scrollable: 'vertical',
                    defaults: defaults,
                    items: [
                        {
                            layout: {
                                type: 'hbox',
                                pack: 'start',
                                align: 'stretch'
                            },
                            xtype: 'container',
                            items: [
                                {
                                    //first column
                                    xtype: 'fieldset',
                                    margin: 5,
                                    defaults: defaults,
                                    title: me.strings.userInfo,
                                    flex: 1,
                                    items: [
                                        {
                                            xtype: 'radiogroup',
                                            fieldLabel: me.strings.genderLabel,
                                            //columns: 1,
                                            items: [
                                                {boxLabel: me.strings.genderFemale, name: 'gender', inputValue: 'f'},
                                                {boxLabel: me.strings.genderMale, name: 'gender', inputValue: 'm'},
                                                {boxLabel: me.strings.genderNeutral, name: 'gender', inputValue: 'n'}
                                            ]
                                        },
                                        {
                                            xtype: 'fieldcontainer',
                                            fieldLabel: me.strings.nameLabel,
                                            toolTip: me.strings.nameTip,
                                            layout: 'hbox',
                                            combineErrors: true,
                                            defaultType: 'textfield',
                                            defaults: {
                                                hideLabel: 'true'
                                            },
                                            items: [
                                                {
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
                                                }
                                            ]
                                        },
                                        {
                                            xtype: 'textfield',
                                            name: 'email',
                                            maxLength: 255,
                                            allowBlank: false,
                                            vtype: 'email',
                                            toolTip: me.strings.emailTip,
                                            fieldLabel: me.strings.emailLabel
                                        }
                                    ]
                                }
                            ]
                        },
                        {
                            layout: {
                                type: 'hbox',
                                pack: 'start',
                                align: 'stretch'
                            },
                            xtype: 'container',
                            items: [
                                {
                                    xtype: 'fieldset',
                                    itemId: 'loginDetailsFieldset',
                                    margin: 5,
                                    flex: 1,
                                    defaults: defaults,
                                    title: me.strings.loginInfo,
                                    items: [
                                        {
                                            xtype: 'textfield',
                                            name: 'login',
                                            maxLength: 255,
                                            minLength: 6,
                                            allowBlank: false,
                                            toolTip: me.strings.loginTip,
                                            fieldLabel: me.strings.loginLabel
                                        },
                                        {
                                            xtype: 'hidden',
                                            name: 'roles'
                                        },
                                        {
                                            xtype: 'fieldset',
                                            itemId: 'rolesGroup',
                                            margin: 5,
                                            title: me.strings.rolesLabel + ' &#8505;',
                                            autoEl: {
                                                tag: 'div',
                                                'data-qtip': Ext.String.htmlEncode(me.strings.bottomRoleInfo)
                                            },
                                            items: this.getRoleGroupSets(),
                                        },
                                        {
                                            xtype: 'Editor.combobox',
                                            itemId: 'lsp',
                                            name: 'lsp',
                                            allowBlank: true,
                                            forceSelection: false,
                                            hidden: true,
                                            store: {
                                                xtype: 'store',
                                                data: [] // Initially empty, will be set dynamically
                                            },
                                            queryMode: 'local',
                                            displayField: 'name',
                                            valueField: 'id',
                                            bind: {
                                                fieldLabel: '{l10n.lsp.title}',
                                            },
                                            listeners: {
                                                change: (box, newValue) => this.onLspChange(box, newValue),
                                            },
                                        },
                                        {
                                            xtype: 'combo',
                                            itemId: 'locale',
                                            name: 'locale',
                                            width: 110,
                                            allowBlank: false,
                                            editable: false,
                                            forceSelection: true,
                                            store: translations,
                                            queryMode: 'local',
                                            fieldLabel: me.strings.localeLabel
                                        },
                                        {
                                            xtype: 'customers',
                                            bind: {
                                                fieldLabel: '{l10n.general.clients}',
                                            },
                                            name: 'customers',
                                            store: {
                                                xtype: 'store',
                                                data: [] // Initially empty, will be set dynamically
                                            },
                                            queryMode: 'local',
                                            displayField: 'name',
                                            valueField: 'id',
                                            typeAhead: true,
                                            anyMatch: true,
                                            selectOnFocus: true
                                        }
                                    ]
                                },
                                {
                                    xtype: 'fieldset',
                                    margin: 5,
                                    flex: 1,
                                    itemId: 'passwords',
                                    defaults: defaults,
                                    defaultType: 'textfield',
                                    title: me.strings.password,
                                    setDisablePasswords: function (disable) {
                                        Ext.Array.forEach(this.query('textfield'), function (field) {
                                            field.setDisabled(disable);

                                            if (disable) {
                                                field.reset();
                                            }
                                        });
                                    },
                                    items: [
                                        {
                                            xtype: 'checkbox',
                                            hideLabel: true,
                                            boxLabel: instanceConfig.editMode ? me.strings.editPassword : me.strings.setPassword,
                                            style: 'margin-bottom:10px',
                                            handler: function (me, checked) {
                                                var fieldset = me.ownerCt;
                                                fieldset.setDisablePasswords(!checked);
                                            }
                                        },
                                        {
                                            inputType: 'password',
                                            name: 'passwd',
                                            itemId: 'password',
                                            minLength: 12,
                                            allowBlank: false,
                                            disabled: true,
                                            fieldLabel: me.strings.password
                                        },
                                        {
                                            inputType: 'password',
                                            name: 'passwd_check',
                                            itemId: 'passwd_check',
                                            minLength: 12,
                                            allowBlank: false,
                                            disabled: true,
                                            validator: function (value) {
                                                var pwd = this.previousSibling('[name=passwd]');

                                                return (value === pwd.getValue()) ? true : me.strings.passwordMisMatch;
                                            },
                                            fieldLabel: me.strings.password_check
                                        }
                                    ]
                                }
                            ]
                        },
                        {
                            xtype: 'container',
                            html: '<p>' + bottomInfo.join('</p><p style="margin-top:5px;margin-left:5px;">') + '</p>',
                            dock: 'bottom'
                        }
                    ]
                }
            ],
            dockedItems: [
                {
                    xtype: 'toolbar',
                    dock: 'bottom',
                    ui: 'footer',
                    layout: {
                        type: 'hbox',
                        pack: 'start'
                    },
                    items: [
                        {
                            xtype: 'button',
                            hidden: true,
                            itemId: 'feedbackBtn',
                            text: me.strings.feedbackText,
                            tooltip: me.strings.feedbackTip,
                            iconCls: 'ico-error',
                            ui: 'default-toolbar'
                        },
                        {
                            xtype: 'tbfill'
                        },
                        {
                            xtype: 'button',
                            glyph: instanceConfig.editMode ? 'f0c7@FontAwesome5FreeSolid' : 'f234@FontAwesome5FreeSolid',
                            itemId: 'save-user-btn',
                            text: instanceConfig.editMode ? me.strings.saveBtn : me.strings.addBtn
                        },
                        {
                            xtype: 'button',
                            glyph: 'f00d@FontAwesome5FreeSolid',
                            itemId: 'cancel-user-btn',
                            text: me.strings.cancelBtn
                        }
                    ]
                }
            ]
        };

        if (instanceConfig.editMode) {
            config.title = me.titleEdit;
        }

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }

        return me.callParent([config]);
    },

    getRoleGroupSets: function () {
        let sets = [];
        const groupedRoles = Editor.data.app.groupedRoles;

        sets.push(this.createRoleFieldSet('General roles', 'general', groupedRoles.general));

        if (groupedRoles.hasOwnProperty('admins')) {
            sets.push(this.createRoleFieldSet('Admin roles', 'admins', groupedRoles.admins));
        }

        if (groupedRoles.hasOwnProperty('notRequireClient')) {
            sets.push(this.createRoleFieldSet('Roles don\'t require client', 'notRequireClient', groupedRoles.notRequireClient));
        }

        sets.push(this.createRoleFieldSet('Roles require client', 'requireClient', groupedRoles.requireClient));
        sets.push(this.createRoleFieldSet(this.strings.clientPmSubRoles, 'clientPmSubRoles', groupedRoles.clientPmSubRoles, true));

        return sets;
    },

    createRoleFieldSet: function (title, groupType, roles, hidden = false) {
        const userWindow = this;
        let items = [];

        roles.forEach(function (node) {
            items.push({
                boxLabel: node.label,
                name: 'roles_helper',
                value: node.role,
                listeners: {
                    change: userWindow.onRoleCheckChange,
                    scope: userWindow
                }
            });
        });

        return {
            xtype: 'fieldset',
            title: title,
            checkboxToggle: false,
            hidden: hidden || ! items || items.length === 0,
            items: [
                {
                    xtype: 'checkboxgroup',
                    columns: 2,
                    items: items
                }
            ],
            reference: groupType + 'FieldSet'
        };
    },

    getSelectedRoles: function (form) {
        return form.down('#rolesGroup').query('checkbox[checked=true]').map(box => box.initialConfig.value);
    },

    toggleEnableForConflictingRoles: function (form) {
        const checkboxes = form.down('#rolesGroup').query('checkbox'),
            findCheckbox = (role) => checkboxes.find(box => box.initialConfig.value === role);

        checkboxes.map(box => box.setDisabled(false));

        form.down('#rolesGroup').query('checkbox[checked=true]').map(function (box) {
            if (Editor.data.app.conflictingRoles.hasOwnProperty(box.initialConfig.value)) {
                Editor.data.app.conflictingRoles[box.initialConfig.value].forEach(function (role) {
                    findCheckbox(role)?.setDisabled(true);
                });

                return;
            }

            for (let role in Editor.data.app.conflictingRoles) {
                if (Editor.data.app.conflictingRoles[role].includes(box.initialConfig.value)) {
                    findCheckbox(role).setDisabled(true);
                }
            }
        });
    },

    onRoleCheckChange: function (checkbox, checked) {
        const form = checkbox.up('form'),
            selectedRoles = this.getSelectedRoles(form),
            clientPmSubRolesGroup = this.lookupReference('clientPmSubRolesFieldSet');

        this.toggleEnableForConflictingRoles(form);

        if (selectedRoles.includes('clientpm')) {
            clientPmSubRolesGroup.setHidden(false);
        } else {
            clientPmSubRolesGroup.setHidden(true);
            clientPmSubRolesGroup.down('checkboxgroup').reset();
        }

        this.toggleRequirementOfCustomersField(
            form,
            this.hasRoleFromGroup(selectedRoles, ['requireClient'])
        );

        form.down('hidden[name="roles"]').setValue(selectedRoles.join(','));

        this.toggleLspField(form, selectedRoles);
    },

    toggleRequirementOfCustomersField: function (form, required) {
        const customersField = form.down('customers');

        customersField.allowBlank = ! required;
        customersField.forceSelection = required;
    },

    toggleLspField: function (form, roles) {
        const lspField = form.down('#lsp');

        if (roles.includes('jobCoordinator')) {
            lspField.setHidden(false);
            lspField.allowBlank = false;
            lspField.setDisabled(! form.getRecord().phantom);
            lspField.forceSelection = true;

            return;
        }

        lspField.setHidden(true);
        lspField.allowBlank = true;
        lspField.setDisabled(true);
        lspField.forceSelection = false;
    },

    hasRoleFromGroup: function (selectedRoles, groups) {
        return groups.some(
            group => selectedRoles.some(
                role => this.isRoleFromGroup(role, group)
            )
        );
    },

    isRoleFromGroup: function (role, group) {
        if (! Editor.data.app.groupedRoles.hasOwnProperty(group)) {
            return false;
        }

        return Editor.data.app.groupedRoles[group].some(node => node.role === role);
    },

    onLspChange: function (fld, newValue) {
        /**
         * @type {Editor.model.admin.LspModel}
         */
        const lsp = fld.getStore().getById(newValue);
        const form = fld.up('form');

        if (null === lsp) {
            this.updateCustomerField(form, Ext.getStore('customersStore').getData().items);

            return;
        }

        this.updateCustomerField(form, lsp.get('customers'));
    },

    /**
     * loads the record into the form, does set the role checkboxes according to the roles value
     * @param {Editor.model.admin.User} record
     */
    loadRecord: function (record) {
        var me = this,
            form = me.down('form'),
            roles = record.getMainRoles();

        form.loadRecord(record);

        me.query('#rolesGroup checkbox').forEach(function (box) {
            const
                isAdminRole = me.isRoleFromGroup(box.initialConfig.value, 'admins'),
                isNotRequireClientRole = me.isRoleFromGroup(box.initialConfig.value, 'notRequireClient'),
                boxInitValue = box.initialConfig.value,
                hidden = (record.isLspUser() && (isAdminRole || isNotRequireClientRole) && 'jobCoordinator' !== boxInitValue)
                    // existing user is not LSP user. Can't set jobCoordinator role
                    || (record.get('userGuid').length !== 0 && ! record.isLspUser() && 'jobCoordinator' === boxInitValue)
                    || (record.isLspUser() && 'clientpm' === boxInitValue)
            ;

            box.setValue(roles.includes(boxInitValue));
            box.setHidden(hidden);
        });

        me.updateCustomerField(form, Ext.getStore('customersStore').getData().items);

        const lspField = form.down('#lsp');
        const lspStore = lspField.getStore();

        Ext.Ajax.request({
            url: Editor.data.restpath + 'lsp',
            method: 'GET',
            success: response => {
                const data = response.responseJson;
                lspStore.loadData(data.rows);

                const lsp = lspStore.getById(record.get('lsp'));
                lspField.setValue(lsp);
                lspField.setHidden(! lsp);

                if (lsp) {
                    me.updateCustomerField(form, lsp.get('customers'));
                }
            }
        });

        if (form.isDisabled() && record.get('openIdIssuer') !== '') {
            form.add({
                xtype: 'container',
                html: '<p>' + me.strings.bottomOpenIdNoEditInfo + '</p><p style="margin-top:5px;margin-left:5px;"></p>',
                dock: 'bottom'
            });
        }
    },

    /**
     * @param {Ext.form.Panel} form
     * @param {{id: int, name: string}[]} storeData
     */
    updateCustomerField: function (form, storeData) {
        const customersField = form.down('customers');
        const customersFieldStore = customersField.getStore();
        /** @type {{id: int, name: string}[]} */
        const customers = [];

        customersFieldStore.loadData(storeData);

        for (const customerId of form.getRecord().getCustomerIds()) {
            const customer = customersFieldStore.getById(customerId);

            if (customer) {
                customers.push(customer);
            }
        }

        customersField.setValue(customers.map(c => c.id));
    },
});