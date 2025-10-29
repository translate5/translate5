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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.view.LanguageResources.TmOverviewPanel
 * @extends Ext.panel.Panel
 */
Ext.define('Editor.view.LanguageResources.CustomerTmAssoc', {
    extend : 'Ext.grid.Panel',
    requires: [
        'Editor.view.LanguageResources.CustomerTmAssocController',
    ],
    alias: 'widget.customerTmAssoc',
    controller: 'customerTmAssoc',
    itemId: 'customerTmAssoc',
    stateful: true,
    stateId: 'editor.customerTmAssoc',
    helpSection: 'languageresource',
    glyph: 'xf1c0@FontAwesome5FreeSolid',
    cls: 'tmOverviewPanel',
    layout: {
        type: 'fit'
    },
    routePrefix: 'client/:clientId/',
    bind: {
        title: '{l10n.customerTmAssoc.title}',
        store: '{customersLanguageResourceStore}'
    },
    selModel: {
        pruneRemoved: false,
    },
    plugins: ['cellediting'],
    features: [{
        ftype: 'grouping',
        hideGroupedHeader: true,
        enableGroupingMenu: false
    }],
    columns: [
        {
            xtype: 'gridcolumn',
            width: 50,
            text: 'ID',
            dataIndex: 'id'
        },
        {
            xtype: 'gridcolumn',
            width: 50,
            dataIndex: 'serviceName',
            hidden: true,
            renderer: (value) => Ext.String.htmlEncode(value),
            bind: {
                text: '{l10n.customerTmAssoc.serviceName}'
            }
        },
        {
            xtype: 'gridcolumn',
            width: 390,
            dataIndex: 'name',
            flex: 1,
            renderer: (value) => Ext.String.htmlEncode(value),
            bind: {
                text: '{l10n.customerTmAssoc.name}'
            }
        },
        {
            xtype: 'gridcolumn',
            width: 100,
            dataIndex: 'sourceLang',
            renderer : 'langRenderer',
            cls: 'source-lang',
            bind: {
                text: '{l10n.customerTmAssoc.sourceLang}'
            }
        },
        {
            xtype: 'gridcolumn',
            width: 100,
            dataIndex: 'targetLang',
            renderer : 'langRenderer',
            cls: 'target-lang',
            bind: {
                text: '{l10n.customerTmAssoc.targetLang}'
            }
        },
        {
            xtype: 'checkcolumn',
            width: 120,
            dataIndex: 'hasClientAssoc',
            hidden: Editor.data.app.user.restrictedClientIds.length === 1,
            bind: {
                text: '{l10n.customerTmAssoc.hasClientAssoc}',
                tooltip: '{l10n.customerTmAssoc.hasClientAssoc}'
            }
        },
        {
            xtype: 'checkcolumn',
            width: 165,
            dataIndex: 'hasReadAccess',
            bind: {
                text: '{l10n.customerTmAssoc.hasReadAccess}',
                tooltip: '{l10n.customerTmAssoc.hasReadAccess}'
            }
        },
        {
            xtype: 'checkcolumn',
            width: 165,
            dataIndex: 'hasWriteAccess',
            bind: {
                text: '{l10n.customerTmAssoc.hasWriteAccess}',
                tooltip: '{l10n.customerTmAssoc.hasWriteAccess}'
            },
            renderer: function(value, meta, record) {
                return record.get('resourceType') === 'tm' ? this.defaultRenderer(value, meta) : '';
            }
        },
        {
            xtype: 'checkcolumn',
            width: 125,
            dataIndex: 'hasPivotAccess',
            bind: {
                text: '{l10n.customerTmAssoc.hasPivotAccess}',
                tooltip: '{l10n.customerTmAssoc.hasPivotAccess}'
            }
        },
        {
            xtype: 'gridcolumn',
            renderer: 'penaltyRenderer',
            width: 74,
            dataIndex: 'penaltyGeneral',
            editor: {
                xtype: 'combobox',
                viewModel: 'customerPanel',
                forceSelection: true,
                bind: {
                    store: '{penaltyGeneral}'
                }
            },
            menuDisabled: true,
            text:
                '<span style="color: #df0000;"><span class="fa fa-chevron-down"></span><span> 1 </span></span>' +
                '<span class="fa fa-edit" style="position: relative; top: 1px;"></span>',
            bind: {
                tooltip: '{l10n.languageResourceTaskAssocPanel.penaltyGeneral}'
            }
        },
        {
            xtype: 'gridcolumn',
            renderer: 'penaltyRenderer',
            width: 74,
            dataIndex: 'penaltySublang',
            editor: {
                xtype: 'combobox',
                viewModel: 'customerPanel',
                forceSelection: true,
                bind: {
                    store: '{penaltySublang}'
                }
            },
            menuDisabled: true,
            text:
                '<span style="color: #df0000;"><span class="fa fa-chevron-down"></span><span> 2 </span></span>' +
                '<span class="fa fa-edit" style="position: relative; top: 1px;"></span>',
            bind: {
                tooltip: '{l10n.languageResourceTaskAssocPanel.penaltySublang}'
            }
        }
    ],
    dockedItems: [{
        xtype: 'toolbar',
        dock: 'top',
        enableOverflow: true,
        hidden: Editor.data.app.user.restrictedClientIds.length === 1,
        items: [
            {
                iconCls: 'x-fa fa-filter',
                itemId: 'assocOnly',
                enableToggle: true,
                bind: {
                    text: '{l10n.customerTmAssoc.onlyListAssigned}',
                    pressed: '{langres.assocOnlyButtonPressed}'
                }
            }
        ]
    }],
});
