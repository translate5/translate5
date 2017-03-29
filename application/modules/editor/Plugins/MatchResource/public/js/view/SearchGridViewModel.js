
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3
			 http://www.gnu.org/licenses/agpl.html

END LICENSE AND COPYRIGHT
*/

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.plugins.MatchResource.view.SearchGridViewModel
 * @extends Ext.app.ViewModel
 */

Ext.define('Editor.plugins.MatchResource.view.SearchGridViewModel', {
    extend: 'Ext.app.ViewModel',
    alias: 'viewmodel.matchResourceSearchGrid',
    requires: [
        'Ext.util.Sorter',
        'Ext.data.Store',
        'Ext.data.field.Integer',
        'Ext.data.field.String'
    ],
    data: {
        title: '',
        record: false
    },
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                stores: {
                    editorsearch: {
                        pageSize: 200,
                        autoLoad: false,
                        model: 'Editor.plugins.MatchResource.model.EditorQuery',
                        sorters: [{
                            property: 'matchrate',
                            direction: 'DESC'
                        }]
                    }
                }
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});