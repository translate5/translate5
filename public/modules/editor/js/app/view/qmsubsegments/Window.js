
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * @class Editor.view.qmsubsegments.Window
 * @extends Ext.window.Window
 * @initalGenerated
 */
Ext.define('Editor.view.qmsubsegments.Window', {
    extend: 'Ext.window.Window',
    alias: 'widget.qmSummaryWindow',
    requires: ['Editor.view.qmsubsegments.SummaryTree'],
    height: 612,
    layout: 'fit',
    itemId: 'qmsummaryWindow',
    width: 1024,
    title: '#UT#QA Statistik',
    modal: true,
    tab_title_field: '#UT# In Feld: {0}',

    initComponent: function() {
        var me = this,
            fields = Editor.data.task.segmentFields(),
            editable = [];
        fields.each(function(field){
            if(field.get('editable')) {
                editable.push(field);
            }
        });
        
        editable = Editor.model.segment.Field.listSort(editable);
        
        if(editable.length == 1) {
            me.items = [me.getField(editable[0])];
        }
        else {
            me.items = [me.getTabbed(editable)];
        }
        
        me.callParent(arguments);
    },
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                title: me.title //see EXT6UPD-9
            };
        
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    getField: function(field) {
        var me = this;
        return {
            xtype: 'qmSummaryTree',
            //creating each time a new store is ugly but simplifies the handling with different tasks and refreshing the store content
            store: Ext.create('Editor.store.QmSummary',{
                storeId: 'QmSummary'+field.get('name'),
                proxy: Ext.applyIf({
                    extraParams: {
                        type: field.get('name')
                    }
                }, Editor.store.QmSummary.prototype.proxy)
            }),
            title: Ext.String.format(me.tab_title_field, field.get('label'))
        };
    },
    getTabbed: function(fields) {
        var me = this;
        return {
            xtype: 'tabpanel',
            items: Ext.Array.map(fields, function(field){
                return me.getField(field);
            })
        };
    }
});