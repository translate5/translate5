
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
        this.addFilesToStore(e.browserEvent.dataTransfer.files, 'workfile');
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
            var rec, source, target;

            rec = store.createModel({
                file: file,
                name: file.name,
                size: file.size,
                type: type,
                error: null
            });
            store.addSorted(rec);

            //FIXME read file only if xlf or sdlxliff, or other xlf based formats at the end.
            //from https://stackoverflow.com/questions/14446447/how-to-read-a-local-text-file
            // seems to be the only way not getting CORS problems
            var reader = new FileReader();
            reader.readAsText(file);
            reader.onload = function (reader) {
                var file = reader.target.result;
                source = file.match(/<file[^>]+source-language=["']([^"']+)["']/i);
                if(source) {
                    rec.set('sourceLang', source[1]);
                }
                target = file.match(/<file[^>]+target-language=["']([^"']+)["']/i);
                if(target) {
                    rec.set('targetLang', target[1]);
                }
                me.validateLanguages(rec);
                rec.commit();
            };
        });
    },

    validateLanguages:function (rec){
        var me = this,
            view = me.getView(),
            window = view.up('#adminTaskAddWindow'),
            sourceField = window.down('languagecombo[name="sourceLang"]'),
            targetField = window.down('tagfield[name="targetLang[]"]');


        if(rec.get('sourceLang') === null){
            rec.set('error',rec.get('error')+['Source not valid']);
            return false;
        }
        if(rec.get('sourceLang') === null){
            rec.set('error',rec.get('error')+['Target not valid']);
            return false;
        }
        if(!sourceField.readOnly && Ext.isEmpty(sourceField.getValue())){
            // convert the rfc value of the record to id
            sourceField.setValue(rec.get('sourceLang'));
            sourceField.setReadOnly(true);
        }

        targetField.addValue(rec.get('targetLang'));
    },

    testNewUpload: function() {
        console.log(this.getView().getStore());
        var store = this.getView().getStore(),
            formData = new FormData();

        store.each(function(record) {
            // Add file to AJAX request
            //FIXME append only files where the type is a processable one(workfile/pivotfile) at the moment.
            formData.append('testField[]', record.get('file'), record.get('name'));
        });

        // Set up the request (encapsulate in ExtJS request?)
        var xhr = new XMLHttpRequest();

        // FIXME get URL from task model!
        xhr.open('POST', '/editor/task/', true);

        // Set up a handler for when the task for the request is complete
        xhr.onload = function () {
            if (xhr.status == 200) {
                alert("Upload done");
            } else {
                alert("Upload error");
            }
        };

        // Send the data.
        xhr.send(formData);
    }
});
