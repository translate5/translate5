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

Ext.define('Editor.view.admin.lsp.Panel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.lspPanel',

    requires: [
        'Editor.store.admin.LspStore',
        'Editor.view.admin.lsp.PanelViewController',
        'Editor.view.admin.lsp.EditWindow',
    ],

    bind: {
        title: '{l10n.lsp.title}',
    },

    glyph: 'f47f@FontAwesome5FreeSolid',
    controller: 'lspPanelView',
    listeners: {
        activate: 'onGridActivate',
    },

    items: [
        {
            xtype: 'gridpanel',
            store: 'admin.LspStore',
            columns: [
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'id',
                    bind: {
                        text: '{l10n.lsp.columns.id}',
                    }
                },
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'name',
                    flex: 1,
                    bind: {
                        text: '{l10n.lsp.columns.name}',
                    }
                },
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'description',
                    flex: 1,
                    bind: {
                        text: '{l10n.lsp.columns.description}',
                    }
                },
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'customers',
                    sortable: false,
                    flex: 1,
                    bind: {
                        text: '{l10n.lsp.columns.clients}',
                    },
                    filter: {
                        type: 'customer',
                    },
                    renderer: function (customers) {
                        if (customers.length === 0) {
                            return '';
                        }

                        return customers.map(customer => customer.name).join(', ');
                    },
                },
                {
                    xtype: 'actioncolumn',
                    items: [
                        {
                            bind: {
                                tooltip: '{l10n.lsp.editBtn}',
                            },
                            // hidden: 'editLsp',
                            glyph: 'f044@FontAwesome5FreeSolid',
                            handler:'onEditClick',
                        },
                        {
                            bind: {
                                tooltip: '{l10n.lsp.deleteBtn}',
                            },
                            // hidden: 'deleteLsp',
                            glyph: 'f2ed@FontAwesome5FreeSolid',
                            handler: 'onDeleteClick',
                        }
                    ],
                }
            ],
        },
    ],

    dockedItems: [
        {
            xtype: 'toolbar',
            dock: 'top',
            enableOverflow: true,
            items: [
                {
                    xtype: 'button',
                    glyph: 'f2f1@FontAwesome5FreeSolid',
                    itemId: 'reloadLspBtn',
                    bind: {
                        text: '{l10n.lsp.reloadBtn}',
                        tooltip: '{l10n.lsp.reloadBtnTooltip}',
                    },
                    listeners: {
                        click: 'onRefreshClick',
                    },
                },
                {
                    xtype: 'button',
                    glyph: 'f234@FontAwesome5FreeSolid',
                    itemId: 'addLspBtn',
                    bind: {
                        text: '{l10n.lsp.addNewBtn}',
                        tooltip: '{l10n.lsp.addNewBtnTooltip}',
                    },
                    listeners: {
                        click: 'onCreateClick',
                    },
                }
            ]
        }
    ]
});
