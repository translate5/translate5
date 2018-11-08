
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
 * @class Editor.plugins.pluginFeasibilityTest.view.EditorPanel
 * @extends Ext.panel.Panel
 */
Ext.define('Editor.view.LanguageResources.services.Default', {
    id: 'Default',
    /**
     * returns the row css class for the associated service in the tm overview panel
     * @param {Editor.model.LanguageResources.LanguageResource} rec
     * @return {Array}
     */
    getTmOverviewRowCls: function(record) {
        var result = [];
        if(record.get('filebased')) {
            result.push('language-ressource-import');
            result.push('language-ressource-export');
        }
        result.push('languageResource-status-'+record.get('status'));
        return result;
    }
/*    
 Currently default for the following types: 
    'editor_Services_OpenTM2',
    'editor_Services_Moses',
    'editor_Services_LucyLT',
    'editor_Services_SDLLanguageCloud',
    'editor_Services_Google',
    'editor_Plugins_GroupShare',
    */
});