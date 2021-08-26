
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

/**
 * @class Editor.view.ApplicationInfoPanel
 * @extends Ext.container.Container
 */
Ext.define('Editor.view.ApplicationInfoPanel', {
	extend:'Ext.container.Container',
    alias: 'widget.applicationInfoPanel',
    cls: 'head-panel-info-panel',
    itemId: 'applicationInfoPanel',
    tpl: [
        '<div class="info-line"><span class="user-label">{userLabel}:</span> <span class="user-name">{user.firstName} {user.surName}</span></div>',
        '<div class="info-line"><span class="login-label">{loginLabel}:</span> <span class="user-login">{user.login}</span></div>',
        '<tpl if="task">',
        '<div class="info-line"><span class="task-label">{taskLabel}:</span> <span class="task-name">{task.taskName}</span>',
        '</tpl>',
        '<tpl if="isReadonly">',
        '<span class="task-readonly">{readonlyLabel}</span>',
        '</tpl>',
        '<tpl if="task">',
        '</div>',
        '</tpl>',
        '<tpl if="task && showTaskGuid">',
        '<div class="info-line"><span class="task-label">TaskGuid:</span> <span class="task-name">{task.taskGuid}</span></div>',
        '</tpl>',
        '<tpl if="version">',
        '<div class="info-line"><span class="task-label">Version:</span> <span class="task-name">{version}</span></div>',
        '</tpl>',
        '<tpl if="browser">',
        '<div class="info-line"><span class="task-label">Browser:</span> <span class="task-name">{browser}</span></div>',
        '</tpl>'
    ],
    strings: {
        task: '#UT#Aufgabe',
        loggedinAs: '#UT# Eingeloggter Benutzer',
        loginName: '#UT# Loginname',
        readonly: '#UT# - [LESEMODUS]',
    },
    /***
     * Get the default tpl data
     */
    getEditorTplData:function(){
    	var me=this;
    	return {
            user: Editor.app.authenticatedUser.data,
            task: Editor.data.task.data,
            showTaskGuid: Editor.data.debug && Editor.data.debug.showTaskGuid,
            version: Editor.data.debug && Editor.data.app.version + ' (ext '+Ext.getVersion().version+')',
            browser: Editor.data.debug && Ext.browser.identity,
            taskLabel: me.strings.task,
            userLabel: me.strings.loggedinAs,
            loginLabel: me.strings.loginName,
            readonlyLabel: me.strings.readonly,
            isReadonly: Editor.data.task.isReadOnly()
        };
    },
    /**
     * renders the application info as text (for activated editor)
     */
    renderEditorText: function() {
        return (new Ext.XTemplate(this.tpl)).applyTemplate(this.getEditorTplData());
    },
    
    /***
     * Get admin tpl data
     */
    getAdminTplData:function(){
    	var me=this;
    	return {
	        user: Editor.app.authenticatedUser.data,
	        task: null,
	        showTaskGuid: false,
	        version: Editor.data.debug && Editor.data.app.version + ' (ext '+Ext.getVersion().version+')',
	        browser: Editor.data.debug && Ext.browser.identity,
	        taskLabel: me.strings.task,
	        userLabel: me.strings.loggedinAs,
	        loginLabel: me.strings.loginName
    	};
    }
  });