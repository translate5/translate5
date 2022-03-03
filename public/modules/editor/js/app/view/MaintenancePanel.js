
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

/*
	@class Editor.view.MntPanel
*/
Ext.define('Editor.view.MaintenancePanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.maintenancePanel',
    formats: {
        en: 'l, F d, Y h:i:s A (T)',
        de: 'l, d. F, Y H:i:s (T)'
    },
    tpl: null,
    maintenanceMessage: '#UT#<p>Achtung! In Kürze wird eine Wartung am System durchgeführt.</p><p>Die Anwendung wird ab diesem Zeitpunkt für kurze Zeit nicht erreichbar sein.</p><p>Geplanter Zeitpunkt: {0}</p>',
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
        		frame: false,
        		border: false,
                listeners: {
                    render: function(c) {
                        me.isMaintenanceMode();
                    }
                }
            };
        
        config.tpl = new Ext.XTemplate(
            '<div class="maintenanceInfoPanel"><strong>',
            '<tpl if="date">',
            //the dateformat is added via string format because of two reasons: our internal translation mechanism struggles with the escaped quotes and its just not sexy in the to be translated texts
            Ext.String.format(me.maintenanceMessage, '{[this.dateFormat(values[\'date\'])]}'), 
            '</tpl>',
            '<tpl if="msg">',
            '<p>{msg}</p>',
            '</tpl>',
            '</strong></div>', {
                dateFormat: function(date) {
                    var format = me.formats[Editor.data.locale] || me.formats.en;
                    return Ext.Date.format(Ext.Date.parse(date, "c"), format);
                }
            }
        );
        // If maintenance is over, destroy this panel
        Ext.Ajax.on('requestcomplete',function(owner, response, options){
            var data = response.getAllResponseHeaders();
            data.date = data['x-translate5-shownotice'];
    		data.msg  = data['x-translate5-maintenance-message'];
			if(!data['x-translate5-shownotice'] && !data['x-translate5-maintenance-message']){
                this.destroy();
            }
        }, this);
        
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
                    method: 'HEAD',
                    url:Editor.data.restpath+'/index/applicationState',
                    failure: function(response){
                        if(response.status === 503){
                            location.href=Editor.data.loginUrl;
                        }
                    }
                });
            },
            interval: 10000
          });
    }
});
