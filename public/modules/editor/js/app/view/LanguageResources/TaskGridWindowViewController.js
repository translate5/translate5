
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.view.LanguageResources.MatchGridViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.view.LanguageResources.TaskGridWindowViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.languageResourceTaskGridWindow',
    strings: {
    },
    listen: {
        component: {
            '#import-task-tm-btn': {
                click: 'importTaskIntoTm'
            },
            '#cancel': {
                click: 'close'
            }
        }
    },
    close: function() {
        this.getView().close();
    },
    gotoTask: function(grid, rowIndex, colIndex, icon, ev, task) {
        Editor.app.openAdministrationSection('#projectPanel', 'project/'+task.get('projectId')+'/'+task.get('taskId')+'/focus');
        this.close();
    },
    /**
     */
    importTaskIntoTm: function() {
        var me = this, 
            languageResource = me.getViewModel().get('record'),
            proxy = languageResource.proxy,
            url = proxy.url,
            selected = me.getView().down('grid').getSelection();
            
        if(!selected || !selected.length) {
            return;
        }
        
        if (!url.match(proxy.slashRe)) {
            url += '/';
        }
        url += languageResource.get('id')+'/tasks';
        
        me.getView().mask('Start reimport...');
        Ext.Ajax.request({
            url: url,
            method: 'POST',
            success: function() {
                me.getView().close();
                Editor.MessageBox.addSuccess("Reimport started, please press refresh button in your task overview periodically."); //FIXME translation
            },
            params: {
                data: Ext.JSON.encode({toReImport: selected.map(function(rec){
                    return rec.get('taskGuid');
                })})
            }
        });
    }
});
