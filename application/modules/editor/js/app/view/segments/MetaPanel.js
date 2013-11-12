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
 * @class Editor.view.segments.MetaPanel
 * @extends Editor.view.ui.segments.MetaPanel
 * @initalGenerated
 */
Ext.define('Editor.view.segments.MetaPanel', {
  extend: 'Editor.view.ui.segments.MetaPanel',
  alias: 'widget.segments.metapanel',
  initComponent: function() {
    var me = this;
    me.callParent(arguments);
    this.addQualityFlags();
    this.addStateFlags();
  },
  /**
   * Fügt anhand der php2js Daten die Status Felder hinzu
   */
  addStateFlags: function() {
    var me = this,
    stati = me.down('#metaStates'),
    flags = Editor.data.segments.stateFlags;
    Ext.each(flags, function(item){
      stati.add({
        name: 'stateId',
        inputValue: item.id,
        boxLabel: item.label
      });
    });
  },
  /**
   * Fügt anhand der php2js Daten die QM Felder hinzu
   */
  addQualityFlags: function() {
    var me = this,
    qm = me.down('#metaQm'),
    flags = Editor.data.segments.qualityFlags;
    Ext.each(flags, function(item){
      qm.add({
        xtype: 'checkbox',
        name: 'qmId', 
        inputValue: item.id,
        boxLabel: item.label
      });
    });
  }
});