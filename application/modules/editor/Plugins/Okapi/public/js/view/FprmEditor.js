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
    extend: 'Ext.window.Window',
    alias: 'widget.fprmeditor',
    id: 'bconfFprmEditor',
    modal: true,
    maximizable: true,
    margin: 50,
    width: 800,
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
    fprmType: null,
    rawData: null,
    transformedData: null,
    guiData: null,
    config: {
        bconfFilter: null
    },
    // Shortcuts:
    formPanel: null,
    formItems: [], // to be defined in extending classes
    form: null,
    helpPage: null,
    strings: {
        title: "#UT#Editiere Filter Typ {0} von Bconf {1}",
        save: "#UT#Speichern",
        cancel: "#UT#Abbrechen",
        invalidTitle: "#UT#Bearbeitung fehlerhaft",
        changesInvalid: "#UT#Ihre Änderungen sind nicht valide, daher konnte der Filter nicht gespeichert werden",
        successfullySaved: "#UT#Der Filter wurde erfolgreich gespeichert"
    },
    translations: {},
    tools: [{
        iconCls: 'x-fa fa-undo',
        tooltip: '#UT#Refresh',
        handler: function(e, el, owner){
            var editor = owner.up('window');
            editor.setFprm(''); // unset old value
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
    initComponent: function(){
        var titletext = this.strings.title.replace('{0}', '<i>“'+this.bconfFilter.get('okapiType')+'”</i>');
        this.title.text = titletext.replace('{1}', '<i>“'+this.bconfFilter.get('name')+'”</i>');
        this.callParent();
        this.formPanel = this.down('form#fprm');
        this.form = this.formPanel.getForm();
        this.load();
    },
    afterRender: function(){
        if(this.rawData === null){
            this.setLoading(); // has no effect if done in initComponent
        }
        return this.callParent(arguments);
    },

    load: function(){
        this.setLoading();
        this.loadFprmData();
    },
    /**
     * Serts the fprm-data after load
     * @param raw
     * @param transformed
     */
    setFprmData: function(raw, transformed){
        this.rawData = raw;
        this.transformedData = transformed;
    },
    /**
     * Can be overwritten to init the layout after the data has been loaded
     * @param {int} height
     */
    fprmDataLoaded: function(height){
        this.form.setValues(this.getFormInitValues());
        this.down('button#save').enable();
    },
    /**
     * Must be overwritten in subclasses to receive the raw content to send to the server
     * @returns {string}
     */
    getRawResult: function(){
        switch(this.fprmType){
            case "xml":
                return this.getFormValues().xml;

            case "yaml":
                return this.getFormValues().yaml;
        }
        throw new Error('getRawResult must be implemented in subclasses!');
    },
    /**
     * Retrieves all values of our form
     * @returns {object}
     */
    getFormValues: function(){
        return this.form.getValues();
    },
    /**
     * Retrieves the values to initialy fill our form. can be overridden in subclasses
     * @returns {object}
     */
    getFormInitValues: function(){
        switch(this.fprmType){

            case "properties":
                return this.transformedData; // special: we have a JSON Object with parsed data in case of properties-based fprms

            case "xml":
                return { xml: this.rawData };

            case "yaml":
                return { yaml: this.rawData };
        }
        throw new Error('getFormInitValues must be implemented in subclasses!');
    },
    /**
     * Save button handler
     */
    save: function(){
        var me = this,
            currentValues = me.form.getValues();
        if(/* !Ext.Object.equals(currentValues, me.lastInvalidValues) && */ this.form.isValid()){
            me.setLoading();
            me.saveFprmData();
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
     */
    loadFprmData(){
        var me = this;
        Ext.Ajax.request({
            url: me.bconfFilter.getProxy().getUrl() + '/getfprm',
            method: 'GET',
            params: {
                id: me.bconfFilter.id
            },
            success: function(response){
                var data = Ext.util.JSON.decode(response.responseText),
                    height = window.innerHeight - 100;
                me.setLoading(false);
                me.translations = data.translations;
                me.fprmType = data.type;
                me.guiData = data.guidata;
                me.rawData = data.raw;
                me.transformedData = data.transformed;
                me.setHeight(height);
                me.fprmDataLoaded(height);
            },
            failure: function(response){
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    },

    /**
     * Saves the edited fprm back to the server
     * @returns {*}
     */
    saveFprmData(){
        var me = this;
        Ext.Ajax.request({
            url: me.bconfFilter.getProxy().getUrl() + '/savefprm',
            headers: {'Content-Type': 'application/octet-stream'},
            params: {
                id: me.bconfFilter.id,
                type: me.fprmType
            },
            rawData: me.getRawResult(),
            success: function(response){
                var result = Ext.util.JSON.decode(response.responseText);
                me.setLoading(false);
                if(result.success){
                    me.close();
                    Editor.MessageBox.addSuccess(me.strings.successfullySaved);
                } else {
                    Ext.Msg.show({
                        title: me.strings.invalidTitle,
                        message: me.strings.changesInvalid+'<br/><i>('+result.error+')</i>',
                        icon: Ext.Msg.ERROR
                    });
                }
            },
            failure: function(response){
                me.setLoading(false);
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    }
});