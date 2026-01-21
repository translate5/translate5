
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

    listen: {
        component: {
            '#taskMainCard combobox#customerId': {
                change: 'onCustomerChange'
            },
            'tabpanel': {
                tabchange: tabpanel => tabpanel.updateLayout()
            }
        }
    },

    selectedCustomersConfigStore: null,

    /***
     *  On customer chagne load the customer specific store with the selected customer.
     *  This will set the pivot language out of the customer specific store if exist
     */
    onCustomerChange: function (comboBox, customerId){
        var me = this,
            pivotLanguageCombo = me.getView().down('#relaisLangaugeTaskUploadWizard'),
            edit100PercentCheckBox = me.getView().down('[name=edit100PercentMatch]');

        if(! me.selectedCustomersConfigStore){
            me.selectedCustomersConfigStore = Ext.create('Editor.store.admin.CustomerConfig');
        }

        me.getView().mask();

        /**
         * https://jira.translate5.net/browse/TRANSLATE-3587
         *
         * Inside the above mask() call, tabindex-attributes of all tabbable children are set to '-1'
         * to prevent them from being tabbable while mask is shown. This, however, happens only for
         * children that are not disabled at the point of time where they are queried, and that is why
         * setting tabindex=-1 for combobox#bconfId was skipped so focus jumped straight into there
         * on Tab-key press as that combo is enabled back shortly while mask is still shown
         *
         * So, here we restore values of tabindex-attribute back to make sure fields are tabbable even
         * despite the mask itself is not yet hidden so far due to we're waiting for the customer config
         * store load callback
         */
        me.getView().el.restoreTabbableState();

        // reset the pivot langauge on each customer change
        pivotLanguageCombo.setValue(null);

        me.selectedCustomersConfigStore.loadByCustomerId(customerId,function (){
            let view = me.getView();

            // Info: do any code processing in the callback only if the view exist.
            if(!view){
                // The window is already closed/destroyed. Do not process any customer change
                return;
            }

            let edit100PercentMatch = me.selectedCustomersConfigStore.getConfig('import.edit100PercentMatch');

            if(edit100PercentCheckBox) {
                edit100PercentCheckBox.setValue(edit100PercentMatch);
            }
            
            var config = me.selectedCustomersConfigStore.getConfig('project.defaultPivotLanguage'),
                langId = config ? Ext.getStore('admin.Languages').getIdByRfc(config) : null;

            view.unmask();

            if( !langId){
                return;
            }

            pivotLanguageCombo.setValue(langId);
        });
    },

    /***
     *
     * @param win
     */
    onTaskAddWindowBeforeRender:function(win){
        //insert the taskUpload card in before render
        win.insertCard({
            xtype:'taskUpload',
            itemId:'taskUploadCard',
            groupIndex:8
        },'postimport');

        //insert the taskUpload card in before render
        win.insertCard({
            xtype:'languageResourcePivotWizard',
            itemId:'languageResourcePivotWizard',
            groupIndex:4
        },'postimport');

        win.insertCard({
            xtype:'adminTaskUserAssocWizard',
            itemId:'adminTaskUserAssocWizard',
            groupIndex:1//index 2 is language resources assoc
        },'postimport');

        if(Editor.app.authenticatedUser.isAllowed('taskConfigOverwriteGrid')) {
            win.insertCard({
                xtype: 'adminConfigWizard',
                itemId: 'adminConfigWizard',
                groupIndex: 5//index 2 is language resources assoc
            }, 'postimport');
        }
    },

    /***
     *
     * @param win
     */
    onTaskAddWindowRender: function(win){

        //sort the group by group index
        win.groupCards['preimport'].sort(function (a, b) {
            return a.groupIndex - b.groupIndex;
        });

        //add all of the cards in the window by order: preimport, import, postimport
        for(var i=0;i<win.groupCards['preimport'].length;i++){
            win.add(win.groupCards['preimport'][i]);
        }

        //sort the group by group index
        win.groupCards['import'].sort(function (a, b) {
            return a.groupIndex - b.groupIndex;
        });

        for(i=0;i<win.groupCards['import'].length;i++){
            win.add(win.groupCards['import'][i]);
        }
        //sort the group by group index
        win.groupCards['postimport'].sort(function (a, b) {
            return a.groupIndex - b.groupIndex;
        });

        for(i=0;i<win.groupCards['postimport'].length;i++){
            win.add(win.groupCards['postimport'][i]);
        }
    },

    /***
     *
     * @param win
     */
    onTaskAddWindowAfterRender: function(win){
        var winLayout=win.getLayout(),
            vm=win.getViewModel();
        vm.set('activeItem',winLayout.getActiveItem());
    },

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
    },

    /***
     * Triggered when the files are dragged over the import wizard
     * @param e
     */
    onDragEnter:function (e){
        if (!e.browserEvent.dataTransfer || Ext.Array.from(e.browserEvent.dataTransfer.types).indexOf('Files') === -1) {
            return;
        }
        e.stopEvent();
        this.handleDropZoneCss(true);
    },

    /***
     * Triggered when the items are dragged out of the import wizard
     * @param e
     */
    onDragLeave:function (e){
        var me = this,
            el = e.getTarget(),
            thisEl = me.getView().getEl();

        e.stopEvent();

        if (el === thisEl.dom) {
            me.handleDropZoneCss(false);
            return;
        }

        while (el !== thisEl.dom && el && el.parentNode) {
            el = el.parentNode;
        }

        if (el !== thisEl.dom) {
            me.handleDropZoneCss(false);
        }
    },

    /***
     * Triggered when the items are dropped on to import wizard
     * @param e
     */
    onDrop:function (e){
        this.handleDropZoneCss(false);
    },

    /***
     * Add or remove dropzone css from droppable components
     * @param add
     */
    handleDropZoneCss: function (add){
        var me = this,
            fn = add ? 'addCls' : 'removeCls',
            view = me.getView().down('wizardUploadGrid'),
            dropZones = view.query('wizardFileButton');
        view.getView()[fn]('dropZone');
        dropZones.forEach(function (cmp){
            cmp[fn]('dropZone');
        });
    }
});