
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
 * @class Editor.plugins.GlobalesePreTranslation.view.GlobaleseAuthViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.plugins.GlobalesePreTranslation.view.GlobaleseSettingsViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.globaleseSettingsPanel',
    
    handleNextCardClick:function(){
        var me=this,
            view=me.getView();
        
        if(!me.isFieldsValid){
            return;
        }
        
        view.fireEvent('wizardCardFinished');
    },
    
    handleSkipCardClick:function(){
        var me=this,
            view=me.getView();
        view.fireEvent('wizardCardFinished',1);
    },
    
    isFieldsValid:function(winLayout,actuelItem){
        var fieldsAreValid=true;
        
        console.log("validate the combo boxes, if thay are valid finish the card")
        return fieldsAreValid;
    }
});
















