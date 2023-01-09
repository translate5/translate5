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

/**
 * Shows the Qualities for a Segment and enables to set those as false positive or not
 */
Ext.define('Editor.view.quality.FalsePositives', {
    extend: 'Ext.form.FieldSet',
    alias: 'widget.falsePositives',
    requires: [
        'Editor.view.quality.FalsePositivesController',
        'Ext.grid.column.Check'
    ],
    controller: 'falsePositives',
    cls: 'segmentQualities',
    padding: 0,
    bind: {
        title: '{l10n.falsePositives.legend.fixed}'
    },
    items: [{
        xtype: 'grid',
        userCls: 't5falsePositivesGrid',
        border: 0,
        width: 277,
        height: 'auto',
        store: {
            type: 'json',
            data: []
        },
        columns: [{
            width: 35,
            xtype: 'checkcolumn',
            menuDisabled: true,
            dataIndex: 'falsePositive',
            sortable: false,
            bind: {
                text: '{l10n.falsePositives.grid.falsePositive}'
            },
            listeners: {
                checkchange: 'onFalsePositiveChanged',
            }
        }, {
            flex: 1,
            menuDisabled: true,
            sortable: false,
            dataIndex: 'text',
            bind: {
                text: '{l10n.falsePositives.grid.text}'
            },
            renderer: 'falsepositivesGridTextRenderer'
        }, {
            xtype: 'widgetcolumn',
            text: '<span class="fa fa-magnifying-glass-arrow-right"></span>',
            width: 35,
            bind: {
                tooltip: '{l10n.falsePositives.grid.similarQty.tooltip}'
            },
            dataIndex: 'similarQty',
            padding: 0,
            menuDisabled: true,
            widget: {
                xtype: 'button',
                width: 26,
                height: 26,
                margin: 0,
                padding: 0,
                ui: "default-toolbar-small",
                setUi: function(ui) {this.setUI(ui);},
                bind: {
                    text: '{record.similarQty}',
                    disabled: '{record.similarQty == 0 || !record.falsePositiveChanged}',
                    tooltip: '{l10n.falsePositives.grid.similarQty.button}',
                    ui: '{record.similarQty == 0 || !record.falsePositiveChanged ? "default-toolbar-small" : "default"}'
                },
                handler: 'onFalsePositiveSpread'
            }
        }]
    }],
    initConfig: function(instanceConfig) {
        var config = {
            title: this.title,
        };
        if (instanceConfig) {
            this.self.getConfigurator().merge(this, config, instanceConfig);
        }
        return this.callParent([config]);
    },

    /**
     * Creates the checkbox components after a store load & evaluates the visibility of our view
     * @param {Editor.model.quality.Segment[]} records
     */
    loadFalsifiable: function(records){
        this.down('grid').getStore().setData(
            Ext.Array.filter(records, rec => rec.get('falsifiable'))
        );
    }
});

