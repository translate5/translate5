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
 * @property {string} fprm
 */
Ext.define('Editor.plugins.Okapi.view.FprmEditor', {
    extend: 'Ext.Window',
    alias: 'widget.fprmeditor',
    modal:true,
    maximizable:true,
    margin: 50,
    width: '-50',
    height: innerHeight-100,
    minHeight: 400,
    layout: 'fit',
    constrainHeader: true,
    loadMask: true,
    title: {text: 'Editing fprm ' },
    config: {
        bconfFilter: null,
        fprm: undefined,
    },
    // Shortcuts:
    formPanel: null,
    form: null,

    items: [{
        xtype:'form',
        itemId: 'fprm',
        height: '100%',
        items: [], // Fill in init method
    }],

    fbar: [{
        xtype: 'button',
        text: '#UT#Save',
        itemId: 'save',
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

    initComponent: function(){
        this.title.text += ` of <i>${this.bconfFilter.get('name')}</i>`;

        this.callParent();
        this.formPanel = this.down('form#fprm');
        this.form = this.formPanel.getForm();
        const setFprm = this.setFprm.bind(this)

        this.bconfFilter.loadFprm().then(setFprm);
    },

    afterRender: function(){

        if(this.fprm === undefined){
            this.setLoading() // has no effect in initComponent
        }
        return this.callParent(arguments);
    },

    applyFprm: function(fprm){
        if(fprm){
            this.form.setValues(this.parseFprm(fprm))
        }
        this.setLoading(false);
    },
    /**
     * @abstract
     * Parses the raw fprm into an object that can be loaded into the form
     */
    parseFprm(){
        this.setLoading(false)
        throw new Error('must be implemented by subclass!');
    },
    /**
     * @abstract
     * Parses theform into an textstring that can be saved
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
            })
        } else {
            this.invalidFields = this.formPanel.query("field{getActiveErrors().length}")
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