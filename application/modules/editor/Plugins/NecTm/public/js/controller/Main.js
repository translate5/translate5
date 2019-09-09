
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.plugins.NecTm.controller.Main
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.NecTm.controller.Main', {
    extend: 'Ext.app.Controller',
    refs:[{
        ref: 'TmWindow',
        selector: '#addTmWindow'
    }],
    listen: {
        component: {
            '#addTmWindow combo[name="resourceId"]': {
                select: 'handleResourceChanged'
            },
            '#editTmWindow':{
                afterrender: 'onEditTmWindowAfterrender',
            }
        }
    },
    /**
     * When a new NEC-TM-LanguageResource is created, we show the 
     * categories-field (no need to add it - it is already in there).
     */
    handleResourceChanged: function(combo, record, index) {
        var form = combo.up('form'),
            disableCategories = (record.get('serviceName') !== 'NEC-TM'),
            categoriesField = form.queryById('categories');
        categoriesField.setDisabled(disableCategories);
        categoriesField.setReadOnly(disableCategories);
    },
    /**
     * After the Edit-Window for a LanguageResource has been opened,
     * we show the categories-field (no need to add it - it is already in there).
     */
    onEditTmWindowAfterrender: function(editTmWindow) {
        var resourceId = editTmWindow.down('#resourceId').getValue(),
            disableCategories = (resourceId.indexOf('editor_Plugins_NecTm') === -1),
            categoriesField = editTmWindow.down('#categories');
        categoriesField.setDisabled(disableCategories); // TODO: or even use setVisibility?
    }
});