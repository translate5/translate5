
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

/**
 * @class Editor.view.quality.statistics.Window
 * @extends Ext.window.Window
 * @initalGenerated
 */
Ext.define('Editor.view.quality.statistics.Tree', {
    extend: 'Ext.tree.TreePanel',
    alias: 'widget.qualityStatisticsTree',

    useArrows: true,
    rootVisible: false,
    multiSelect: true,
    singleExpand: true,
    height: 582,

    treepanel_text: 'Fehlertyp',
    treepanel_totalTotal: 'Gesamt',
    treepanel_total: 'Summe',

    initConfig: function(instanceConfig) {
        var me = this,

        config = {
            columns: me.getSeverityCols()
        };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    getSeverityCols: function(){
        var me = this;
        me.sevCols = new Array({
                    xtype: 'treecolumn',
                    text: this.treepanel_text,
                    flex: 10,
                    sortable: true,
                    renderer: function(v, md, rec) {
                        md.tdAttr = 'data-qtip="' + rec.get('text')+' ('+rec.get('qmtype')+')'+'"';
                        return v;
                    },
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
        var sev = Editor.data.task.getMqmSeverities();
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