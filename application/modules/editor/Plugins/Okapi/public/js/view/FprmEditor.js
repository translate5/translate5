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
    resetable: false,
    title: {
        text: "FPRM Editor"
    },
    config: {
        bconfFilter: null
    },
    strings: {
        title: '#UT#Editiere Filter Typ {0} von Bconf {1}',
        help: '#UT#OKAPI Hilfe',
        helpTooltip: '#UT#Das Okapi Framework wird auf der Serverseite für Dateikonvertierungen verwendet',
        save: '#UT#Speichern',
        cancel: '#UT#Abbrechen',
        reset: '#UT#Änderungen zurücksetzen',
        invalidTitle: '#UT#Bearbeitung fehlerhaft',
        invalidField: "#UT#Feld '{0}' vom Typ '{1}' ist nicht valide",
        float: '#UT#Gleitkommazahl',
        boolean : '#UT#Boolscher Wert',
        integer : '#UT#Ganzzahl',
        validationFailed: '#UT#Ihre Änderungen sind nicht valide',
        changesInvalid: '#UT#Ihre Änderungen sind nicht valide, daher konnte der Filter nicht gespeichert werden',
        successfullySaved: '#UT#Der Filter wurde erfolgreich gespeichert',
        yes: '#UT#Ja',
        no: '#UT#Nein'
    },
    initConfig: function(instanceConfig){
        var config = {
            minHeight: 400,
            minWidth: 600,
            layout: 'fit', /* this way it can be defined by inheritance */
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
                tooltip: this.strings.reset,
                hidden: !this.resetable,
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
                layout: (this.formPanelLayout) ? this.formPanelLayout : 'form',
                bodyPadding: (this.formPanelPadding) ? this.formPanelPadding : 10,
                border: false,
                defaults: { labelClsExtra: Ext.baseCSSPrefix + 'selectable' },
                items: []
            }],
            dockedItems: [{
                xtype: 'toolbar',
                dock: 'bottom',
                ui: 'footer',
                listeners: {
                    afterlayout: function(toolbar){
                        // the button-position gets lost every time the layout is applied (e.g. when changing between tabs in Openxml)
                        // positioning only possible programmatical ...
                        toolbar.down('button#help').setStyle('left', '0px');
                    }
                },
                items: [{
                    xtype: 'component',
                    flex: 1
                },{
                    xtype: 'button',
                    text: this.strings.help,
                    tooltip: this.strings.helpTooltip,
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
                        this.up('window').close();
                    }
                }]
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
            this.setLoading(true); // has no effect if done in initComponent
        }
        return this.callParent(arguments);
    },
    /**
     * Initializes the base-form
     */
    initForm: function(){
        this.formPanel = this.down('form#fprm');
        this.form = this.formPanel.getForm();
    },
    /**
     * The main entry-point to create the form
     */
    createForm: function(){
        throw new Error('createForm must be implemented in subclasses!');
    },
    /**
     * Can be used to apply final tweaks after the form was completely created
     */
    finalizeForm: function(){

    },
    /**
     * Loads the values into the created form
     */
    loadForm: function(){
        this.form.setValues(this.getFormInitValues());
    },
    /**
     * Enable save button & show help button after data is loaded
     */
    finalizeLayout: function(){
        this.down('button#save').enable();
        if(this.getHelpLink() != null){
            this.down('button#help').show();
        }
        var height = this.getHeight(),
            vpHeight = document.documentElement.clientHeight,
            top = Math.max(10, Math.floor((vpHeight - height) / 2));
        this.setY(top);
        // if window is too large, we reduce the height and force inner scrollbars
        // NOTE: we respect the minHeight as tabbed UIs otherwise have hidden tabs then
        if(vpHeight < (top + height) && height > this.minHeight){
            this.setHeight(Math.max((vpHeight - top - 10), this.minHeight));
        }
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
        me.setLoading(true);
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
                me.initForm();
                me.createForm();
                me.finalizeForm();
                me.loadForm();
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
        me.setLoading(true);
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
                    me.close();
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
     * Destructs all relevant fields to guarantee a fresh construction
     */
    doDestroy: function() {
        this.formPanel.removeAll();
        this.removeAll();
        this.callParent();
    }
});