
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

Ext.define('Editor.view.LanguageResources.services.Microsoft.SynonymSearchViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.synonymSearch',

    listen: {
        component: {
            "#searchButton": {
                click: 'onSearchButtonClick'
            }
        }
    },

    /***
     * On text change, run the translation search with the text content
     * @param field
     * @param newValue
     */
    onSearchButtonClick: function (){
        var me = this,
            view = me.getView(),
            searchValue = view && view.down('#textSearch').getValue(),
            languageResourceEditorPanel = view && view.up('#languageResourceEditorPanel'),
            assocStore = languageResourceEditorPanel && languageResourceEditorPanel.assocStore,
            index = assocStore && assocStore.find('serviceName', 'Microsoft'),
            record = index > -1 ? assocStore.getAt(index) : null;

        if( !record || Ext.isEmpty(searchValue)){
            return;
        }
        view.getStore().load({
            params:{
                searchText:searchValue
            },
            url: Editor.data.restpath+'languageresourceinstance/'+record.get('languageResourceId')+'/translate',
            failure: Editor.app.getController('ServerException').handleException
        });
    }
});