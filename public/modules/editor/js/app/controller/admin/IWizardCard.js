
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

/**
 * Interface for the Wizard cards
 * 
 * @class Editor.controller.admin.IWizardCard
 */
Ext.define('Editor.controller.admin.IWizardCard', {

	/***
	 * Index where the card will apear in the group
	 */
	groupIndex:-1,
	
    /***
     * There are 3 options for import type:
     * preimport, import, postimport
     */
    importType:"",
    
    /***
     * called when next button of the card is clicked
     */
    triggerNextCard:function(activeItem){
        
    },
    
    /***
     * called when skip button of the card is clicked
     */
    triggerSkipCard:function(activeItem){
        
    },

    /***
     * called when import defaults button is clicked
     */
    triggerImportDefaults:function(activeItem){

    },

    /***
     * if return true, disable the skip button
     */
    disableSkipButton:function(){
        
    },
    
    /***
     * if return true, disable the continue button
     */
    disableContinueButton:function(){
        
    },
    
    /***
     * if return true, disable the add button
     */
    disableAddButton:function(){
        
    },
    
    /***
     * if return true, disable the cancel button
     */
    disableCancelButton:function(){
        
    },

    /***
     * if true, disable the import defaults button
     */
    disableImportDefaults:function(){
        return true;
    }

});