/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

Ext.define('Editor.view.admin.task.CustomField.PanelController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.adminTaskCustomFieldPanel',
    listen: {
        component: {
            'form': {
                boxready: 'formBoxReady'
            }
        }
    },

    /**
     * Apply handlers to fire when json-fields are clicked,
     * as there is neither triggers supported by ExtJS 6.2 nor click-event directly supported for fields
     *
     * @param form
     */
    formBoxReady: function(form){
        var label = form.down('#label'),
            tooltip = form.down('#tooltip'),
            picklistData = form.down('#picklistData');

        // Apply handlers
        label.inputEl.on('click', el => this.jsonFieldClick(label));
        tooltip.inputEl.on('click', el => this.jsonFieldClick(tooltip));
        picklistData.inputEl.on('click', el => this.jsonFieldClick(picklistData));
    },

    /**
     * make sure SimpleMap-popup is shown when field is clicked so that json-value can be edited via the popup
     *
     * @param field
     */
    jsonFieldClick: function(field) {
        Ext.ClassManager.get('Editor.view.admin.config.type.SimpleMap').getJsonFieldEditor(field);
    },

    /**
     * Save changes to new or existing custom field
     */
    onSave:function(){
        var view = this.getView();
        view.mask(Ext.LoadMask.prototype.msg);
        view.getViewModel().get('customField').save({
            callback: () => view.unmask()
        });
    },

    /**
     * Cancel changes pending for to new or existing custom field
     */
    onCancel:function(){
        this.getViewModel().get('customField').reject();
    },

    /**
     * Delete custom field
     */
    onDelete:function(){
        var view = this.getView(), record = view.getViewModel().get('customField');
        record.erase();
        if (!record.phantom) {
            view.down('#taskCustomFieldGrid').getStore().reload();
        }
    }
});