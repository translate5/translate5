
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

/**
 * @class SearchReplace
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.SearchReplace', {
    extend : 'Ext.app.Controller',
    
    requires:['Editor.view.searchandreplace.SearchReplaceWindow'],
    listen:{
        component:{
            '#segmentgrid':{
                afterrender:'onSegmentGridAfterRender',
                filterchange:'onSegmentGridFilterChange',
                columnshow:'onColumnVisibilityChange',
                columnhide:'onColumnVisibilityChange'
            },
            '#searchreplacewindow':{
                show:'onSearchReplaceWindowShow',
                destroy:'onSearchReplaceWindowDestroy'
            },
            '#searchreplacetabpanel #searchButton':{
                click:'onSearchButtonClick'
            },
            
            '#searchreplacetabpanel #replaceButton':{
                click:'onReplaceButtonClick'
            },
            
            '#searchreplacetabpanel #replaceAllButton':{
                click:'onReplaceAllButtonClick'
            },
            
        },
        controller:{
            '#Editor': {
                beforeKeyMapUsage: 'handleEditorKeyMapUsage'
            }
        }
    },
    
    refs:[{
        ref:'segmentGrid',
        selector:'#segmentgrid'
    },{
        ref:'tabPanel',
        selector:'#searchreplacetabpanel'
    }],
    
    searchFields:[],
    replaceFields:[],
    activeColumnDataIndex:'',
    DEFAULT_COLUMN_DATA_INDEX:'targetEdit',
    
    
    currentIndex:null,
    
    strings:{
        searchInfoMessage:'#UT#The search will be performed only on the filtered segments',
        comboFieldLabel:'#UT#Replace',
    },
    
    activeSelection:{
        segmentIndex:-1,
        indexInSegment:-1,
    },
    
    initConfig:function(){
        this.callParent(arguments);
        this.resetActiveColumnDataIndex();
    },
    
    /***
     * On segment grid after render handler
     * Here we add the search and replace menu item on the needed column
     */
    onSegmentGridAfterRender:function(segmentGrid){
        var me=this,
            menu = segmentGrid.headerCt.getMenu();
        
        me.addSearchReplaceMenu(menu);
        
        menu.on({
            beforeshow:{
                fn:me.onSegmentGridMenuBeforeShow,
                scope:me
            },
            hide:{
                fn:me.onSegmentGridMenuHide,
                scope:me
            },
        });
        
        me.initColumnArrays();
    },
    
    onSegmentGridFilterChange:function(store,filters,eOpts){
        if(filters.length>0){
            Editor.MessageBox.addInfo(this.strings.searchInfoMessage);
        }
    },
    
    onColumnVisibilityChange:function(){
        this.initColumnArrays();
    },
    
    handleEditorKeyMapUsage: function(cont, area, mapOverwrite) {
        var me = this;
        cont.keyMapConfig['ctrl-f'] = ['f',{ctrl: true, alt: false},me.handleSearchReplaceHotkey, true];
        cont.keyMapConfig['ctrl-h'] = ['h',{ctrl: true, alt: false},me.handleSearchReplaceHotkey, true];
    },
    
    addSearchReplaceMenu:function(gridMenu){
        // add menu item  into the menu and store its reference
        var me=this,
            menuItem = gridMenu.add({
                itemId:'searchReplaceMenu',
                text: 'Search and replace window',
                iconCls:'x-fa fa-search',
                scope:me,
                handler:me.showSearchAndReplaceWindow
            });
    },
    
    onSegmentGridMenuBeforeShow:function(menu){
        // get data index of column for which menu will be displayed
        var me=this,
            searchReplaceMenu=menu.down('#searchReplaceMenu'),
            currentDataIndex = menu.activeHeader.dataIndex;

        me.activeColumnDataIndex = currentDataIndex;
        // show/hide menu item in the menu
        if(Ext.Array.contains(me.searchFields,currentDataIndex)) {
            searchReplaceMenu.show();
            return;
        }
        searchReplaceMenu.hide();
    },
    
    onSegmentGridMenuHide:function(){
        //reset the current active column data index
        this.resetActiveColumnDataIndex();
    },
    
    resetActiveColumnDataIndex:function(){
        var me=this;
        me.activeColumnDataIndex =me.DEFAULT_COLUMN_DATA_INDEX;
    },
    
    onSearchReplaceWindowShow:function(win){
        var tabPanel=win.down('#searchreplacetabpanel'),
            replaceTab=tabPanel.down('#replaceTab'),
            activeTab=tabPanel.getActiveTab(),
            searchCombo=activeTab.down('#searchCombo'),
            searchInCombo=activeTab.down('#searchInCombo');

        
        replaceTab.insert(1,{
            xtype:'combo',
            itemId:'replaceCombo',
            focusable:true,
            fieldLabel:this.strings.comboFieldLabel,
        });
        
        searchCombo.focus();
        this.initSearchInComboStore(searchInCombo);
    },
    
    onSearchReplaceWindowDestroy:function(){
        this.resetActiveColumnDataIndex();
    },
    
    onSearchButtonClick:function(button){
        var me=this,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            vm=activeTab.getViewModel(),
            result=vm.get('result');
        
        if(result.length>0){
            //if edited segment select in it all finded results
            //if not edited open first find, and select the matches found
            
            //save the current position 
            me.handleRowSelection();
        }
        me.search();
    },
    
    onReplaceButtonClick:function(){
    },
    
    onReplaceAllButtonClick:function(){
        
    },
    
    showSearchAndReplaceWindow:function(key){
        var me=this;
        if(key instanceof Object){
            me.handleSerchReplaceMenu();
            return;
        }
        
        //if it is not from menu, check if the window is opened from edited segment, if yes select to search to field the current edited segment column
        //if onyl ctrl+f or ctrl+h is pressed and there 
        
        var searchReplaceWindow=Ext.widget('searchreplacewindow'),
            plug = Editor.app.getController('Editor').getEditPlugin();
            //rec = plug.editing && plug.context.record,
            //grid = me.getSegmentGrid(),
            //selModel = grid.getSelectionModel();

        if(plug.editor && plug.editor.editingPlugin.editing){
            me.activeColumnDataIndex = plug.editor.columnClicked;
        }
        
        if(!key || (key == Ext.event.Event.F)){
            focusTab = 'searchTab';
        }
        if(key == Ext.event.Event.H){
            focusTab = 'replaceTab';
        }
        
        var tabPanel=searchReplaceWindow.down('#searchreplacetabpanel'),
            activeTab=tabPanel.down('#'+focusTab+'');
        tabPanel.setActiveTab(activeTab);
        tabPanel.getViewModel().set('searchView',(focusTab == 'searchTab') ? true : false);
        searchReplaceWindow.show();
    },
    
    handleSearchReplaceHotkey:function(key){
        var me=Editor.app.getController('SearchReplace'),
            segmentGrid=me.getSegmentGrid();
        if(!segmentGrid || !segmentGrid.isVisible()) {
            return;
        }
        me.showSearchAndReplaceWindow(key);
    },
    
    handleSerchReplaceMenu:function(){
        var searchReplaceWindow=Ext.widget('searchreplacewindow');
        searchInCombo=searchReplaceWindow.down('#searchInCombo');
        searchReplaceWindow.show();
    },
    
    initSearchInComboStore:function(){
        var me=this,
            segmentGrid=Ext.ComponentQuery.query('#segmentgrid')[0],
            columns = segmentGrid.query('gridcolumn[isContentColumn]:not([hidden])'),
            storeData=[],
            searchInCombos=Ext.ComponentQuery.query('#searchInCombo');
        
        Ext.Array.each(columns, function(rec) {
            storeData.push({'id':rec.dataIndex , 'value':rec.text.replace(/<(?:.|\n)*?>/gm, '')});
        });
        
        Ext.Array.each(searchInCombos,function(combo){
            combo.setStore(Ext.create('Ext.data.Store', {
                fields: ['id', 'value'],
                data:storeData
            }));
            var rec = combo.findRecord('id',me.activeColumnDataIndex);
            if(rec){
                combo.setSelection(rec);
            }
        });
    },
    
    initColumnArrays:function(){
        var me=this,
            segmentGrid=me.getSegmentGrid();
        me.searchFields=[];
        me.replaceFields=[];
        me.searchFields=me.getColumnDataIndex(segmentGrid.query('gridcolumn[isContentColumn]:not([hidden])'));
        me.replaceFields=me.getColumnDataIndex(segmentGrid.query('gridcolumn[isEditableContentColumn]:not([hidden])'));
    },
    
    getColumnDataIndex:function(columns){
        if(columns.length < 1){
            return [];
        }
        var dataArray=[];
        Ext.Array.each(columns,function(col){
            Ext.Array.push(dataArray,col.dataIndex)
        });
        return dataArray;
    },
    
    search:function(){
        var me=this,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            form=activeTab.getForm(),
            segmentGrid = me.getSegmentGrid(),
            segmentStore=segmentGrid.editingPlugin.grid.store,
            proxy = segmentStore.getProxy(),
            params = {};
        
        params[proxy.getFilterParam()] = proxy.encodeFilters(segmentStore.getFilters().items);
        params[proxy.getSortParam()] = proxy.encodeSorters(segmentStore.getSorters().items);
        
        form.submit({
            url: Editor.data.restpath+'segment/search',
            params:params,
            method:'GET',
            success: function(form, submit){
                if(!submit.result || !submit.result.rows){
                    return;
                }
                var foundSegments = submit.result.rows,
                    activeTabViewModel=activeTab.getViewModel(),
                    tabPanelviewModel=tabPanel.getViewModel();

                tabPanelviewModel.set('searchPerformed',foundSegments.length > 0);
                activeTabViewModel.set('resultsCount',foundSegments.length);
                activeTabViewModel.set('result',foundSegments.length >0 ? foundSegments : [] );
                activeTabViewModel.set('showResultsLabel',true);
            },
            failure: function(form, submit){
                var res = submit.result;
                //submit results are always state 200.
                //If success false and errors is an array, this errors are shown in the form directly,
                // so we dont need the handleException
                if(!res || res.success || !Ext.isArray(res.errors)) {
                    Editor.app.getController('ServerException').handleException(submit.response);
                    return;
                }
                if(Ext.isArray(res.errors)) {
                    form.markInvalid(res.errors);
                    return;
                }
            }
        });
    },
    
    handleRowSelection:function(){
        var me=this,
            plug = Editor.app.getController('Editor'),
            editor = plug.getEditPlugin().editor,
            grid = plug.getSegmentGrid(),
            selModel = grid.getSelectionModel(),
            ed = plug.getEditPlugin();

        if(!me.isEditing) {
            //FIXME find first in grid (the function from Thomas)
            //so the starting point is definded
        }
        me.selectText(editor);
    },
    
    selectText:function(editor){
        //check if in the current edited segment the key is found
        //if yes, select the first match in the colum
        //-- put all matches in the current segment in the buffer array, so on the next click we just change the index of the current selected
        
        //if now find the fist mathc from the results array
        var me=this;
        ////TODO with this we can ged and set the value of the current edited cell
        //editor.mainEditor.setValue("<span style='background-color:red;'>ace</span>");
        var testWindow = editor.mainEditor.iframeEl.dom.contentWindow;
        var testDocument = editor.mainEditor.iframeEl.dom.contentDocument || iFrame.getWin().document
        
        me.testSearch(testWindow,testDocument,"the");
    },
    
    //FIXME refactor this function!!!!!!!!!
    //the images should not be removed from the text, only the mark tags
    //FIXME fix the regular expression
    testSearch:function(testWindow,testDocument,text){
        var me=this,
            plug = Editor.app.getController('Editor'),
            editor = plug.getEditPlugin().editor,
            grid = plug.getSegmentGrid(),
            selModel = grid.getSelectionModel(),
            ed = plug.getEditPlugin(),
            count = 0,
            idx=0,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            searchCombo=activeTab.down('#searchCombo');

        me.searchValue = searchCombo.getRawValue();
        me.indexes = [];
        
        // detects html tag
        me.tagsRe=/<[^>]*>/gm;
        // DEL ASCII code
        me.tagsProtect='\x0f';

        if (me.searchValue !== null) {
            me.searchRegExp = new RegExp(me.searchValue, 'g' + (me.caseSensitive ? '' : 'i'));
            
            //me.store.each(function(record, idx) {
            var cell, matches, cellHTML,
                cell = Ext.get(testDocument.body);
            
                //matches = cell.dom.innerHTML.match(me.tagsRe);
                //cellHTML = cell.dom.innerHTML.replace(me.tagsRe, me.tagsProtect);
            
            
                //clear the html tags from the string
                cellHTML = cell.dom.innerHTML.replace(/<\/?[^>]+(>|$)/g, "");
                matches = cellHTML.match(me.searchRegExp);
            
                // populate indexes array, set currentIndex, and replace wrap matched string in a span
                cellHTML = cellHTML.replace(me.searchRegExp, function(m) {
                    count++;
                    if (me.currentIndex === null) {
                        me.currentIndex = 1;
                    }
                    
                    if(me.currentIndex === count){
                        return '<mark style="background-color:red;">' + m + '</mark>';
                    }
                    return '<mark>' + m + '</mark>';
                   //return '<span style="background-color:yellow;">' + m + '</span>';
                   //return '<mark>' + m + '</mark>';
                });
                me.currentIndex++;
                
                if(me.currentIndex > matches.length){
                    me.currentIndex = null;
                }
                cell.dom.innerHTML = cellHTML;
        }
    }
    /*FIXME this works but somehow destroys the content inside the segment!!
    testSearch:function(testWindow,testDocument,text){
        if (testWindow.find && testWindow.getSelection) {
            testDocument.designMode = "on";
            var sel = testWindow.getSelection();
            sel.collapse(testDocument.body, 0);

            while (testWindow.find(text)) {
                testDocument.execCommand("HiliteColor", false, "yellow");
                sel.collapseToEnd();
            }
            testDocument.designMode = "off";
        } else if (testDocument.body.createTextRange) {
            var textRange = testDocument.body.createTextRange();
            while (textRange.findText(text)) {
                textRange.execCommand("BackColor", false, "yellow");
                textRange.collapse(false);
            }
        }
    },
    */
});
    