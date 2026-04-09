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
    title: '#UT#Create user',
    titleEdit: '#UT#Edit user',
    requires: [
        'Editor.view.admin.user.AddWindowViewController',
        'Editor.store.admin.CoordinatorGroupStore'
    ],
    controller: 'adminUserAddWindow',
    strings: {
        userInfo: '#UT#User information',
        loginInfo: '#UT#Login details',
        nameTip: '#UT#Name (mandatory)',
        nameLabel: '#UT#Name¹',
        firstNameTip: '#UT#Forename (mandatory)',
        firstNameLabel: '#UT#Forename¹',
        surNameTip: '#UT#Surname (mandatory)',
        surNameLabel: '#UT#Surname¹',
        genderLabel: '#UT#Salutation',
        genderMale: '#UT#Mr.',
        genderFemale: '#UT#Ms.',
        genderNeutral: '#UT#None',
        loginTip: '#UT#Login name (mandatory)',
        loginLabel: '#UT#Login name¹',
        emailTip: '#UT#E-mail address (mandatory)',
        emailLabel: '#UT#E-Mail address¹',
        rolesLabel: '#UT#System roles²',
        setPassword: '#UT#Set password³',
        editPassword: '#UT#Change password',
        password: '#UT#Password',
        password_check: '#UT#Password control',
        passwordMisMatch: '#UT#The passwords do not match!',
        bottomInfo: '#UT#¹ This information is mandatory.',
        bottomRoleInfo: '#UT#² System roles determine which rights a user has in the system. The Editor role can access all components of the Editor, as well as the components of the task list that are required for editing tasks. The PM role can manage users and assign or withdraw tasks from users in the workflow. Attention: System roles should not be confused with workflow roles such as reviewer or translator. Users who are to perform tasks in the workflow must have at least the Editor system role.',
        bottomPwInfo: '#UT#³ If no password is set, the user automatically receives an e-mail to create a password.',
        feedbackText: '#UT#Error while saving!',
        feedbackTip: '#UT#Error while saving the user: please contact the support!',
        addBtn: '#UT#Add user',
        saveBtn: '#UT#Save user',
        cancelBtn: '#UT#Cancel',
        languagesLabel: '#UT#Auto-assignment',
        languageInfo: '#UT#During import, users with the right “Editor” and matching language combination <a href="http://confluence.translate5.net/pages/viewpage.action?pageId=557164" target="_blank" title="mehr Info">will automatically be assigned to the task</a>.',
        localeLabel: '#UT#User GUI language',
        parentUserLabel: '#UT#Parent user',
        bottomOpenIdNoEditInfo: '#UT#⁴ This user cannot be edited. It is automatically created by translate5 following OpenID authentication.',
        clientPmSubRoles: '#UT#Accessible management overviews for PM role',
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
                                            xtype: 'combobox',
                                            itemId: 'coordinatorGroup',
                                            name: 'coordinatorGroup',
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
                                                fieldLabel: '{l10n.coordinatorGroup.title}',
                                            },
                                            listeners: {
                                                change: (box, newValue) => this.onCoordinatorGroupChange(box, newValue),
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

        this.toggleCoordinatorGroupField(form, selectedRoles);
    },

    toggleRequirementOfCustomersField: function (form, required) {
        const customersField = form.down('customers');

        customersField.allowBlank = ! required;
        customersField.forceSelection = required;
    },

    toggleCoordinatorGroupField: function (form, roles) {
        const coordinatorGroupField = form.down('#coordinatorGroup');

        if (roles.includes('jobCoordinator')) {
            coordinatorGroupField.setHidden(false);
            coordinatorGroupField.allowBlank = false;
            coordinatorGroupField.setDisabled(! form.getRecord().phantom);
            coordinatorGroupField.forceSelection = true;

            return;
        }

        coordinatorGroupField.setHidden(true);
        coordinatorGroupField.allowBlank = true;
        coordinatorGroupField.setDisabled(true);
        coordinatorGroupField.forceSelection = false;
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

    onCoordinatorGroupChange: function (fld, newValue) {
        /**
         * @type {Editor.model.admin.CoordinatorGroupModel}
         */
        const coordinatorGroup = fld.getStore().getById(newValue);
        const form = fld.up('form');

        if (null === coordinatorGroup) {
            this.updateCustomerField(form, Ext.getStore('customersStore').getData().items);

            return;
        }

        this.updateCustomerField(form, coordinatorGroup.get('customers'));
    },

    /**
     * loads the record into the form, does set the role checkboxes according to the roles value
     * @param {Editor.model.admin.User} record
     */
    loadRecord: function (record) {
        let me = this,
            form = me.down('form'),
            roles = record.getRoles(),
            adminRolesGroup = this.lookupReference('adminsFieldSet'),
            notRequireClientRolesGroup = this.lookupReference('notRequireClientFieldSet');

        form.loadRecord(record);

        if (record.isCoordinatorGroupUser() && ! Editor.app.authenticatedUser.getRoles().includes('jobCoordinator')) {
            adminRolesGroup.setHidden(true);
            notRequireClientRolesGroup.setHidden(true);
        }

        me.query('#rolesGroup checkbox').forEach(function (box) {
            const
                isAdminRole = me.isRoleFromGroup(box.initialConfig.value, 'admins'),
                isNotRequireClientRole = me.isRoleFromGroup(box.initialConfig.value, 'notRequireClient'),
                boxInitValue = box.initialConfig.value,
                hidden = (record.isCoordinatorGroupUser() && (isAdminRole || isNotRequireClientRole) && 'jobCoordinator' !== boxInitValue)
                    // existing user is not Coordinator group user. Can't set jobCoordinator role
                    || (record.get('userGuid').length !== 0 && ! record.isCoordinatorGroupUser() && 'jobCoordinator' === boxInitValue)
                    || (record.isCoordinatorGroupUser() && 'clientpm' === boxInitValue)
            ;

            box.setValue(roles.includes(boxInitValue));
            box.setHidden(hidden);
        });

        me.updateCustomerField(form, Ext.getStore('customersStore').getData().items);

        const coordinatorGroupField = form.down('#coordinatorGroup');
        const coordinatorGroupStore = coordinatorGroupField.getStore();

        Ext.Ajax.request({
            url: Editor.data.restpath + 'coordinatorgroup',
            method: 'GET',
            success: response => {
                const data = response.responseJson;
                coordinatorGroupStore.loadData(data.rows);

                const coordinatorGroup = coordinatorGroupStore.getById(record.get('coordinatorGroup'));
                coordinatorGroupField.setValue(coordinatorGroup);
                coordinatorGroupField.setHidden(! coordinatorGroup);

                if (coordinatorGroup) {
                    if (Editor.app.authenticatedUser.getRoles().includes('jobCoordinator')) {
                        form.down('customers').hide();
                    } else {
                        me.updateCustomerField(form, coordinatorGroup.get('customers'));
                    }
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