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
 * @property {Editor.plugins.Okapi.model.BconfFilterModel} bconfFilter
 * @property {string} fprm The raw content of the .fprm file
 */
Ext.define('Editor.plugins.Okapi.view.FprmEditor', {
    extend: 'Ext.Window',
    alias: 'widget.fprmeditor',
    modal: true,
    maximizable: true,
    margin: 50,
    width: '-50',
    height: window.innerHeight - 100,
    minHeight: 400,
    minWidth: 800,
    layout: 'fit',
    constrainHeader: true,
    iconCls: 'x-fa fa-edit',
    loadMask: true,
    title: {
        text: "FPRM Editor"
    },
    config: {
        bconfFilter: null,
        fprm: undefined
    },
    // Shortcuts:
    formPanel: null,
    form: null,
    strings: {
        title: "#UT#Editiere Filter Typ {0} von Bconf {1}",
        save: "#UT#Speichern",
        cancel: "#UT#Abbrechen",
        changesInvalid: "#UT#Ihre Änderungen sind nicht valide, daher konnte der Filter nicht gespeichert werden",
        successfullySaved: "#UT#Filter wurde erfolgreich gespeichert"
    },

    tools: [{
        iconCls: 'x-fa fa-undo',
        tooltip: '#UT#Refresh',
        handler: function(e, el, owner){
            var editor = owner.up('window');
            editor.setFprm(); // unset old value
            editor.load();
        }
    }],

    items: [{
        xtype: 'form',
        itemId: 'fprm',
        height: '100%',
        scrollable: true,
        layout: 'form',
        defaults: { labelClsExtra: Ext.baseCSSPrefix + 'selectable' },
        items: [], // Fill in init method
    }],

    fbar: [{
        xtype: 'button',
        text: 'Save',
        itemId: 'save',
        disabled: true,
        formBind: true,
        iconCls: 'x-fa fa-check',
        handler: function(){
            this.up('window').save();
        }
    }, {
        xtype: 'button',
        text: 'Cancel',
        itemId: 'cancel',
        iconCls: 'x-fa fa-times-circle',
        handler: function(){
            this.up('window').close();
        }
    }],

    initConfig: function(){
        this.items[0].items = this.formItems;
        this.fbar[0].text = this.strings.save;
        this.fbar[1].text = this.strings.cancel;
        return this.callParent(arguments);
    },

    load: function(){
        var me = this;
        me.setLoading();
        this.loadFprmData().then(function(fprm){
            me.setLoading(false);
            me.setFprm(fprm);
        });
    },
    initComponent: function(){
        var titletext = this.strings.title.replace('{0}', '<i>“'+this.bconfFilter.get('okapiType')+'”</i>');
        this.title.text = titletext.replace('{1}', '<i>“'+this.bconfFilter.get('name')+'”</i>');
        this.callParent();
        this.formPanel = this.down('form#fprm');
        this.form = this.formPanel.getForm();
        this.load();
    },

    afterRender: function(){
        if(this.fprm === undefined){
            this.setLoading(); // has no effect in initComponent
        }
        return this.callParent(arguments);
    },

    getValues: function(){
        return this.form.getValues();
    },

    /**
     * Called after fprm has been set
     * @see Ext.Class.config
     * @param fprm
     */
    updateFprm: function(fprm){
        if(fprm !== undefined){
            const parsed = this.parseFprm(fprm);
            this.form.setValues(parsed);
            //this.form.setValues(parsed) // Done by setupForm
            // TODO BCONF only enable save btn when different from last disabled state
            this.down('button#save').enable();
        }
    },
    /**
     * @method
     * @abstract
     * Parses the raw fprm into an object that can be loaded into the form
     * @param {string} fprm The content of the .fprm file
     * @return {object} Contains keys and values, is fed to this.form.setValues()
     */
    parseFprm: function(fprm){
        throw new Error('must be implemented by subclass!');
    },
    /**
     * @abstract
     * Parses the form into a textstring that can be saved
     * @return string The content that will be sent to server and saved in the fprm file
     */
    compileFprm: function(){
        this.setLoading(false);
        throw new Error('must be implemented by subclass!');
    },

    save: function(){
        var me = this,
            currentValues = me.form.getValues();
        if(/* !Ext.Object.equals(currentValues, me.lastInvalidValues) && */ this.form.isValid()){
            me.setLoading();
            me.saveFprmData(me.compileFprm()).then(function resolve(){
                me.setLoading(false);
                me.close();
            });
        } else {
            this.invalidFields = this.formPanel.query('field{getActiveErrors().length}');
            this.lastInvalidValues = currentValues;
            var response = {
                status: 422,
                statusText: 'Unprocessable Entity',
                responseText: JSON.stringify({
                    errorMessage: 'Invalid form data',
                    errors: Object.assign({}, this.invalidFields.map(f => f.getActiveErrors()))
                })
            };
            Editor.app.getController('ServerException').handleException(response);
        }
    },

    /**
     * Loads the content of the .fprm file
     * @return {Promise<string>} Also fulfilled with undefined on unsuccessful requests
     */
    loadFprmData(){
        var me = this;
        return new Promise(function(resolve, reject){
            Ext.Ajax.request({
                url: me.bconfFilter.getProxy().getUrl() + '/getfprm',
                params: {
                    id: me.bconfFilter.id
                },
                callback: function(options, success, response){
                    if(success){
                        resolve(response.responseText);
                    } else {
                        resolve();
                        Editor.app.getController('ServerException').handleException(response);
                    }
                }
            });
        });
    },

    /**
     * Saves the edited fprm back to the server
     * @param {string} rawData
     * @returns {*}
     */
    saveFprmData(rawData){
        var me = this;
        return Ext.Ajax.request({
            url: me.bconfFilter.getProxy().getUrl() + '/savefprm',
            headers: {'Content-Type': 'application/octet-stream'},
            params: { id: me.bconfFilter.id },
            rawData: rawData,
            failure: function(response){
                me.setLoading(false);
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    }

});