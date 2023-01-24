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
 
/**
 * Custom Row Editor for the bconf-filter grid
 * This class is mainly needed to come around ExtJS Quirks
 */
Ext.define('Editor.plugins.Okapi.view.BconfFilterRowEditing', {
    extend: 'Ext.grid.plugin.RowEditing',
    alias: 'plugin.bconffilterrowediting',
    clicksToEdit: 3, // QUIRK: 1 not possible, triggers on actioncolumns TODO: limit to non actionCols, add pointerCls
    pluginId: 'rowEditing',
    removeUnmodified: true,
    errorSummary: false,
    strings: {
        infosMissing: '#UT#Informationen fehlen',
        nameMustBeChanged: '#UT#Der ursprüngliche Name muss verändert werden',
        nameMustBeSupplied: '#UT#Es muss ein endeutiger Name angegeben werden',
        nameMustNotBeLikeT5: '#UT#Der Name darf nicht den Begriff "translate5" enthalten',
        extensionMustBeSupplied: '#UT#Es muss mindestens ein Dateityp angegeben werden'
    },
    onEnterKey: function(){}, // deactivates the save-on-enter feature with interferes with the tagfields add-item-on-enter
    listeners: {
        beforeedit: function(rowEditing, cellContext){
            var record = cellContext.record,
                editor = rowEditing.getEditor(),
                form = editor.getForm(),
                tagField = editor.down('tagfield');
            // set the tagfields extensions store
            tagField.setStore(Ext.getStore('bconffilterStore').getAllExtensions());
            // adds the change listener needed to update our button position after tagfield changes
            tagField.addListener('change', rowEditing.delayedHeightChange, rowEditing);
            // needed for further processing in the controller
            record.extensionsBeforeEdit = record.get('extensions');
            // respects the initial height of the tagfield
            rowEditing.delayedHeightChange();
            // we disable name, mimeType & description editing for non-custom rows
            if(record.get('isCustom')){
                form.findField('name').enable();
                form.findField('mimeType').enable();
                form.findField('description').enable();
            } else {
                form.findField('name').disable();
                form.findField('mimeType').disable();
                form.findField('description').disable();
            }
        },
        validateedit: function(rowEditing, cellContext){
            // this case is superflous as the name-field has it's own validation
            if(cellContext.record.isClonedRecord && cellContext.newValues.name === cellContext.originalValues.name){
                Ext.MessageBox.alert(this.strings.infosMissing, this.strings.nameMustBeChanged);
                return false;
            }
            // a freshly cloned item needs a extension
            if(cellContext.record.isClonedRecord && (!cellContext.newValues.extensions || cellContext.newValues.extensions.length < 1)){
                Ext.MessageBox.alert(this.strings.infosMissing, this.strings.extensionMustBeSupplied);
                return false;
            }
            // case currently is superflous due to name-field's own validation
            if(!cellContext.newValues.name || cellContext.newValues.name.length < 1){
                Ext.MessageBox.alert(this.strings.infosMissing, this.strings.nameMustBeSupplied);
                return false;
            }
            // case currently is superflous due to name-field's own validation
            if(cellContext.newValues.name.indexOf('translate5') > -1){
                Ext.MessageBox.alert(this.strings.infosMissing, this.strings.nameMustNotBeLikeT5);
                return false;
            }
            return true;
        },
        edit: function(rowEditing, cellContext){
            var tagfield = rowEditing.getEditor().down('tagfield'),
                record = cellContext.record;
            if(tagfield){
                // when the value changed, the height changes as well
                tagfield.removeListener('change', rowEditing.delayedHeightChange);
            }
            // process the edited record
            rowEditing.saveRecord(record, cellContext.newValues, cellContext.originalValues);
            // unset the serach & clean a clone
            if(record.isClonedRecord){
                delete record.isClonedRecord;
                // invalidate the potential tmp search value set when cloning records
                cellContext.grid.unsetSearchValue(true);
            }
        },
        canceledit: function(rowEditing, cellContext){
            var record = cellContext.record;
            // remove temp data, drop new records when canceled
            delete record.extensionsBeforeEdit;
            if(record.isClonedRecord){
                delete record.isClonedRecord;
                record.drop();
                // invalidate the potential tmp search value set when cloning records
                cellContext.grid.unsetSearchValue(true);
            }
        }
    },
    delayedHeightChange: function(){
        // UGLY
        // the row-editor is not prepared to update it's height & button positions according to it's child component heights
        // There is no usable event (especially of the tagfield itself has no event that fires when it's size changed; "resize" simply does not fire) but "beforeedit" of the RowEditor
        // Problem is, that the tagfield is rendered after "beforeedit" and therefore we need to use this ugly and potentially unreliable delay
        Ext.defer(this.updateButtonsPosition, 25, this);
    },
    /**
     * Re-positions the RowEditor Buttons to match the height of the height of the editor, which may was changed by the tagfield expanding it
     */
    updateButtonsPosition: function(){
        var buttons = this.getEditor().getFloatingButtons();
        buttons.setButtonPosition(buttons.position);
    },
    /**
     * Save a changed record
     * @param {Editor.plugins.Okapi.model.BconfFilterModel} record
     * @param {object} newValues
     * @param {object} originalValues
     */
    saveRecord: async function(record, newValues, originalValues){
        var store = Ext.getStore('bconffilterStore'),
            isCustom = record.get('isCustom'),
            extensions = record.get('extensions'),
            identifier = record.get('identifier'),
            // checks, if the extensions have been changed
            extensionsChanged = !Editor.util.Util.arraysAreEqual(extensions, record.extensionsBeforeEdit),
            // checks if the record has been changed
            recordChanged = Editor.util.Util.objectWasChanged(newValues, originalValues, ['name','description','mimeType']);
        // cleanup tmp data
        delete record.extensionsBeforeEdit;
        // save a custom record or just transfere the new extensions for a non-custom record
        if(isCustom && (recordChanged || extensionsChanged)){
            // transfere changed data of a custom entry
            record.set({
                'name': newValues.name,
                'description': newValues.description,
                'mimeType': newValues.mimeType,
                'extensions': extensions
            });
            // "heal" & clean new/cloned records
            if(record.isClonedRecord){
                record.crudState = 'C';
                record.phantom = true;
            }
            record.save({
                failure: function(unsavedRecord) {
                    store.remove([unsavedRecord]);
                },
                success: function(savedRecord) {
                    record.commit(true);
                    var newFilterId = (identifier === 'NEW@FILTER') ? savedRecord.get('id') : null; // this is needed to hint at a newly added filter record in the extension update!
                    identifier = savedRecord.get('identifier'); // crucial: identifier was changed from the backend!
                    // update the maps in the store & remove extension from other items
                    store.updateExtensionsByIdentifier(identifier, extensions, true, newFilterId);
                }
            });
        } else if(!isCustom && extensionsChanged){
            Ext.Ajax.request({
                url: Editor.data.restpath + 'plugins_okapi_bconfdefaultfilter/setextensions',
                params: {
                    identifier: identifier,
                    bconfId: store.getProxy().bconfId,
                    extensions: extensions.join(',')
                },
                success: function(){
                    // update the record silently
                    record.set('extensions', extensions, { silent: true, dirty: false });
                    record.commit();
                    // update the maps in the store & remove extension from other items
                    store.updateExtensionsByIdentifier(identifier, extensions, false, null);
                },
                failure: function(response){
                    Editor.app.getController('ServerException').handleException(response);
                }
            });
        } else {
            // to remove the "red corner" when the extension-editor changed anything
            record.commit();
        }
    }
});