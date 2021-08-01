
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
    
    //card type, used for card display order
    importType:'postimport',
    
    strings:{
        wizardTitle:'#UT#Globalese Einstellungen',
        nextButtonText:'#UT#Importieren',
        groupComboLabel:'#UT#Globalese Benutzergruppe',
        engineComboLabel:'#UT#Globalese engine',
        emptyComboText:'#UT#Bitte wählen'
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
                    typeAhead: true,
                    minChars:1,
                    queryMode:'local',
                    listeners:{
                        change:'onGlobaleseGroupChange'
                    }
                },{
                    xtype:'combo',
                    itemId:'globaleseEngine',
                    disabled:true,
                    fieldLabel:me.strings.engineComboLabel,
                    allowBlank: false,
                    displayField:'name',
                    valueField:'id',
                    emptyText:me.strings.emptyComboText,
                    submitEmptyText:false,
                    typeAhead: true,
                    minChars:1,
                    queryMode:'local',
                }]
        };
        
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
        var me=this,
            win=me.up('window'),
            btnContinue=win.down('#continue-wizard-btn');
    
        if(win.isTaskUploadNext()){
            btnContinue.setIconCls('ico-finish-wizard');
            btnContinue.setText(me.strings.nextButtonText);
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