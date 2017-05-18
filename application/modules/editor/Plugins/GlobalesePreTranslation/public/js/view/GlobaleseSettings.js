
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.plugins.GlobalesePreTranslation.view.GlobaleseSettings
 * @extends Ext.panel.Panel
 */
Ext.define('Editor.plugins.GlobalesePreTranslation.view.GlobaleseSettings', {
    extend:'Ext.panel.Panel',
    alias: 'widget.globaleseSettingsPanel',
    controller:'globaleseSettingsPanel',
    requires: [
        'Editor.plugins.GlobalesePreTranslation.view.GlobaleseSettingsViewController'
    ],
    mixins:['Editor.controller.admin.IWizardCard'],
    strings:{
        wizardTitle:'#UT#Globalese settings',
        groupComboLabel:'#UT#Globalese group',
        engineComboLabel:'#UT#Globalese engine',
        emptyComboText:'#UT#-- Please select --'
    },
    initConfig: function(instanceConfig) {
        var me = this,
        config = {
                defaults: {
                    padding: '20 0 0 20'
                },
                items: [{
                    xtype:'combobox',
                    itemId:'globaleseGroup',
                    fieldLabel:me.strings.groupComboLabel,
                    allowBlank: false,
                    displayField:'name',
                    valueField:'id',
                    emptyText:me.strings.emptyComboText,
                    submitEmptyText:false,
                    allowBlank: false,
                    typeAhead: true,
                    minChars:1,
                    queryMode:'local',
                },{
                    xtype:'combo',
                    itemId:'globaleseEngine',
                    fieldLabel:me.strings.engineComboLabel,
                    allowBlank: false,
                    displayField:'name',
                    valueField:'id',
                    emptyText:me.strings.emptyComboText,
                    submitEmptyText:false,
                    allowBlank: false,
                    typeAhead: true,
                    minChars:1,
                    queryMode:'local',
                }]
        };
        
        me.importType='postimport';
        
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([ config ]);
    },
    
    triggerNextCard:function(activeItem){
        this.getController().handleNextCardClick();
    },
    
    triggerSkipCard:function(activeItem){
        this.getController().handleSkipCardClick();
    },
    
    disableSkipButton:function(){
        var me = this,
            win = me.up('window'),
            winLayout=win.getLayout(),
            nextStep=winLayout.getNext();
        
        if(!nextStep || nextStep.getXType()){
            return true;
        }
    },
    
    disableContinueButton:function(){
        
    },
    
    disableAddButton:function(){
        return true;
    },
    
    disableCancelButton:function(){
        
    }
});