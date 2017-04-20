
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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
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
 * @class Editor.view.segments.column.SegmentNrInTask
 * @extends Editor.view.ui.segments.column.SegmentNrInTask
 * @initalGenerated
 */
Ext.define('Editor.view.segments.column.SegmentNrInTask', {
    extend: 'Ext.grid.column.Column',

    itemId: '',
    width: 50,
    tdCls: 'segmentNrInTask',
    dataIndex: 'segmentNrInTask',
    text: 'Nr.',
    alias: 'widget.segmentNrInTaskColumn',
    mixins: ['Editor.view.segments.column.BaseMixin'],
    isErgonomicVisible: true,
    isErgonomicSetWidth: true,
    ergonomicWidth: 60,
    otherRenderers: null,
    filter: {
        type: 'numeric'
    },
    tableTpl: ['<table>',
    '<tpl for=".">',
        '<tpl if="value">',
          '<tr><th>{name}</th><td>{value}</td></tr>',
        '</tpl>',
    '</tpl></table>'],
    initComponent: function() {
        this.scope = this; //so that renderer can access this object instead the whole grid.
        this.tableTpl = new Ext.XTemplate(this.tableTpl);
        this.callParent(arguments);
    },
    initOtherRenderers: function() {
        var me = this,
            grid = me.up('grid');

        me.otherRenderers = {};
        Ext.Array.each(grid.columns, function(item) {
            if(!item.showInMetaTooltip) {
                return;
            }
            me.otherRenderers[item.dataIndex] = item;
        });
    },
    editor: {
        xtype: 'displayfield',
        getModelData: function() {
            return null;
        },
        ownQuicktip: true,
        renderer: function(value, field) {
            var context = field.ownerCt.context,
                qtip, cell;
            if(context && context.row){
                cell = Ext.fly(context.row).down('td.segmentNrInTask');
                if(cell) {
                    qtip = cell.getAttribute('data-qtip');
                    field.getEl().dom.setAttribute('data-qtip', qtip);
                }
            }
            return value;
        }
    },
    renderer: function(v, meta, record) {
        //TODO this should be done in a native Editor.view.ToolTip implementation to create the ToolTips on the fly. 
        // Since invocation of ToolTip is bound to QmSubSegments,
        // the invocation should be changed in a general manner before reusing the general Editor.ToolTip class
        var me = this,
            data = [];

        if(!me.otherRenderers) {
            me.initOtherRenderers();
        }

        Ext.Object.each(me.otherRenderers, function(id, column){
            var scope = column.scope || me.up('grid'),
                obj = {
                    name: column.text
                };

            if (column.renderer) {
                obj.value = column.renderer.apply(scope, [record.get(id), {}, record]);
            }
            else {
                obj.value = record.get(id);
            }

            if(id == 'comments'){
                //cleaning up comment add/edit icon
                obj.value = obj.value.replace(/^<img class="(add|edit)"[^>]+>/, '');
            }

            data.push(obj);
        })

        meta.tdAttr = 'data-qtip="'+Ext.String.htmlEncode(me.tableTpl.apply(data))+'"';
        return v;
    }
});