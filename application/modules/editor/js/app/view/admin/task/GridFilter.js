/*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor Javascript GUI and build on ExtJs 4 lib
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
 
 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty
 for any legal issue, that may arise, if you use these FLOSS exceptions and recommend
 to stick to GPL 3. For further information regarding this topic please see the attached 
 license.txt of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
        locked: '#UT#in Arbeit',
        forMe: '#UT#für mich '
    },
    
    stateFilterOrder: ['user_state_open','user_state_waiting','user_state_finished','locked', 'task_state_end'],

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
        
        //we're hardcoding the state filter options order, all other (unordered) utStates are added below
        Ext.Array.each(this.stateFilterOrder, function(state){
            if(msg[state]) {
                states.push([state, msg[state]]);
            }
        });
        
        //adding additional, not ordered utStates
        Ext.Object.each(Editor.data.app.utStates, function(key, value){
            var state = 'user_state_'+key;
            if(!msg[state]) {
                states.push([state, msg.forMe+' '+value]);
            }
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