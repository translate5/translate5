
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
 * @class SearchTabViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.view.searchandreplace.SearchTabViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.searchtabviewcontroller',
    
    /***
     * On search field text change.
     * if the text is change reset the search helper variables
     */
    onSearchFieldTextChange:function(searchField,newValue,oldValue,eOpts){
        if(newValue===oldValue){
            return;
        }
        this.resetSearchParameters();
    },

    /***
     * search type change handler
     */
    onSearchTypeChange:function(field,newValue,oldValue,eOpts){
        var me=this,
            tabPanel=me.getView().up('#searchreplacetabpanel'),
            activeTab=tabPanel.getActiveTab(),
            searchField=activeTab.down('#searchField');

        this.resetSearchParameters();
        
        var task = new Ext.util.DelayedTask(function(){
            //reset the search value
            searchField.validate();
        }).delay(0);
    },

    /***
     * When form field value is changed, reset the search helper variables.
     * This function will be called from multiple form fields
     */
    resetSearchParameters:function(){
        var me=this,
            tabPanel=me.getView().up('#searchreplacetabpanel'),
            activeTab=tabPanel.getActiveTab(),
            vm=activeTab.getViewModel(),
            tabPanelviewModel=tabPanel.getViewModel(),
            searchReplaceController=Editor.app.getController('SearchReplace');
        
        //reset the viewmodel variables
        tabPanelviewModel.set('searchResultsFound',false);
        tabPanelviewModel.set('hasMqm',false);
        vm.set('result',[]);
        vm.set('resultsCount',0);
        vm.set('showResultsLabel',false);
        
        //reset the search indexes
        searchReplaceController.activeSegment.matchIndex=0;
        searchReplaceController.activeSegment.nextSegmentIndex=0;
        searchReplaceController.activeSegment.currentSegmentIndex=0;
        searchReplaceController.activeSegment.matchCount=0;
        
        searchReplaceController.searchRequired=true;
    }
});