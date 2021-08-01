
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

Ext.define('Editor.view.admin.task.KpiWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.adminTaskKpiWindow',
    itemId: 'kpiWindow',
    cls: 'kpiWindow',
    minHeight : 200,
    width : 450,
    height:300,
    autoHeight: true,
    autoScroll: true,
    modal : true,
    bodyPadding:10,
    layout:'fit',
    strings: {
        closeBtn: '#UT#Fenster schließen',
        averageProcessingTimeToolTip: '#UT#Durchschnittliche Zeit von der Zuweisung bis zum Abschluss einer Aufgabe.',
        excelExportUsageToolTip: '#UT#Prozent der Aufgaben, bei denen der Excel-Export der Segmenttabelle genutzt wurde.',
        filterInfoLabel:'#UT#Durchschnittliche Zeiten beziehen sich auf die Zeiten in der aktuellen Filterung.'
    },
    
    initConfig: function(instanceConfig) {
        var me = this,
            config;
        config = {
            items: [{
            	
                xtype: 'panel',
                dock: 'top',
                border:false,
                items: [{
                    xtype: 'displayfield',
                    value:me.strings.filterInfoLabel
                },{
                    xtype: 'displayfield',
                    itemId: 'kpi-average-processing-time-display',
                    margin: 5,
                    cls:'displayFieldInfoIcon',
                    autoEl: {
                        tag: 'div',
                        'data-qtip': me.strings.averageProcessingTimeToolTip
                    }
                },{
                    xtype: 'displayfield',
                    itemId: 'kpi-excel-export-usage-display',
                    margin: 5,
                    cls:'displayFieldInfoIcon',
                    autoEl: {
                        tag: 'div',
                        'data-qtip': me.strings.excelExportUsageToolTip
                    }
                }],
              }],
              dockedItems: [{
                  xtype: 'toolbar',
                  dock: 'bottom',
                  ui: 'footer',
                  items: [{
                      xtype: 'tbfill'
                  },{
                      xtype: 'button',
                      glyph: 'f00d@FontAwesome5FreeSolid',
                      text: me.strings.closeBtn,
                      listeners:{
                          click:function(){
                             this.up('window').close();
                          }
                      }
                  }]
              }]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});