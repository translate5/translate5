
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
 * @class Editor.view.admin.preferences.OverviewPanelViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.view.admin.preferences.OverviewPanelViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.preferencesOverviewPanel',
    routes: {
        'preferences': 'onPreferencesRoute',
        'preferences/:tab' :'onPreferencesRoute',
        'preferences/:tab/:selectionId' :'onPreferencesRoute',
        'preferences/:tab/:selectionId/:action' :'onPreferencesRoute',
    },
    listen:{
        component: {
            '#preferencesOverviewPanel adminConfigGrid #searchField': {
                change: 'onSearchFieldChange'
            }
        }
    },
    /**
     * If we change the search field in the main config grid, we set that as route
     * @param field
     */
    onSearchFieldChange: function(field) {
        var confGrid = field.up('adminConfigGrid');
        if(confGrid && confGrid.getController().getSearchValue()) {
            //TODO UGLY: is there another generic way to do such a thing? Otherwise we would have to implement a parser which gets and changes only the desired part of the hash instead of setting the whole one(here the config value)
            this.redirectTo('preferences/adminConfigGrid|config/'+confGrid.getController().getSearchValue());
        }
    },
    onPreferencesRoute: function(tab, selectionId, action) {
        var v = this.getView();
        Editor.app.openAdministrationSection(this.getView());
        if(tab) {
            var activeTab = v.setActiveTab(v.down('#' + tab));
            if(!selectionId && activeTab.selection){ activeTab.setSelection(); } // Unselect
        }
    },
    /**
     * Sets the url hash to the current choosen preferences tab (itemID needed therefore to be configured!)
     * @param tabpanel {Ext.tab.Panel}
     * @param newCard {Ext.panel.Panel}
     */
    onTabChange: function(tabpanel, newCard){
        var newRoute = 'preferences/' + newCard.getItemId();
        if(!Ext.util.History.getToken().startsWith(newRoute)){
            this.redirectTo(newRoute);
        }
    }
});
