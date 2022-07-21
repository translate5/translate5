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
    constrainHeader: true,
    iconCls: 'x-fa fa-edit',
    loadMask: true,
    title: {
        text: "FPRM Editor"
    },
    config: {
        bconfFilter: null
    },
    strings: {
        title: "#UT#Editiere Filter Typ {0} von Bconf {1}",
        help: "#UT#OKAPI Hilfe",
        save: "#UT#Speichern",
        cancel: "#UT#Abbrechen",
        invalidTitle: "#UT#Bearbeitung fehlerhaft",
        invalidField: '#UT#Feld "{0}" vom Typ "{1}" ist nicht valide',
        float: "#UT#Gleitkommazahl",
        boolean : "#UT#Boolscher Wert",
        integer : "#UT#Ganzzahl",
        validationFailed: "#UT#Ihre Änderungen sind nicht valide",
        changesInvalid: "#UT#Ihre Änderungen sind nicht valide, daher konnte der Filter nicht gespeichert werden",
        successfullySaved: "#UT#Der Filter wurde erfolgreich gespeichert"
    },
    initConfig: function(instanceConfig){
        var config = {
            minHeight: 400,
            minWidth: 600,
            layout: (this.formPanelLayout) ? this.formPanelLayout : 'form', /* this way it can be defined by inheritance */
            okapiType: null, /* the okapi-type, e.g. okf_xml */
            fprmType: null, /* the fprm-type, properties|xml|yaml|plain */
            rawData: null,
            transformedData: null,
            guiData: null,
            translations: {},
            formPanel: null,
            form: null,
            tools: [{
                iconCls: 'x-fa fa-undo',
                tooltip: '#UT#Refresh',
                handler: function(e, el, owner){
                    var editor = owner.up('window');
                    editor.unload();
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
                items: []
            }],
                fbar: [{
                xtype: 'button',
                text: this.strings.help,
                itemId: 'help',
                hidden: true,
                iconCls: 'x-fa fa-book',
                handler: function(){
                    this.up('#bconfFprmEditor').openHelpLink();
                }
            },{
                xtype: 'button',
                text: this.strings.save,
                itemId: 'save',
                disabled: true,
                formBind: true,
                iconCls: 'x-fa fa-check',
                handler: function(){
                    this.up('window').save();
                }
            },{
                xtype: 'button',
                text: this.strings.cancel,
                itemId: 'cancel',
                iconCls: 'x-fa fa-times-circle',
                handler: function(){
                    this.up('window').closeWindow();
                }
            }]
        };
        return this.callParent([Ext.apply(config, instanceConfig)]);
    },
    initComponent: function(){
        this.okapiType = this.bconfFilter.get('okapiType');
        var titletext = this.strings.title.replace('{0}', '<i>“'+this.okapiType+'”</i>');
        this.title.text = titletext.replace('{1}', '<i>“'+this.bconfFilter.get('name')+'”</i>');
        this.callParent();
        this.load();
    },
    afterRender: function(){
        if(this.rawData === null){
            this.setLoading(); // has no effect if done in initComponent
        }
        return this.callParent(arguments);
    },
    /**
     * Creates the basic form that is
     */
    createBaseForm: function(){
        this.formPanel = this.down('form#fprm');
        // this.formPanel.setLayout('form'); // Ext.create('Ext.layout.container.Form')
        this.getBaseFormItems().forEach(item => this.formPanel.add(item));
        this.form = this.formPanel.getForm();
    },
    /**
     * retrieves the base form items we have
     * @returns {array}
     */
    getBaseFormItems: function(){
        return [];
    },
    /**
     * Enable save button & show help button after data is loaded
     */
    finalizeLayout: function(){
        this.down('button#save').enable();
        if(this.getHelpLink() != null){
            var helpButton = this.down('button#help');
            helpButton.show();
            // positioning only possible programmatical ...
            helpButton.setStyle('left', '0px');
        }
        var top = Math.floor((window.innerHeight - this.getHeight()) / 2);
        this.setY((top < 10) ? 10 : top);
    },
    /**
     * Can be overwritten to init the layout after the data has been loaded
     * This is the main entry-point in extending classes to create custom forms after the fprm-data is loaded
     */
    fprmDataLoaded: function(){
        this.form.setValues(this.getFormInitValues());
    },
    /**
     * Can be overwritten to add additional validations
     * If this API returns a string, this will show up as additional error-msg in the dialog
     * Only a return-value of true will be regarded as valid
     * @returns {boolean|string}
     */
    validate: function(){
        return this.form.isValid();
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
     * The helpLinks are stored in the translations.
     * In case of GUIs, which represent multiple okapi-types, there may be a helpLink for each type with a fixed naming-scheme: "helpLink-" + okapiType
     */
    getHelpLink: function(){
        if(this.translations.hasOwnProperty('helpLink-' + this.okapiType)){
            return this.translations['helpLink-' + this.okapiType];
        } else if(this.translations.hasOwnProperty('helpLink')){
            return this.translations.helpLink;
        }
        return null;
    },
    openHelpLink: function(){
        window.open(this.getHelpLink(), '_blank');
    },
    /**
     * Save button handler
     */
    save: function(){
        var valid = this.validate();
        if(valid === true){
            this.setLoading();
            this.saveFprmData();
        } else {
            // no specific error given, create details from Form
            if(valid === false || valid === "" || valid === null){
                var invalidFields = this.formPanel.query('field{getActiveErrors().length}'),
                    errors = invalidFields.map(f => f.getActiveErrors());
                valid = (errors.length > 0) ? errors.join('<br/>') : 'Unknown error'; // Unknown Error just for completeness, can not happen
            }
            this.showValidationMsg(this.strings.validationFailed + '<br/><i>('+valid+')</i>');
        }
    },
    /**
     * starts loading the resources (raw data, transformed data, translations, gui-data)
     */
    load: function(){
        this.setLoading();
        this.loadFprmData();
    },
    /**
     *
     */
    unload: function(){
        this.formPanel.removeAll();
        this.rawData = null;
        this.transformedData = null;
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
                var data = Ext.util.JSON.decode(response.responseText);
                me.setLoading(false);
                me.translations = data.translations;
                me.fprmType = data.type;
                me.guiData = data.guidata;
                me.rawData = data.raw;
                me.transformedData = data.transformed;
                me.createBaseForm();
                me.fprmDataLoaded();
                me.finalizeLayout();
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
                    Editor.MessageBox.addSuccess(me.strings.successfullySaved);
                    me.closeWindow();
                } else {
                    me.showValidationMsg(me.strings.changesInvalid+'<br/><i>('+result.error+')</i>');
                }
            },
            failure: function(response){
                me.setLoading(false);
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    },
    /**
     *
     * @param {string} msg
     */
    showValidationMsg: function(msg){
        Ext.Msg.show({
            title: this.strings.invalidTitle,
            message: msg,
            icon: Ext.Msg.ERROR,
            buttons: Ext.Msg.OK
        });
    },
    /**
     *
     */
    closeWindow: function(){
        this.formPanel.removeAll();
        this.removeAll();
        this.close();
    }
});