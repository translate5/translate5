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

Ext.define('Editor.view.admin.coordinatorGroup.EditCustomersWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.coordinatorGroupEditCustomerWindow',
    requires: [
        'Editor.view.admin.customer.TagField',
    ],
    itemId: 'coordinatorGroupEditCustomerWindow',
    modal: true,
    layout: 'fit',
    width: 600,
    flex: 1,
    style: {
        'padding': '10px'
    },
    bind: {
        title: '{l10n.coordinatorGroup.customers.title}'
    },

    items: [
        {
            xtype: 'gridpanel',
            layout: 'fit',
            store: {
                xtype: 'store',
                data: [] // Initially empty, will be set dynamically
            },
            columns: [
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'id',
                    text: 'Id',
                    width: 20,
                    filter: {
                        type: 'number'
                    }
                },
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'name',
                    text: 'Name',
                    flex: 1,
                    filter: {
                        type: 'string'
                    },
                    renderer: v => Ext.String.htmlEncode(v)
                },
                {
                    xtype: 'actioncolumn',
                    items: [
                        {
                            tooltip: Editor.data.l10n.coordinatorGroup.customers.iconTooltips.delete,
                            glyph: 'f2ed@FontAwesome5FreeSolid',
                            handler: (table, row, column, button, event, customer) => {
                                Ext.ComponentQuery.query('coordinatorGroupPanel')[0].controller.unassignCustomer(
                                    event.view.up('coordinatorGroupEditCustomerWindow').down('form').getRecord(),
                                    customer
                                );
                            },
                        },
                    ],
                },
            ],
        },
    ],
    dockedItems: [
        {
            xtype: 'panel',
            bind: {
                html: '{l10n.coordinatorGroup.customers.infobox}',
            },
            cls: 'infobox-panel'
        },
        {
            xtype: 'toolbar',
            dock: 'top',
            layout: {
                type: 'fit',
                pack: 'start'
            },
            items: [
                {
                    xtype: 'form',
                    items: [
                        {
                            layout: {
                                type: 'hbox',
                                pack: 'start',
                                align: 'stretch'
                            },
                            xtype: 'container',
                            padding: 20,
                            items: [
                                {
                                    xtype: 'hiddenfield',
                                    name: 'id',
                                },
                                {
                                    xtype: 'combobox',
                                    name: 'customer',
                                    itemId: 'customer',
                                    dataIndex: 'customers',
                                    store: {
                                        xtype: 'store',
                                        data: [] // Initially empty, will be set dynamically
                                    },
                                    queryMode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    allowBlank: false,
                                    typeAhead: true,
                                    anyMatch: true,
                                    forceSelection: true,
                                    bind: {
                                        fieldLabel: '{l10n.general.clients}',
                                    },
                                    labelWidth: 80,
                                    width: 480,
                                    anchor: '100%'
                                },
                                {
                                    xtype: 'button',
                                    glyph: 'f0c1@FontAwesome5FreeSolid',
                                    bind: {
                                        tooltip: '{l10n.coordinatorGroup.customers.assignButton}',
                                    },
                                    handler: (button, event) => {
                                        const form = button.up('coordinatorGroupEditCustomerWindow').down('form');

                                        if (!form.isValid()) {
                                            return;
                                        }

                                        const customer = Ext.getStore('customersStore').getById(
                                            form.getValues()['customer']
                                        );

                                        const controller = Ext.ComponentQuery.query('coordinatorGroupPanel')[0].controller;
                                        controller.assignCustomer(form.getRecord(), customer);
                                    }
                                },
                            ]
                        },
                    ]
                },
            ]
        }
    ],

    loadRecord: function (record) {
        const form = this.down('form');
        const customersStore = Ext.getStore('customersStore');

        form.loadRecord(record);

        const parentCoordinatorGroup = Ext.getStore('admin.CoordinatorGroupStore').getById(record.get('parentId'));
        let allowedCustomers = customersStore.getData().items;

        if (parentCoordinatorGroup) {
            allowedCustomers = parentCoordinatorGroup.get('customers').map((customer) => customersStore.getById(customer.id));
        }

        const customersSet = [];

        for (const customerData of record.get('customers')) {
            const customer = customersStore.getById(customerData.id);

            if (customer) {
                customersSet.push(customer);
                allowedCustomers = allowedCustomers.filter((item) => item.get('id') !== customer.get('id'));
            }
        }

        form.down('#customer').getStore().setData(allowedCustomers);
        this.down('gridpanel').getStore().setData(customersSet);
        form.reset();
    },
});
