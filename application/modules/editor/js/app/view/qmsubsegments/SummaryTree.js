/*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor Javascript GUI and build on ExtJs 4 lib
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
 
 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty
 for any legal issue, that may arise, if you use these FLOSS exceptions and recommend
 to stick to GPL 3. For further information regarding this topic please see the attached 
 license.txt of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
Ext.define('Editor.view.qmsubsegments.SummaryTree', {
    extend: 'Ext.tree.TreePanel',
    alias: 'widget.qmSummaryTree',

    useArrows: true,
    rootVisible: false,
    multiSelect: true,
    singleExpand: true,
    height: 582,

    treepanel_text: 'Fehlertyp',
    treepanel_totalTotal: 'Gesamt',
    treepanel_total: 'Summe',

    initComponent: function() {
        var me = this;
        me.columns = me.getSeverityCols();
        me.callParent(arguments);
    },
    getSeverityCols: function(){
        var me = this;
        me.sevCols = new Array({
                    xtype: 'treecolumn',
                    text: this.treepanel_text,
                    flex: 10,
                    sortable: true,
                    dataIndex: 'text'
                },{
                    text: this.treepanel_totalTotal,
                    width: 71,
                    sortable: true,
                    align: 'right',
                    dataIndex: 'totalTotal'
                },{
                    text: this.treepanel_total,
                    width: 61,
                    dataIndex: 'total',
                    align: 'right',
                    sortable: true
                });
        var sev = Editor.data.task.get('qmSubSeverities');
        Ext.each(sev, function(severity,index, myself) {
            this.sevCols.push({
                text: this.treepanel_total+' '+ severity.text,
                    sortable: true,
                    width: 102,
                    align: 'right',
                    dataIndex: 'total'+ severity.text.charAt(0).toUpperCase() + severity.text.slice(1).toLowerCase()
            });
            this.sevCols.push({
                text: severity.text,
                    sortable: true,
                    width: 61,
                    align: 'right',
                    dataIndex: severity.text.toLowerCase()
            });
        },me);
        return me.sevCols;
    }
});