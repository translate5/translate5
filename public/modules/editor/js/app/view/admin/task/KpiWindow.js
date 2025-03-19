
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
    minHeight:200,
    width:510,
    height: Editor.data.statistics.enabled ? 460 : 300,
    autoHeight: true,
    autoScroll: true,
    modal : true,
    bodyPadding:10,
    layout:'fit',
    
    initConfig: function(instanceConfig) {
        var me = this,
        l10n = Editor.data.l10n.taskKpiWindow,
        config = {
            items: [{
            	
                xtype: 'panel',
                dock: 'top',
                margin: '0 0 0 5px',
                border:false,
                items: [{
                    xtype: 'displayfield',
                    value: l10n.filterInfoLabel
                },{
                    xtype: 'displayfield',
                    itemId: 'kpi-average-processing-time-display',
                    cls:'displayFieldInfoIcon',
                    autoEl: {
                        tag: 'div',
                        'data-qtip': l10n.averageProcessingTimeToolTip
                    }
                },{
                    xtype: 'displayfield',
                    itemId: 'kpi-excel-export-usage-display',
                    cls:'displayFieldInfoIcon',
                    autoEl: {
                        tag: 'div',
                        'data-qtip': l10n.excelExportUsageToolTip
                    }
                },{
                    xtype: 'displayfield',
                    itemId: 'kpi-levenshtein-distance-start-display',
                    hidden: !Editor.data.statistics.enabled
                },{
                    xtype: 'displayfield',
                    itemId: 'kpi-postediting-time-start-display',
                    hidden: !Editor.data.statistics.enabled
                },{
                    xtype: 'container',
                    hidden: !Editor.data.statistics.enabled,
                    layout: {
                        type: 'hbox'
                    },
                    items: [{
                        xtype: 'displayfield',
                        itemId: 'kpi-postediting-time-display'
                    }, {
                        xtype: 'displayfield',
                        cls: 'displayFieldInfoIcon',
                        margin: '0 0 -16px 0',
                        autoEl: {
                            tag: 'div',
                            'data-qtip': l10n.processingTimeToolTip,
                            'data-hide': false
                        }
                    }]
                },{
                    xtype: 'container',
                    hidden: !Editor.data.statistics.enabled,
                    layout: {
                        type: 'hbox'
                    },
                    items: [{
                        xtype: 'displayfield',
                        itemId: 'kpi-levenshtein-distance-display'
                    }, {
                        xtype: 'displayfield',
                        cls: 'displayFieldInfoIcon',
                        margin: '0 0 -16px 0',
                        autoEl: {
                            tag: 'div',
                            'data-qtip': l10n.levenshteinDistanceToolTip,
                            'data-hide': false
                        }
                    }]
                },{
                    xtype: 'container',
                    hidden: !Editor.data.statistics.enabled,
                    layout: {
                        type: 'hbox'
                    },
                    items: [{
                        xtype: 'displayfield',
                        itemId: 'kpi-postediting-time-total-display'
                    }, {
                        xtype: 'displayfield',
                        cls: 'displayFieldInfoIcon',
                        margin: '0 0 -16px 0',
                        autoEl: {
                            tag: 'div',
                            'data-qtip': l10n.processingTimeTotalToolTip,
                            'data-hide': false
                        }
                    }]
                },{
                    xtype: 'container',
                    hidden: !Editor.data.statistics.enabled,
                    layout: {
                        type: 'hbox'
                    },
                    items: [{
                        xtype: 'displayfield',
                        itemId: 'kpi-levenshtein-distance-original-display'
                    }, {
                        xtype: 'displayfield',
                        cls: 'displayFieldInfoIcon',
                        margin: '0 0 -16px 0',
                        autoEl: {
                            tag: 'div',
                            'data-qtip': l10n.levenshteinDistanceOriginalToolTip,
                            'data-hide': false
                        }
                    }]
                },{
                    xtype: 'displayfield',
                    itemId: 'kpi-levenshtein-distance-end-display',
                    hidden: !Editor.data.statistics.enabled
                },{
                    xtype: 'displayfield',
                    itemId: 'kpi-postediting-time-end-display',
                    hidden: !Editor.data.statistics.enabled
                }]
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
                      text: l10n.closeBtn,
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