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
    id:'importWizardUploadGridViewController',

    errorMessages:{
        extension:'#UT#Die Datei ({0}) mit der Erweiterung ({1}) wird nicht unterstützt.',
        fileMix:'#UT#Wählen Sie entweder eine ZIP-Datei oder mehrere andere Dateien. Ein Mix aus ZIP-Dateien und anderen Dateien ist nicht möglich!',
        pivotNotBilingual:'#UT#Die Pivot-Datei ist nicht zweisprachig.',
        sourceNotValid:'#UT#Ungültige Ausgangssprache ({0}).',
        targetNotValid:'#UT#Ungültige Zielsprache ({0}).',
        sourceNotSame:'#UT#Die Ausgangssprache der Datei ({0}) stimmt nicht mit der aktuell ausgewählten Ausgangssprache überein.',
        pivotSourceNotSame:'#UT#Die Quellsprache in der Pivot-Datei ({0}) ist nicht dieselbe wie die aktuelle Quellsprache des Projekts ({1}).',
        additionalRelaisNotSameTarget:'#UT#Die Pivot-Datei ({0}) hat eine andere Zielsprache ({1}) als erwartet ({2}).'
    },

    onManualAdd: function(btn) {
        this.addFilesToStore(btn.fileInputEl.dom.files, Editor.model.admin.projectWizard.File.TYPE_WORKFILES);
    },

    onManualAddPivot: function(btn) {
        this.addFilesToStore(btn.fileInputEl.dom.files, Editor.model.admin.projectWizard.File.TYPE_PIVOT);
    },

    onDrop: function(e) {
        e.stopEvent();
        var me = this,
            adminTaskAddWindow = me.getView().up('#adminTaskAddWindow');

        // remove the drop zone css
        adminTaskAddWindow && adminTaskAddWindow.getController().handleDropZoneCss(false);

        me.addFilesToStore(e.browserEvent.dataTransfer.files, me.getTypByTarget(e.getTarget()));
    },

    /***
     * Remove selected files from the upload grid
     */
    removeFiles: function() {
        var me = this,
            grid = me.getView(),
            store = grid.getStore(),
            items,
            container = grid.up('#taskSecondCardContainer'),
            sourceField = container.down('#sourceLangaugeTaskUploadWizard'),
            targetField = container.down('#targetLangaugeTaskUploadWizard'),
            relaisLang = container.down('#relaisLangaugeTaskUploadWizard');

        Ext.Array.forEach(Ext.Array.from(grid.getSelection()), function(file){
            file.drop();
        });
        items = store.queryBy(function (rec){
            return rec.get('bilingual');
        });
        if(items.length < 1){
            sourceField.setReadOnly(false);
        }
        // clean the selected languages when there are no files in the store
        if(store.getCount() === 0){
            sourceField.setValue(null);
            targetField.setValue(null);
        }

        // update isZipUpload view model after the files are removed
        items = store.queryBy(function (rec){
            return Editor.util.Util.getFileExtension(rec.get('name')) === 'zip';
        });
        me.setIsZipUploadViewModel(items.length > 0);

        items = store.queryBy(function (rec){
            return rec.get('type') === Editor.model.admin.projectWizard.File.TYPE_PIVOT;
        });
        // clean the relais lang field when there are no relais files anymore
        if(items.length < 1){
            relaisLang.setValue(null);
        }

        me.fireEvent('workfilesRemoved',store.getData());
    },

    addFilesToStore: function(items, type) {
        var me = this,
            msg = me.errorMessages,
            files = me.zipFileFilter(items),
            store = me.getView().getViewModel().getStore('files');


        // INFO: workaround when files are dropped in the Add project button in project overview.
        if(store.isEmptyStore){
            store.loadRawData([]);
        }

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
                var task = new Ext.util.DelayedTask(function(){
                    Ext.Msg.alert(me.getView().strings.errorColumnText, Ext.String.format(msg.extension, file.name, rec.getExtension()));
                });
                task.delay(100); // needs to be delayed because it is not shown when the error pops-up when drag and drop on "Add project" button
                return true;
            }

            if(!me.isAllowed(store,rec)){
                Ext.Msg.alert(me.getView().strings.errorColumnText, msg.fileMix);
                return false;
            }

            //FIXME read file only if xlf or sdlxliff, or other xlf based formats at the end.
            //from https://stackoverflow.com/questions/14446447/how-to-read-a-local-text-file
            // seems to be the only way not getting CORS problems
            reader.readAsText(file);

            reader.onload = function (reader) {
                readFile = reader.target.result;
                source = readFile.match(/<file[^>]+source-language=["']([^"']+)["']/i);
                source = source ? source[1] : null;
                if(source) {
                    // convert the rfc to id
                    rec.set('sourceLang', Ext.StoreMgr.get('admin.Languages').getIdByRfc(source));
                }
                target = readFile.match(/<file[^>]+target-language=["']([^"']+)["']/i);
                target = target ? target[1] : null;
                if(target) {
                    rec.set('targetLang', Ext.StoreMgr.get('admin.Languages').getIdByRfc(target));
                }

                // validate project langauges fields (source,target and pivot)
                me.validateLanguages(rec, source, target);

                // check if the current filename exist in the uploaded files.
                me.handleDuplicateFileName(rec);

                // set project name and customer (if not set) from the current uploaded file name
                me.autofillProjectFields(rec);

                // commit the changes before the record is added to the store
                rec.commit();

                store.addSorted(rec);

                me.fireEvent('workfileAdded',rec);
            };
        });
    },

    /***
     * Validates and sets the language fields in the current file record
     *
     * @param rec
     * @param fileSourceLang
     * @param fileTargetLang
     */
    validateLanguages:function (rec, fileSourceLang, fileTargetLang){
        var me = this,
            msg = me.errorMessages,
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
            errorMsg = msg.pivotNotBilingual;
        }else if(Ext.isEmpty(sl)){
            errorMsg = Ext.String.format(msg.sourceNotValid,fileSourceLang);
        }else if(Ext.isEmpty(tl)){
            errorMsg = Ext.String.format(msg.targetNotValid,fileTargetLang);
        }else if(!Ext.isEmpty(sourceField.getValue()) && sourceField.getValue() !== sl){

                errorMsg = isPivotType ?
                    // if there is already source lang set, and the pivot file source lang is different, this is not allowed
                    // All uploaded pivot files must have same source-language
                    Ext.String.format(msg.pivotSourceNotSame,fileSourceLang,languages.getRfcById(sourceField.getValue()))
                    :
                    // bilingual upload where the source language of the bilingual file is not the same as the one selected/set before
                    Ext.String.format(msg.sourceNotSame,fileSourceLang);
        }else if(isPivotType && !Ext.isEmpty(relaisLang.getValue()) && tl !== relaisLang.getValue()){
            // the relais language is set and the relais file target language is different
            errorMsg = Ext.String.format(msg.additionalRelaisNotSameTarget, rec.get('name'),fileTargetLang,languages.getRfcById(relaisLang.getValue()));
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

    /***
     * Validate if the file name is duplicate for the selected filetype and target language.
     * If it is duplicate, index number will be appended to te name.
     * @param rec
     */
    handleDuplicateFileName:function (rec){
        rec.set('name',this.checkFileName(rec.get('name'),rec.get('type'),rec.get('targetLang')));
    },

    /***
     * Auto set the project name, project languages and customer based on the import file name.
     * @param rec
     */
    autofillProjectFields: function (rec) {
        var container = Ext.ComponentQuery.query('#taskMainCardContainer')[0],
            languageContainer = Ext.ComponentQuery.query('#taskSecondCardContainer')[0],
            customer = null,
            taskName = container.down('textfield[name=taskName]'),
            srcLang = languageContainer.down('combo[name=sourceLang]'),
            targetLang = languageContainer.down('tagfield[name^=targetLang]'),
            langs = rec.get('name').match(/-([a-zA-Z_]{2,5})-([a-zA-Z_]{2,5})\.[^.]+$/);

        if(rec.get('type') === 'error'){
            return;
        }

        if(Ext.isEmpty(taskName.getValue())){
            taskName.setValue(Editor.util.Util.getFileNameNoExtension(rec.get('name')));
        }

        // auto set the customer for dev version only
        if(Editor.app.isDevelopmentVersion()){
            customer = container.down('#customerId');
            if(Ext.isEmpty(customer.getValue())){
                customer.setValue(Ext.getStore('userCustomers').getDefaultCustomerId());
            }
        }

        if(!langs || langs.length !== 3){
            return;
        }

        //simple algorithmus to get the language from the filename
        //try to convert deDE language to de-DE for searching in the store
        var regex = /^([a-z]+)_?([A-Z]+)$/;
        if (regex.test(langs[1])) {
            langs[1] = langs[1].match(/^([a-z]+)_?([A-Z]+)$/).splice(1).join('-');
        }
        if (regex.test(langs[2])) {
            langs[2] = langs[2].match(/^([a-z]+)_?([A-Z]+)$/).splice(1).join('-');
        }

        var srcStore = srcLang.getStore(),
            targetStore = targetLang.getStore(),
            srcIdx = srcStore.find('label', '(' + langs[1] + ')', 0, true, true),
            targetIdx = targetStore.find('label', '(' + langs[2] + ')', 0, true, true);

        if (srcIdx >= 0) {
            srcLang.setValue(srcStore.getAt(srcIdx).get('id'));
        }
        if (targetIdx >= 0) {
            targetLang.setValue(targetStore.getAt(targetIdx).get('id'));
        }
    },

    /***
     * Check if the given name is duplicate for the new task. Is duplicated when the record
     * has same name, type and target language
     * TODO: move me to utils and refactor duplicate
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

        this.setIsZipUploadViewModel(filtered.length > 0);

        // if no zip files where found, return all files
        if(filtered.length === 0){
            return files;
        }
        return filtered.slice(0,1);
    },

    /***
     * Check if a file is allowed to be uploaded.
     * If in the uploaded files there is already zip file, no more uploads are allowed
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
    },

    /***
     * Set the isZipUpload view model flat with the given value
     * @param isZip
     */
    setIsZipUploadViewModel:function (isZip){
        var me = this,
            window = me.getView().up('#adminTaskAddWindow');
        window && window.getViewModel().set('isZipUpload',isZip);
    }
});
