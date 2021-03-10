
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
Ext.define('Editor.plugins.MatchAnalysis.view.AnalysisPanelViewModel', {
    extend : 'Ext.app.ViewModel',
    alias : 'viewmodel.matchAnalysisPanel',

    formulas : {
        isAnalysisRunning : {
            get : function (task) {
                return !task ? false : task.isAnalysis();
            },
            bind : {
                bindTo : '{currentTask}',
                deep : true
            }
        },
        enablePanel : {
            get : function (task) {
                // if import status error disabled
                return task && (!task.isErroneous() && !task.isImporting());
            },
            bind : {
                bindTo : '{currentTask}',
                deep : true
            }
        },
        getEdit100PercentMatchLableText : {
            get : function (task) {
                if(!task){
                    return false;
                }
                var strings = this.getView().strings;
                return task.get('edit100PercentMatch') ? strings.edit100PercentMatchEnabledMessage : strings.edit100PercentMatchDisabledMessage;
            },
            bind : {
                bindTo : '{currentTask}',
                deep : true
            }
        }
    },

    stores : {
        // this store is defined here because the reference filter binding is
        // required
        analysisStore : {
            model : 'Editor.plugins.MatchAnalysis.model.MatchAnalysis',
            remoteSort : true,
            remoteFilter : true,
            pageSize : false,
            autoLoad : true,
            listeners : {
                load : 'onAnalysisRecordLoad'
            },
            setFilters : function (filters) {
                // the binding is triggered wiht empty values to, we do not want
                // to filter for empty taskGuid
                if (filters && !filters.value) {
                    this.loadData([], false);
                    return;
                }
                this.superclass.superclass.setFilters.apply(this, [ filters ]);
            },
            filters : {
                property : 'taskGuid',
                operator : "eq",
                value : '{projectTaskSelection.taskGuid}'
            }
        }
    }
});