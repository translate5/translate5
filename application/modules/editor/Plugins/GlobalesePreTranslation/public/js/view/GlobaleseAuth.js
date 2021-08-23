
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
 * @class Editor.plugins.GlobalesePreTranslation.view.GlobaleseAuth
 * @extends Ext.form.Panel
 */
Ext.define('Editor.plugins.GlobalesePreTranslation.view.GlobaleseAuth', {
    extend:'Ext.panel.Panel',
    alias: 'widget.globaleseAuthPanel',
    controller: 'globaleseAuthPanel',
    requires: [
        'Editor.plugins.GlobalesePreTranslation.view.GlobaleseAuthViewController'
    ],
    mixins:['Editor.controller.admin.IWizardCard'],
    
    //card type, used for card display order
    importType:'postimport',
    
    listeners: {
        beforerender : 'onAuthPanelBeforeRender',
        activate : 'onAuthPanelActivate'
    },
    
    strings:{
        skipPreTranslation:'#UT#Globalese Vorübersetzung überspringen',
        username:'#UT#Globalese Benutzer',
        apiKey:'#UT#API Schlüssel',
        wizardTitle:'#UT#Globalese Authentifizierung'
    },
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                    defaults: {
                        padding: '20 0 0 20'
                    },
                    items: [{
                        xtype: 'textfield',
                        fieldLabel: me.strings.username,
                        itemId:'apiUsername',
                    },{
                        xtype: 'textfield',
                        fieldLabel:me.strings.apiKey,
                        itemId:'apiPassword',
                        width:380
                    }]
            };
        
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([ config ]);
    },
    //called when next button is clicked
    triggerNextCard:function(activeItem){
        this.getController().handleNextCardClick();
    },
    //called when skip button is clicked
    triggerSkipCard:function(activeItem){
        this.getController().handleSkipCardClick();
    },

    disableSkipButton:function(){
        return false;
    },
    
    disableContinueButton:function(){
        return false;
    },
    
    disableAddButton:function(){
        return true;
    },
    
    disableCancelButton:function(){
        return false;
    }
    
});