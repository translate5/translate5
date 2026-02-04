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

Ext.define('Editor.view.admin.coordinatorGroup.Panel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.coordinatorGroupPanel',

    requires: [
        'Editor.store.admin.CoordinatorGroupStore',
        'Editor.view.admin.coordinatorGroup.PanelViewController',
        'Editor.view.admin.coordinatorGroup.EditWindow',
        'Editor.view.admin.coordinatorGroup.EditCustomersWindow',
    ],

    bind: {
        title: '{l10n.coordinatorGroup.title}',
        tooltip: '{l10n.coordinatorGroup.tooltip}',
    },
    setTooltip: function(tooltip) {
        this.tab.el.dom.setAttribute('data-qtip', tooltip);
    },
    glyph: 'f47f@FontAwesome5FreeSolid',
    controller: 'coordinatorGroupPanelView',
    listeners: {
        activate: 'onGridActivate',
    },

    items: [
        {
            xtype: 'gridpanel',
            store: 'admin.CoordinatorGroupStore',
            columns: [
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'id',
                    bind: {
                        text: '{l10n.general.id}',
                    },
                },
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'name',
                    flex: 1,
                    bind: {
                        text: '{l10n.general.name}',
                    },
                    renderer: (v) => Ext.String.htmlEncode(v),
                },
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'description',
                    flex: 1,
                    bind: {
                        text: '{l10n.general.description}',
                    },
                    renderer: (v) => Ext.String.htmlEncode(v),
                },
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'customers',
                    sortable: false,
                    flex: 1,
                    bind: {
                        text: '{l10n.general.clients}',
                    },
                    filter: {
                        type: 'customer',
                    },
                    renderer: function (customers) {
                        if (customers.length === 0) {
                            return '';
                        }

                        return customers.map(customer => Ext.String.htmlEncode(customer.name)).join(', ');
                    },
                },
                {
                    xtype: 'actioncolumn',
                    items: [
                        {
                            glyph: 'f044@FontAwesome5FreeSolid',
                            handler: 'onEditClick',
                            tooltip: Editor.data.l10n.coordinatorGroup.iconTooltips.edit,
                            getClass: function (v, meta, rec) {
                                return rec.get('canEdit') ? '' : 'x-hidden';
                            },
                        },
                        {
                            glyph: 'f2ed@FontAwesome5FreeSolid',
                            tooltip: Editor.data.l10n.coordinatorGroup.iconTooltips.delete,
                            handler: 'onDeleteClick',
                            getClass: function (v, meta, rec) {
                                return rec.get('canDelete') ? '' : 'x-hidden';
                            },
                            margin: '0 0 0 10px',
                        },
                        {
                            tooltip: Editor.data.l10n.coordinatorGroup.iconTooltips.assignClient,
                            glyph: 'xf1ad@FontAwesome5FreeSolid',
                            handler: 'onEditCustomersClick',
                            getClass: function (v, meta, rec) {
                                return rec.get('canEdit') ? '' : 'x-hidden';
                            },
                            margin: '0 0 0 10px',
                        },
                    ],
                },
            ],
            listeners: {
                itemdblclick: function (table, record, row, column, event, button) {
                    this.up().getController().onEditClick(table, row, column, button, event, record);
                },
            },
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
                    itemId: 'reloadCoordinatorGroupBtn',
                    bind: {
                        text: '{l10n.general.reload}',
                        tooltip: '{l10n.coordinatorGroup.reloadBtnTooltip}',
                    },
                    listeners: {
                        click: 'onRefreshClick',
                    },
                },
                {
                    xtype: 'button',
                    glyph: 'f234@FontAwesome5FreeSolid',
                    itemId: 'addCoordinatorGroupBtn',
                    bind: {
                        text: '{l10n.general.addNew}',
                        tooltip: '{l10n.coordinatorGroup.addNewBtnTooltip}',
                    },
                    listeners: {
                        click: 'onCreateClick',
                    },
                },
            ],
        },
    ],
});
