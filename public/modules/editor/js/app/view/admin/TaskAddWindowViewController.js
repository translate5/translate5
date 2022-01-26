
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
 * @class Editor.view.admin.TaskAddWindowViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.view.admin.TaskAddWindowViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.adminTaskAddWindow',

    /***
     * Target langauge before-deselect event handler
     * @param component
     * @param record
     * @param index
     */
    onBeforeTargetLangDeselect:function (component, record){
        var me = this,
            view = me.getView(),
            grid = view && view.down('wizardUploadGrid'),
            store = grid && grid.getStore(),
            toRemove = [];

        if(!view.isVisible()){
            return;
        }

        store.each(function (rec){
            if(rec.get('targetLang') === record.get('id')){
                toRemove.push(rec);
            }
        });
        if(toRemove.length > 0){
            store.remove(toRemove);
            Editor.MessageBox.addWarning(me.getView().strings.autoRemovedUploadFilesWarningMessage);
        }
    }
});