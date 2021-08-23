
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
 * @class Editor.plugins.NecTm.controller.Main
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.NecTm.controller.Main', {
    extend: 'Ext.app.Controller',
    requires: [
        'Editor.util.LanguageResources',
        'Editor.plugins.NecTm.view.LanguageResources.services.NecTm'
    ],
    refs:[{
        ref: 'TmWindow',
        selector: '#addTmWindow'
    }],
    listen: {
        store: {
            '#Tags':{
                load: 'onCategoriesStoreLoad'
            }
        },
        component: {
            '#addTmWindow combo[name="resourceId"]': {
                select: 'handleResourceChanged'
            },
            '#editTmWindow':{
                afterrender: 'onEditTmWindowAfterrender',
            }
        }
    },
    init: function() {
        Editor.util.LanguageResources.addService(Ext.create('Editor.plugins.NecTm.view.LanguageResources.services.NecTm'));
    },
    /**
     * Categories (="tags") store load handler: 
     * Don't show top-level-categories.
     */
    onCategoriesStoreLoad:function(store){
        var topLevelCategories = Editor.data.plugins.NecTm.topLevelCategories,
            topLevelFilter = new Ext.util.Filter({
                filterFn: function(item) {
                    return !Ext.Array.contains(topLevelCategories, item.get('originalCategoryId'));
                }
            });
        store.filter(topLevelFilter);
    },
    /**
     * When a new NEC-TM-LanguageResource is created, we show the 
     * categories-field (no need to add it - it is already in there).
     */
    handleResourceChanged: function(combo, record, index) {
        var form = combo.up('form'),
            resourceId = form.down('combo[name="resourceId"]').getValue(),
            categoriesField = form.queryById('categories'),
            disableCategories = (resourceId.indexOf('editor_Plugins_NecTm') === -1),
            topLevelCategories;
        categoriesField.setDisabled(disableCategories);
        categoriesField.setReadOnly(disableCategories);
        if (!disableCategories) {
            // For NEC-TMs, at least one category must be assigned. So, if no top-level-categories
            // are set, this field is mandatory.
            topLevelCategories = Editor.data.plugins.NecTm.topLevelCategories;
            categoriesField.setConfig('allowBlank',topLevelCategories.length > 0);
        }
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