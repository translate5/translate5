
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

Ext.define('Editor.view.task.ConfirmationWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.taskConfirmationWindow',
    closeAction: 'destroy',
    closable: false,
    modal: false,
    resizable: false,
    layout: {
        type: 'vbox',
        align: 'stretch'
    },
    strings: {
        title: "#UT#Aufgabe bestätigen?",
        confirmMsg: "#UT#Möchten Sie die Aufgabe bestätigen? <br/> Ohne Bestätigung kann diese nicht bearbeitet werden.",
        confirmCompetitive: "#UT#<br/><br/>Allen anderen Benutzern wird die Aufgabe entzogen<br/> und sie wird ausschließlich in Ihrer Verantwortung liegen.",
        confirmBtn: '#UT#bestätigen'
    },
    border: false,
    y: '5%',
    onEsc: Ext.emptyFn,
    titleCollapse: true,
    collapsible: true,
    initConfig : function(instanceConfig) {
        var me = this,
            config,
            msg = me.strings.confirmMsg;
              
        if(Editor.data.task.get('usageMode') == Editor.model.admin.Task.USAGE_MODE_COMPETITIVE) {
            msg += me.strings.confirmCompetitive;
        }
                
        config = {
            title: me.strings.title,
            items: [{
                xtype: 'container',
                padding: 10,
                html: msg
            }],
            dockedItems: [{
                xtype: 'toolbar',
                ui: 'footer',
                dock: 'bottom',
                enableFocusableContainer: false,
                ariaRole: null,
                layout: {
                    pack: 'center'
                },
                items: [{
                    xtype: 'button',
                    text: me.strings.confirmBtn,
                    iconCls: 'task-confirmation'
                }]
            }]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});
