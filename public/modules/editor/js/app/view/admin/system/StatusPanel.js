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

Ext.define('Editor.view.admin.system.StatusPanel', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.editorSystemStatus',
    itemId: 'adminSystemStatus',
    bodyStyle: 'border: 0',
    strings: {
        reload: "re-check"
    },
    loader: {
        url: Editor.data.restpath + 'index/systemstatus',
        loadOnRender: true,
        loadMask: 'loading... (timeout after 120 seconds)',
        ajaxOptions: {
            timeout: 120000 //after 120 seconds the result should be there
        },
        failure: function(loader, response, options) {
            if(response.status > 0) {
                Editor.app.getController('ServerException').handleException(response);
            }

            if(response.timedout) {
                response.statusText += ' - TIMEOUT'
            }

            if(loader && loader.target) {
                let msg = [
                    '<div style="margin: 20px;"><h2 style="color:red;">Error on loading system-check data: <i>'+response.statusText+'</i></h2>',
                    'Please press re-check button.',
                    '',
                    'If you get a timeout, the check of some services may need to long.',
                    'In that case please re-check <a href="https://confluence.translate5.net/x/AwAzGg" target="_blank">directly on the server via CLI</a>',
                    '</div>'
                ];
                loader.target.update(msg.join('<br>'));
            }
        }
    },
    initConfig: function(instanceConfig) {
        let me = this,
            config;

        config = {
            glyph: 'xf022@FontAwesome5FreeSolid',
            title: "System Check",
            layout: { type: 'vbox', pack: 'start', align: 'stretch' },
            //title: me.strings.title,
            dockedItems: [{
                xtype: 'toolbar',
                dock: 'top',
                enableOverflow: true,
                items: [{
                    xtype: 'button',
                    itemId: 'userPrefReload',
                    glyph: 'f2f1@FontAwesome5FreeSolid',
                    text: me.strings.reload,
                    handler: function() {
                        me.loader.load();
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
