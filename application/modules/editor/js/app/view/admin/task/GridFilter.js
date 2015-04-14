
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
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
 * Editor.view.segments.GridFilter definiert zum einen die Filter für das Segment Grid. 
 * Zum anderen werden einige Methoden des Orginal Filters für Bugfixing überschrieben. 
 * @class Editor.view.segments.GridFilter
 * @extends Ext.ux.grid.FiltersFeature
 */
Ext.define('Editor.view.admin.task.GridFilter', {
    extend: 'Editor.view.segments.GridFilter',
    alias: 'feature.adminTaskGridFilter',
    noRelaisLang: '#UT#- Ohne Relaissprache -',
    
    strings: {
        user_state_open: '#UT#offen',
        user_state_waiting: '#UT#wartend',
        user_state_finished: '#UT#abgeschlossen',
        task_state_end: '#UT#beendet',
        task_state_import: '#UT#beendet',
        locked: '#UT#in Arbeit',
        forMe: '#UT#für mich '
    },
    
    //we must have here an own ordered list of states to be filtered 
    stateFilterOrder: ['user_state_open','user_state_waiting','user_state_finished','locked', 'task_state_end', 'task_state_import'],

    constructor: function(config) {
        var me = this;
        me.callParent([config]);
    },
  /**
   * Gibt die Definitionen der Grid Filter zurück.
   * @returns {Array}
   */
    getFilterForGridFeature: function() {
        var relaisLanguages = Ext.Array.clone(Editor.data.languages),
            msg = this.strings,
            states = [];
        
        //we're hardcoding the state filter options order, all other (unordered) workflow states are added below
        Ext.Array.each(this.stateFilterOrder, function(state){
            if(msg[state]) {
                states.push([state, msg[state]]);
            }
        });
        
        //adding additional, not ordered states
        Ext.Object.each(Editor.data.app.workflows, function(key, workflow){
            Ext.Object.each(workflow.states, function(key, value){
                var state = 'user_state_'+key;
                if(!msg[state]) {
                    states.push([state, msg.forMe+' '+value]);
                }
            });
        });
        
        relaisLanguages.unshift([0, this.noRelaisLang]);
        
        return [{
            dataIndex: 'taskNr'
        },{
            dataIndex: 'taskName'
        },{
            type: 'list',
            options: Editor.data.languages,
            phpMode: false,
            dataIndex: 'sourceLang'
        },{
            type: 'list',
            options: relaisLanguages,
            phpMode: false,
            dataIndex: 'relaisLang'
        },{
            type: 'list',
            options: Editor.data.languages,
            phpMode: false,
            dataIndex: 'targetLang'
        },{
            type: 'list',
            phpMode: false,
            options: states,
            dataIndex: 'state'
        },{
            type: 'numeric',
            dataIndex: 'userCount'
        },{
            dataIndex: 'pmName'
        },{
            type: 'numeric',
            dataIndex: 'wordCount'
        },{
            type: 'date',
            dataIndex: 'targetDeliveryDate'
        },{
            type: 'date',
            dataIndex: 'realDeliveryDate'
        },{
            type: 'boolean',
            dataIndex: 'referenceFiles'
        },{
            type: 'boolean',
            dataIndex: 'terminologie'
        },{
            type: 'boolean',
            dataIndex: 'edit100PercentMatch'
        },{
            type: 'date',
            dataIndex: 'orderdate'
        },{
            type: 'boolean',
            dataIndex: 'enableSourceEditing'
        }];
    }
});