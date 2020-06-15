
/*
START LICENSE AND COPYRIGHT

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a plug-in for translate5. 
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and 
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the 
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
   
 There is a plugin exception available for use with this release of translate5 for 
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3: 
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * @class Editor.plugins.PangeaMt.controller.Main
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.PangeaMt.controller.Main', {
    extend: 'Ext.app.Controller',
    requires: [
        'Editor.plugins.PangeaMt.view.LanguageResources.EngineCombo',
    ],
    stores:['Editor.plugins.PangeaMt.store.LanguageResources.PangeaMtEngine'],
    listen:{
        controller:{
            '#TmOverview':{
                engineSelect:'handleEngineSelect'
            }
        },
        component: {
            '#addTmWindow': {
                afterrender: 'onTmWindowRender', 
            },
            '#addTmWindow combo[name="resourceId"]': {
                select: 'handleResourceChanged'
            }
        }
    },
    /**
     * On add new tm window render handler
     */
    onTmWindowRender:function(addTmWindow){
        var pangeaMtStore = Ext.StoreManager.get('pangeaMtEngine'),
            form,
            engineItem;
        if (pangeaMtStore) {
            // fill store
            pangeaMtStore.setData(Editor.data.LanguageResources.pangeaMtEngines);
            // add select for engines
            form = addTmWindow.down('form');
            engineItem = {
                    xtype: 'pangeamtenginecombo',
                    itemId: 'pangeaMtEngine',
                    displayField: 'name',
                    bind:{
                        hidden: true,
                        disabled: true
                    },
                    allowBlank: false,
                    listeners:{
                        change: 'onEngineComboChange'
                    }
            };
            form.insert(1, engineItem);
        }
    },
    /**
     * When a new PangeaMT-LanguageResource is created, we show the engines.
     * Otherwise we hide and reset the engines.
     */
    handleResourceChanged: function(combo, record, index) {
        var form = combo.up('form'),
            resourceId = form.down('combo[name="resourceId"]').getValue(),
            pangeaMtEnginesField = form.queryById('pangeaMtEngine'),
            disablePangeaMtEngines = (resourceId.indexOf('editor_Plugins_PangeaMt') === -1);
        pangeaMtEnginesField.setDisabled(disablePangeaMtEngines);
        pangeaMtEnginesField.setHidden(disablePangeaMtEngines);
        if (disablePangeaMtEngines) {
            pangeaMtEnginesField.reset();
        }
    },
    /**
     * Set the specificData
     */
    handleEngineSelect:function(form){
        var pangeaMtEngine = form.down('#pangeaMtEngine').getSelection(); // = null after reset()
        if (pangeaMtEngine){
            form.getForm().findField('specificData').setValue(JSON.stringify({
                engineId:pangeaMtEngine.get('engineId')
            }));
        }
    }

});