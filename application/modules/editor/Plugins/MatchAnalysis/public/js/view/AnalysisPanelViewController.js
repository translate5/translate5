
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

Ext.define('Editor.plugins.MatchAnalysis.view.AnalysisPanelViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.matchAnalysisPanel',

    exportAction:function (type){
        var me = this,
            params = {},
            task = me.getView().lookupViewModel(true).get('currentTask');
        params["taskGuid"] = task.get('taskGuid');
        params["type"] = type;
        window.open(Editor.data.restpath+'plugins_matchanalysis_matchanalysis/export?'+Ext.urlEncode(params));
    },

    onExcelExportClick:function(){
        this.exportAction("excel");
    },

    onXmlExportClick:function(){
        this.exportAction("xml");
    },

    /***
     * On match analysis record is loaded in the store
     */
    onAnalysisRecordLoad:function(store) {
        var me=this,
        	view=me.getView(),
        	record=store.getAt(0),
            noRecords=!record;
        
        view.down('#exportExcel').setDisabled(noRecords);
    	if(noRecords){
    		return;
    	}
    	
    	view.down('#analysisDatum').setValue(record.get('created'));
    	view.down('#internalFuzzy').setValue(record.get('internalFuzzy'));
    },
    
    onMatchAnalysisPanelActivate:function(){
    	var me=this;
    	me.getView().down('#matchAnalysisGrid').getStore().load();
    }

});