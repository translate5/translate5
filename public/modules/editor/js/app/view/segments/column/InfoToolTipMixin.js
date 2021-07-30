
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
 * @class Editor.view.segments.column.InfoToolTipMixin
 */
Ext.define('Editor.view.segments.column.InfoToolTipMixin', {
    tableTpl: ['<table>',
    '<tpl for=".">',
        '<tpl if="value">',
          '<tr><th>{name}</th><td>{value}</td></tr>',
        '</tpl>',
    '</tpl></table>'],
    initOtherRenderers: function() {
        var me = this,
            grid = me.up('grid');

        me.tableTpl = new Ext.XTemplate(me.tableTpl);
        me.otherRenderers = {};
        Ext.Array.each(grid.getColumns(), function(item) {
            if(!item.showInMetaTooltip) {
                return;
            }
            me.otherRenderers[item.dataIndex] = item;
        });
        /**
         * Fires an event on the columns using this mixin. On this way more otherRenderers can be added
         */
        me.fireEvent('initOtherRenderers', me.otherRenderers);
    },
    /**
     * Render the QTip info data about the segment
     * @param {Editor.model.Segment}
     * @return {String}
     */
    renderInfoQtip: function(record) {
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
        });
        data.push({name: 'MID', value: record.get('mid')});
        return Ext.String.htmlEncode(me.tableTpl.apply(data));
    }
});