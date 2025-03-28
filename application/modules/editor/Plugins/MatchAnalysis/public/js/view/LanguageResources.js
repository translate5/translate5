
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
 * @class Editor.plugins.MatchAnalysis.view.LanguageResources
 * @extends Ext.form.Panel
 */
Ext.define('Editor.plugins.MatchAnalysis.view.LanguageResources', {
    extend:'Ext.panel.Panel',
    alias: 'widget.languageResourcesWizardPanel',
    controller: 'languageResourcesWizardPanel',
    itemId:'languageResourcesWizardPanel',
    requires: [
        'Editor.plugins.MatchAnalysis.view.LanguageResourcesViewController',
        'Editor.view.LanguageResources.TaskAssocPanel'
    ],
    mixins:['Editor.controller.admin.IWizardCard'],
    
    //card type, used for card display order
    importType:'postimport',
    
    task:null,
    autoScroll: true,
    
    strings:{
        wizardTitle:'#UT#Sprachressourcen zuweisen'
    },
    listeners: {
        activate:function (panel) {
            // the languageResourceTaskAssocPanel component is used in 2 different contexts (import,and projectTask properties)
            // in the import wizard we dont need to hide the panel based on the task state. Tha is why we enable the panel
            // manually here.
            panel.down('#languageResourceTaskAssocPanel').getViewModel().set('enablePanel', true);
        }
    },
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                    items: [{
                        xtype: 'languageResourceTaskAssocPanel',
                        title:null
                    }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([ config ]);
    },
    
    initComponent:function(){
    	var me=this;
    	me.callParent([arguments]);
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