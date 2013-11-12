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
Ext.define('Editor.view.changealike.GridFilter', {
    extend: 'Editor.view.segments.GridFilter',
    alias: 'feature.alikeGridFilter',


    constructor: function(config) {
        var me = this;
        config.local = true;
        me.callParent([config]);
    },
    /**
   * Gibt die Definitionen der Grid Filter zurück.
   * @returns {Array}
   */
    getFilterForGridFeature: function() {
        return [{
            type: 'numeric',
            dataIndex: 'segmentNrInTask'
        },{
            type: 'boolean',
            dataIndex: 'sourceMatch'
        },{
            type: 'boolean',
            dataIndex: 'targetMatch'
        },{
            dataIndex: 'source'
        },{
            dataIndex: 'target'
        },{
        	dataIndex: 'relais'
        },{
            dataIndex: 'infilter'
        }];
    }
});