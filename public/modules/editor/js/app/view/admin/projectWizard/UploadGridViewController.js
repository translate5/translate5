
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
 * @class Editor.view.admin.projectWizard.UploadGridViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.view.admin.projectWizard.UploadGridViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.wizardUploadGrid',

    onManualAdd: function(btn) {
        this.addFilesToStore(btn.fileInputEl.dom.files, Editor.model.admin.projectWizard.File.TYPE_WORKFILES);
    },
    onManualAddPivot: function(btn) {
        this.addFilesToStore(btn.fileInputEl.dom.files, Editor.model.admin.projectWizard.File.TYPE_PIVOT);
    },
    onDrop: function(e) {
        e.stopEvent();
        var me = this;
        me.handleDropZoneCss(false);
        me.addFilesToStore(e.browserEvent.dataTransfer.files, me.getTypByTarget(e.getTarget()));
    },
    onDragEnter:function (e){
        this.handleDropZoneCss(true);
    },
    removeFiles: function() {
        var me = this,
            grid = me.getView(),
            store = grid.getStore(),
            items,
            view = me.getView(),
            container = view.up('#taskSecondCardContainer'),
            sourceField = container.down('#sourceLangaugeTaskUploadWizard'),
            targetField = container.down('#targetLangaugeTaskUploadWizard');

        Ext.Array.forEach(Ext.Array.from(grid.getSelection()), function(file){
            file.drop();
        });
        items = store.queryBy(function (rec){
            return rec.get('bilingual');
        });
        if(items.length < 1){
            sourceField.setReadOnly(false);
        }
        if(store.getCount() === 0){
            sourceField.setValue(null);
            targetField.setValue(null);
        }
    },
    addFilesToStore: function(items, type) {
        var me = this,
            files = me.zipFileFilter(items),
            store = me.getView().store;

        Ext.Array.forEach(files, function(file) {
            var source,
                target,
                readFile,
                reader = new FileReader(),
                rec = Ext.create('Editor.model.admin.projectWizard.File',{
                    file: file,
                    name: file.name,
                    size: file.size,
                    type: type,
                    error: null
                });

            if(!Ext.Array.contains(Editor.data.import.validExtensions,rec.getExtension())){
                Editor.MessageBox.getInstance().showDirectError(Ext.String.format('Files {0} with extension {1} are not supported', file.name, rec.getExtension()));
                return true;
            }

            if(!me.isAllowed(store,rec)){
                Editor.MessageBox.getInstance().showDirectError('Either select one ZIP file, or multiple other files. A mix of ZIP files and other files is not possible!');
                return false;
            }

            //FIXME read file only if xlf or sdlxliff, or other xlf based formats at the end.
            //from https://stackoverflow.com/questions/14446447/how-to-read-a-local-text-file
            // seems to be the only way not getting CORS problems
            reader.readAsText(file);

            reader.onload = function (reader) {
                readFile = reader.target.result;
                source = readFile.match(/<file[^>]+source-language=["']([^"']+)["']/i);
                if(source) {
                    rec.set('sourceLang', source[1]);
                }
                target = readFile.match(/<file[^>]+target-language=["']([^"']+)["']/i);
                if(target) {
                    rec.set('targetLang', target[1]);
                }

                me.validateLanguages(rec);

                me.validateFileName(rec);

                // commit the changes before the record is added to the store
                rec.commit();

                store.addSorted(rec);
            };
        });
    },

    /***
     * Validates and sets the language fields in the current file record
     * @param rec
     */
    validateLanguages:function (rec){
        var me = this,
            languages = Ext.StoreMgr.get('admin.Languages'),
            isPivotType = rec.get('type') === Editor.model.admin.projectWizard.File.TYPE_PIVOT,
            sl = rec.get('sourceLang'),
            tl = rec.get('targetLang'),
            view = me.getView(),
            container = view.up('#taskSecondCardContainer'),
            sourceField = container.down('#sourceLangaugeTaskUploadWizard'),
            targetField = container.down('#targetLangaugeTaskUploadWizard'),
            relaisLang = container.down('#relaisLangaugeTaskUploadWizard'),
            errorMsg = null,

            isBilingual = !(Ext.isEmpty(sl) && Ext.isEmpty(tl));// no langauge detection in the file -> bilingual. The extension validation is done before.


        rec.set('bilingual',isBilingual);

        // if the file is not bilingual(source and target are not detected) and
        // the dropped file is not a pivot file, this is file for okapi
        if(!isBilingual && !isPivotType){
            // no language was detected but the uploaded extension is supported -> do not mark this record as error
            return;
        }

        if(!isBilingual && isPivotType){
            // for pivot language only bilingual files can be used
            errorMsg = 'Pivot file is not bilingual.';
        }else if(Ext.isEmpty(sl)){
            errorMsg = 'Source not valid';
        }else if(Ext.isEmpty(tl)){
            errorMsg = 'Target not valid';
        }else if(!Ext.isEmpty(sourceField.getValue()) && sourceField.getValue() !== sl){
            // bilingual upload where the source language of the bilingual file is not the same as the one selected/set before
            errorMsg = 'The source language is not the same as the selected one.';
        }else if(isPivotType && sl !== sourceField.getValue()){
            // if there is already source lang set, and the pivot file source lang is different, this is not allowed
            // All uploaded pivot files must have same source-language
            errorMsg = Ext.String.format('The source language in the pivot file {0} is not the same as the current source language of the project {1}.',languages.getRfcById(sl),languages.getRfcById(sourceField.getValue()));
        }else if(isPivotType && !Ext.isEmpty(relaisLang.getValue()) && tl !== relaisLang.getValue()){
            // the relais language is set and the relais file target language is different
            errorMsg = Ext.String.format('The file {0} has different target language {1} as the expected {2}.', rec.get('name'),languages.getRfcById(tl),languages.getRfcById(relaisLang.getValue()));
        }

        if(errorMsg!== null){
            rec.set('error',errorMsg);
            rec.set('type','error');
            return;
        }

        if(!sourceField.readOnly && Ext.isEmpty(sourceField.getValue())){
            // convert the rfc value of the record to id
            sourceField.setValue(sl);
            sourceField.setReadOnly(true);
        }

        if(isPivotType){
            relaisLang.setValue(tl);
            return;
        }
        targetField.addValue(tl);

    },

    validateFileName:function (rec){
        rec.set('name',this.checkFileName(rec.get('name'),rec.get('type'),rec.get('targetLang')));
    },

     /***
     * Check if the given name is duplicate for the new task. Is duplicated when the record
     * has same name, type and target language
     * @param fileName
     * @param type
     * @param targetLang
     * @param index
     * @returns {*|string}
     */
    checkFileName:function (fileName,type,target,index = 0){
        var me = this,
            store = me.getView().store,
            checkName = fileName,
            ext = '',
            tokens,
            nameExists = false;
        if(index){
            if(checkName.indexOf('.') > -1){
                tokens = checkName.split('.'); ext = '.' + tokens.pop();
                checkName = tokens.join('.');
            }
            checkName = `${checkName}(${index})${ext}`;
        }

        // check for matching name in the store
        store.each(function(record) {
            if(record.get('name') === checkName && record.get('type') === type && (record.get('targetLang') === target || Ext.isEmpty(target))){
                nameExists = true;
            }
        });
        return nameExists ? me.checkFileName(fileName, type, target,index + 1) : checkName;
    },

    /***
     * Get the file type by given dropzone element.
     * @param element
     * @returns {string}
     */
    getTypByTarget:function (element){
        var cmp = Ext.get(element.id) ? Ext.get(element.id).component : null,
            name = cmp ? cmp.name : '';

        switch (name){
            case 'workFilesFilesButton':
                return Editor.model.admin.projectWizard.File.TYPE_WORKFILES;
            case 'pivotFilesFilesButton':
                return Editor.model.admin.projectWizard.File.TYPE_PIVOT;
            default:
                return Editor.model.admin.projectWizard.File.TYPE_WORKFILES;
        }
    },

    /***
     * Add or remove dropzone css from droppable components
     * @param add
     */
    handleDropZoneCss: function (add){
        var me = this,
            fn = add ? 'addCls' : 'removeCls',
            view = me.getView(),
            dropZones = view.query('wizardFileButton');
        view.getView()[fn]('dropZone');
        dropZones.forEach(function (cmp){
            cmp[fn]('dropZone');
        });
    },

    /***
     * Check if in the dropped files there are zip files. If there are zip files,
     * use the first matched zip file for the import. All other files(including zip files) will be ignored
     * @param items
     * @returns {*}
     */
    zipFileFilter:function (items) {
        var files = Ext.Array.from(items),
            filtered = [];
        Ext.Array.forEach(Ext.Array.from(files), function (file) {
            if(Editor.util.Util.getFileExtension(file.name) === 'zip'){
                filtered.push(file);
            }
        });
        // if no zip files where found, return all files
        if(filtered.length === 0){
            return files;
        }
        return filtered.slice(0,1);
    },

    /***
     * Check if a file is allowed to be uploaded.
     * If in the uploaded files there is already zip file, no more uploads are alowed
     * @param store
     * @param record
     * @returns {boolean}
     */
    isAllowed:function (store, record) {
        var hasZip = false,
            isZip = record.getExtension() === 'zip';

        store.each(function (r){
            if(r.getExtension() === 'zip'){
                hasZip = true;
                return false;
            }
        });

        // if there is already zip uploaded file or if the upload is zip and
        // there are other uploads, no other files are allowed
        if(hasZip || (isZip && store.getCount() > 0)){
            return false;
        }
        return true;
    }
});
