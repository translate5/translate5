
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

Ext.define('Editor.view.admin.task.UserAssocViewModel', {
    extend: 'Ext.app.ViewModel',
    alias: 'viewmodel.taskuserassoc',
    requires: [
       'Ext.data.Store',
       'Editor.model.admin.User'
    ],
    strings: {
        editInfo: '#UT#Wählen Sie einen Eintrag in der Tabelle aus um diesen zu bearbeiten!',
        segmentrangeError: '#UT#Nicht zugewiesene Segmente',
        translator: '#UT#Übersetzer',
        translatorCheck: '#UT#Zweiter Lektor',
        reviewer: '#UT#Lektor',
        visitor: '#UT#Besucher',
        allEditedByUsers:'#UT#(alle Segmente können von allen Nutzern editiert werden)',
        canNotBeEditedByUsers:'#UT#(können derzeit von niemandem bearbeitet werden)'
    },
    
    stores: {
        users: {
            model: 'Editor.model.admin.User',
            autoLoad: false,
            pageSize: 0
        },
        states: {
            data: '{statesData}'
        },
        steps: {
            data: '{stepsData}'
        },
        userAssoc:{
            model:'Editor.model.admin.TaskUserAssoc',
            remoteFilter: true,
            pageSize: false,
            setFilters:function(filters){
                //the binding is also triggered when the value is empty. Ignore the filtering with empty value
                if(filters && !filters.value){
                    this.loadData([],false);
                    return;
                }
                this.superclass.superclass.setFilters.apply(this, [filters]);
            },
            filters:{
                property: 'taskGuid',
                operator:"eq",
                value:'{projectTaskSelection.taskGuid}'
            }
        }
    },
    formulas: {
        enablePanel:{
            get: function (task) {
                return task && (task.isUnconfirmed() || task.isOpen());
            },
            bind:{bindTo:'{currentTask}',deep:true}
        },
        editInfoHtml:{
            get: function (task) {
                var me= this,
                    html = me.strings.editInfo,
                    missingsegmentranges,
                    i,
                    workflowdata,
                    workflowsteps,
                    stepname,
                    allUnassigned=true;
                if (task === null) {
                    return html;
                }
                missingsegmentranges = task.get('missingsegmentranges');
                if(missingsegmentranges && missingsegmentranges.length > 0) {
                    workflowdata =  task.getWorkflowMetaData();
                    workflowsteps = workflowdata.steps;
                    html += '<hr><span class="errors">' + me.strings.segmentrangeError + ':</span><br>';
                    for (i = 0; i < missingsegmentranges.length; i++) {
                        allUnassigned=true;
                        stepname = missingsegmentranges[i]['workflowStepName'];
                        //check if there are assigned users of the curent step
                        for(var u=0;u<task.get('users').length;u++){
                            var assoc=task.get('users')[u];
                            if(assoc.workflowStepName===stepname && assoc.segmentrange && assoc.segmentrange!==""){
                                allUnassigned=false;
                            }
                        }
                        //add aditional text if all are unasigned or there are asigned users to a role
                        if(allUnassigned){
                            html += ' '+me.strings.allEditedByUsers + '<br>';
                        }else{
                            html += ' '+me.strings.canNotBeEditedByUsers + '<br>';
                        }
                        
                        html += '- ' + workflowsteps[stepname] + ': ' + missingsegmentranges[i]['missingSegments'] + '<br>';
                        html += '<hr>';
                    } 
                }
                return html;
            },
            bind:{bindTo:'{currentTask}',deep:true}
        },
        statesData: {
            get: function (get) {
            	var task=get('currentTask'),
                	states = [],
                	metaData = task ? task.getWorkflowMetaData() : [];
	            Ext.Object.each(metaData.states, function(key, state) {
	                states.push({id: key, text: state});
	            });
                return states;
            }
        },
        stepsData: {
            get: function (task) {
                var me = this,
                    metaData = task && task.getWorkflowMetaData(),
                    steps = [],
                    added = [];

                if(!metaData) {
                    return [];
                }

                Ext.Array.each(metaData.stepChain, function (key) {
                    if(metaData.usableSteps[key]){
                        steps.push({id: key, text: metaData.usableSteps[key]});
                        added.push(key);
                    }
                });

                Ext.Object.each(metaData.usableSteps, function(key,value){
                    if(!Ext.Array.contains(added, key)){
                        steps.push({id: key, text: value});                
                    }
                });

                return steps;
            },
            bind:{bindTo:'{currentTask}',deep:true}
        }
    }
});