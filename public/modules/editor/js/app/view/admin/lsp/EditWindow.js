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

Ext.define('Editor.view.admin.lsp.EditWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.lspEditWindow',
    requires: [
        'Editor.view.admin.customer.TagField',
    ],
    itemId: 'lspEditWindow',
    modal: true,
    layout: 'fit',
    width: 700,
    flex: 1,

    bind: {
        title: '{l10n.lsp.edit}'
    },

    items: [
        {
            xtype: 'form',
            items: [
                {
                    layout: {
                        type: 'vbox',
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
                            xtype: 'textfield',
                            name: 'name',
                            bind: {
                                fieldLabel: '{l10n.general.name}',
                            },
                            allowBlank: false,
                        },
                        {
                            xtype: 'textfield',
                            name: 'description',
                            bind: {
                                fieldLabel: '{l10n.general.description}',
                            },
                            allowBlank: false,
                        },
                        {
                            xtype: 'customers',
                            name: 'customerIds',
                            dataIndex: 'customers',
                            bind:{
                                fieldLabel: '{l10n.general.clients}',
                            },
                        },
                    ]
                },
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
                    xtype: 'tbfill'
                },
                {
                    xtype: 'button',
                    glyph: 'f0c7@FontAwesome5FreeSolid',
                    bind: {
                        text: '{l10n.general.save}',
                    },
                    handler: (button, event) => {
                        const form = button.up('lspEditWindow').down('form');

                        if (!form.isValid()) {
                            return;
                        }

                        const controller = Ext.ComponentQuery.query('lspPanel')[0].controller;
                        controller.onSaveClick(form.getValues(), form.getRecord());
                    }
                },
                {
                    xtype: 'button',
                    glyph: 'f00d@FontAwesome5FreeSolid',
                    bind: {
                        text: '{l10n.general.cancel}',
                    },
                    handler: () => Ext.ComponentQuery.query('lspPanel')[0].controller.onCancelEditClick(),
                }
            ]
        }
    ],

    loadRecord: function (record) {
        const customers = [];
        const customersStore = Ext.getStore('customersStore');

        for (const customerData of record.get('customers')) {
            const customer = customersStore.getById(customerData.id);

            if (customer) {
                customers.push(customer);
            }
        }

        const form = this.down('form');
        form.loadRecord(record);
        form.down('customers').setValue(customers);
    },
});
