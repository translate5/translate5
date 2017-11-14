
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

/*
	@class Editor.view.MntPanel
*/
Ext.define('Editor.view.MaintenancePanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.maintenancePanel',
    maintenanceMessage: '#UT#<p>Achtung! In Kürze wird eine Wartung am System durchgeführt.</p><p>Translate5 wird zu diesem Zeitpunkt kurzfristig nicht erreichbar sein.</p><p>Geplanter Zeitpunkt: {0}</p>',
    initConfig: function(instanceConfig) {
        var me = this,
            date = instanceConfig.maintenanceStartDate ? instanceConfig.maintenanceStartDate : Editor.data.maintenance.startDate,
            date = Ext.Date.format(Ext.Date.parse(date, Editor.DATE_ISO_FORMAT), Ext.Date.defaultFormat + ' ' + Ext.form.field.Time.prototype.format),
            config = {
        		frame: false,
        		border: false,
                html:'<div class="maintenanceInfoPanel"><strong>'+Ext.String.format(me.maintenanceMessage,date)+'</strong></div>',
                listeners: {
                    render: function(c) {
                        me.isMaintenanceMode();
                    }
                }
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    //check on each 'n' seconds if the maintenance is runing
    isMaintenanceMode:function(){
        Ext.TaskManager.start({
            run: function() {
                Ext.Ajax.request({
                    url:Editor.data.restpath+'/index/applicationState',
                    failure: function(response){
                        if(response && response.status == 503){
                            location.href=Editor.data.loginUrl;
                        }
                    }
                });
            },
            interval: 10000
          });
    }
});
