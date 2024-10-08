
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
 * @class Editor.view.segments.grid.Toolbar
 * @extends Ext.toolbar.Toolbar
 * @initalGenerated
 */
Ext.define('Editor.view.segments.grid.Header', {
    extend: 'Ext.panel.Header',
    alias: 'widget.segmentsHeader',
    requires: [
        'Editor.view.segments.grid.HeaderViewController',
        'Editor.view.ApplicationInfoPanel',
        'Editor.view.help.HelpButton'
    ],
    controller: 'segmentsHeader',
    strings: {
        progressTooltip:'#UT#% abgeschlossen durch zugewiesene Benutzer im aktuellen Workflowschritt',
        leaveBtn: '#UT#Zurück zur Aufgabenübersicht',
        leaveTaskWindowTitle:'#UT#Zurück zur Aufgabenübersicht',
        closeBtn: '#UT#Anwendung verlassen',
        showDesc: '#UT#Projektbeschr. anzeigen',
        hideDesc: '#UT#Projektbeschr. ausblenden',
        leaveTaskWindowMessage:'#UT#Möchten Sie die Aufgabe beenden und zurücksenden, oder möchten Sie diese später weiterbearbeiten?',
        leaveTaskWindowFinishBtn:'#UT#Alles fertig - Aufgabe abschließen',
        leaveTaskWindowCancelBtn:'#UT#Aufgabe später weiterbearbeiten'
    },
    initConfig: function(instanceConfig) {
        var me = this,
            infoPanel = Ext.create('Editor.view.ApplicationInfoPanel');

        // Setup a flag indicating whether current user is assigned to the task
        var showUserSpecificProgress = false;
        Editor.data.task.get('users').forEach(user => {
            if (user.userGuid === Editor.data.app.user.userGuid) {
                if (user.segmentrange) {
                    return showUserSpecificProgress = true;
                }
            }
        });

        var config = {
                title: Ext.String.htmlEncode(me.title),
                padding:'8 8 8 8', // the default padding spreads up the height of the header
                defaults: {
                    margin:'0 0 0 4'
                },
                items: [{
                    xtype: 'button',
                    itemId: 'toggleTaskDesc',
                    enableToggle: true,
                    pressed: true,
                    bind: {
                        hidden: '{!taskDescription}'
                    },
                    showText: me.strings.showDesc,
                    hideText: me.strings.hideDesc,
                    text: me.strings.hideDesc
                },{
                    xtype: 'helpButton',
                    bind: {
                        tooltip: '{l10n.helpButton.tooltip}'
                    }
                },{
                    xtype: 'button',
                    itemId:'toolbarInfoButton',
                    icon: Editor.data.moduleFolder+'images/information-white.png',
                    tooltip: infoPanel.renderEditorText()
                },{
                    xtype: 'container',
                    layout: 'vbox',
                    items: [{
                        xtype: 'progressbar',
                        itemId: 'segmentFinishCount',
                        width: 150,
                        height: showUserSpecificProgress ? 12 : 24,
                        userCls: showUserSpecificProgress ? 'half-height' : '',
                        bind: {
                            tooltip: '{l10n.tasksGrid.progress.taskProgress}: {taskProgressPercent}',
                            value: '{taskProgress}'
                        }
                    },{
                        xtype: 'progressbar',
                        itemId: 'segmentFinishCountForUser',
                        width:150,
                        height: 12,
                        userCls: showUserSpecificProgress ? 'half-height' : '',
                        hidden: !showUserSpecificProgress,
                        bind: {
                            tooltip: '{l10n.tasksGrid.progress.userProgress}: {userProgressPercent}',
                            value: '{userProgress}',
                        }
                    }]
                },{
                    xtype: 'button',
                    itemId: 'leaveTaskHeaderBtn',
                    icon: Editor.data.moduleFolder+'images/table_back.png',
                    text: me.strings.leaveBtn,
                    hidden: Editor.data.editor.toolbar.hideLeaveTaskButton
                },{
                    xtype: 'button',
                    itemId:'closeHeaderBtn',
                    icon: Editor.data.moduleFolder+'images/door_out.png',
                    text: me.strings.closeBtn,
                    hidden: Editor.data.editor.toolbar.hideCloseButton
                }]
            };
        
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([ config ]);
    }
});