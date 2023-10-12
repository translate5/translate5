
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
 * @class Editor.view.searchandreplace.TabPanelViewModel
 * @extends Ext.app.ViewModel
 */
Ext.define('Editor.view.searchandreplace.TabPanelViewModel', {
    extend: 'Ext.app.ViewModel',
    alias: 'viewmodel.tabpanelviewmodel',
    
    data:{
        searchView:true,
        searchResultsFound:false,
        disableSearchButton:true,
        hasMqm:false,
        isOpenedByMoreThanOneUser: false
    },
    
    formulas:{
    	isReplaceAllTooltip: function(get) {
            if (get('hasMqm')) {
            	return this.getView().strings.mqmNotSupporterTooltip;
            }
            if (!get('searchResultsFound')) {
            	return this.getView().getViewModel().get('l10n.segmentGrid.searchReplace.replaceAll.disabled.reason1');
            }
            if (get('isOpenedByMoreThanOneUser')) {
            	return this.getView().getViewModel().get('l10n.segmentGrid.searchReplace.replaceAll.disabled.reason2');
            }
            return null;
        },
        isSearchView: function(get) {
            return get('searchView');
        },
        isSearchResultsFound: function(get) {
            return get('searchResultsFound');
        },
        isDisableReplaceAllButton: function(get) {
            return !get('searchResultsFound') || get('hasMqm') || get('isOpenedByMoreThanOneUser');
        },
        isDisableSearchButton: function(get) {
            return get('disableSearchButton');
        }
    }
});