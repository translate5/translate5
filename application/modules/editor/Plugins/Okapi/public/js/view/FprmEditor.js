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
    height: innerHeight - 100,
    minHeight: 400,
    minWidth: 800,
    layout: 'fit',
    constrainHeader: true,
    loadMask: true,
    title: {text: 'Editing fprm '},
    config: {
        bconfFilter: null,
        fprm: undefined,
    },
    // Shortcuts:
    formPanel: null,
    form: null,

    tools: [{
        iconCls: 'x-fa fa-undo',
        tooltip: '#UT#Refresh',
        handler: function(){
            this.up('window').load();
        }
    }],

    items: [{
        xtype: 'form',
        itemId: 'fprm',
        height: '100%',
        scrollable: true,
        layout: 'form',
        items: [], // Fill in init method
    }],

    fbar: [{
        xtype: 'button',
        text: '#UT#Save',
        itemId: 'save',
        disabled: true,
        formBind: true,
        iconCls: 'x-fa fa-check',
        handler: function(){
            this.up('window').save()
        }
    }, {
        xtype: 'button',
        text: '#UT#Cancel',
        itemId: 'cancel',
        iconCls: 'x-fa fa-times-circle',
        handler: function(){
            this.up('window').close()
        }
    }],

    initConfig: function(config){
        this.items[0].items = this.formItems
        return this.callParent(arguments);
    },

    load: function(){
        const me = this
        me.setLoading()
        this.bconfFilter.loadFprm().then(function(fprm){
            me.setLoading(false)
            me.setFprm(fprm)
        });
    },
    initComponent: function(){
        this.title.text += ` of BconfFilter <i>${this.bconfFilter.get('name')}</i>, type ${this.bconfFilter.get('okapiType')}`;

        this.callParent();
        this.formPanel = this.down('form#fprm');
        this.form = this.formPanel.getForm();

        this.load();
    },

    afterRender: function(){
        if(this.fprm === undefined){
            this.setLoading() // has no effect in initComponent
        }
        return this.callParent(arguments);
    },
    /**
     * @method
     * @param {object} keyValues
     * Prepares the formPanel for setValues, adding formfields based on loaded fprm content
     */
    setupForm: Ext.emptyFn,

    /**
     * Called after fprm has been set
     * @see Ext.Class.config
     * @param fprm
     */
    updateFprm(fprm){
        if(fprm !== undefined){
            const parsed = this.parseFprm(fprm)
            this.setupForm(parsed)
            //this.form.setValues(parsed) // Done by setupForm
            // TODO BCONF only enable save btn when different from last disabled state
            this.down('button#save').enable()
        }
    },
    /**
     * @method
     * @abstract
     * Parses the raw fprm into an object that can be loaded into the form
     * @param {string} fprm The content of the .fprm file
     * @return object Contains keys and values, is fed to this.form.setValues()
     */
    parseFprm(fprm){
        throw new Error('must be implemented by subclass!');
    },
    /**
     * @abstract
     * Parses the form into a textstring that can be saved
     * @return string The content that will be sent to server and saved in the fprm file
     */
    compileFprm(){
        this.setLoading(false)
        throw new Error('must be implemented by subclass!');
    },

    save: function(){
        var me = this,
            currentValues = me.form.getValues();
        if(/* !Ext.Object.equals(currentValues, me.lastInvalidValues) && */ this.form.isValid()){
            me.setLoading()
            me.bconfFilter.saveFprm(me.compileFprm()).then(function(){
                me.setLoading(false)
                me.close()
            })
        } else {
            this.invalidFields = this.formPanel.query('field{getActiveErrors().length}')
            this.lastInvalidValues = currentValues
            var response = {
                status: 422,
                statusText: 'Unprocessable Entity',
                responseText: JSON.stringify({
                    errorMessage: 'Invalid form data',
                    errors: Object.assign({}, this.invalidFields.map(f => f.getActiveErrors()))
                })
            }
            Editor.app.getController('ServerException').handleException(response);
            Ext.Msg.toFront() // QUIRK: Sometimes not on top

        }
    }

})