
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
        this.addFilesToStore(btn.fileInputEl.dom.files, 'workfile');
    },
    onManualAddPivot: function(btn) {
        this.addFilesToStore(btn.fileInputEl.dom.files, 'pivot');
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
        Ext.Array.forEach(Ext.Array.from(this.getView().getSelection()), function(file){
            file.drop();
        });
    },
    addFilesToStore: function(files, type) {
        var me = this,
            store = me.getView().store;

        Ext.Array.forEach(Ext.Array.from(files), function(file) {
            var extension = file.name ? file.name.split('.').pop() : '',
                isSupportedFile = Ext.Array.contains(Editor.data.import.validExtensions,extension),
                source,
                target,
                readFile,
                reader = new FileReader(),
                rec = Ext.create('Editor.model.admin.projectWizard.File',{
                    file: file,
                    name: file.name,
                    size: file.size,
                    type: !isSupportedFile ? 'error' : type,
                    error: !isSupportedFile ? 'Type not supported.' : null
                });

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

    validateLanguages:function (rec){
        var me = this,
            sl = rec.get('sourceLang'),
            tl = rec.get('targetLang'),
            view = me.getView(),
            window = view.up('#adminTaskAddWindow'),
            sourceField = window.down('languagecombo[name="sourceLang"]'),
            targetField = window.down('tagfield[name="targetLang[]"]');


        if(Ext.isEmpty(sl) && Ext.isEmpty(tl)){
            // no language was detected but the uploaded extension is supported -> do not mark this record as error
            return;
        }

        if(Ext.isEmpty(sl)){
            rec.set('error','Source not valid');
            rec.set('type','error');
            return false;
        }
        if(Ext.isEmpty(tl)){
            rec.set('error','Target not valid');
            rec.set('type','error');
            return false;
        }
        if(!sourceField.readOnly && Ext.isEmpty(sourceField.getValue())){
            // convert the rfc value of the record to id
            sourceField.setValue(sl);
            sourceField.setReadOnly(true);
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
                return 'workfiles';
            case 'pivotFilesFilesButton':
                return 'pivot';
            default:
                return 'workfiles';
        }
    },

    handleDropZoneCss: function (add){
        var me = this,
            fn = add ? 'addCls' : 'removeCls',
            view = me.getView(),
            dropZones = view.query('wizardFileButton');
        view.getView()[fn]('dropZone');
        dropZones.forEach(function (cmp){
            cmp[fn]('dropZone');
        });
    }
});
