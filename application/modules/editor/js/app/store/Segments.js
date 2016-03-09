
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
 * Store für Editor.model.Segment
 * @class Editor.store.Segments
 * @extends Ext.data.Store
 */
Ext.define('Editor.store.Segments', {
  extend : 'Ext.data.BufferedStore',
  model: 'Editor.model.Segment',
  pageSize: 200,
  remoteSort: true,
  autoLoad: false,
  pageMetaData: [],
  lastEditable: null,
  lastEditableFiltered: null,
  firstEditableRow: null,
  constructor: function() {
      var me = this;
      me.callParent(arguments);
      me.proxy.on('metachange', me.handleMetachange, me);
      me.on('clear', me.resetMeta, me);
  },
  resetMeta: function() {
      this.pageMetaData = [];
      this.lastEditable = null;
      this.lastEditableFiltered = null;
      this.firstEditableRow = null;
  },
  handleMetachange: function(proxy, meta) {
      var me = this;
      if(meta.page === 1 && meta.firstRow) {
          me.firstEditableRow = meta;
      }
      //on adding a trailing page, assume that page is containing the last editable segments
      if(!me.pageMetaData[meta.page]) {
          if(meta.prevRow) {
              me.lastEditable = meta.prevRow;
          }
          if(meta.prevRowFiltered) {
              me.lastEditableFiltered = meta.prevRowFiltered;
          }
      }
      me.pageMetaData[meta.page] = meta;
  },
  /**
   * @see getOtherEditable
   */
  getPrevPageEditable: function(rowIdx, filtered) {
      return this.getOtherEditable('prevRow', rowIdx, filtered);
  },
  /**
   * @see getOtherEditable
   */
  getNextPageEditable: function(rowIdx, filtered) {
      return this.getOtherEditable('nextRow', rowIdx, filtered);
  },
  /**
   * returns previous/next editable segment, if filtered == true, consider autostatefiltered segments only
   * @param direction {String} direction use getPrevEditable / getNextEditable, this methods are setting the direction automatically
   * @param rowIdx {Integer} rowindex from where shall be searched for next / prev segment
   * @param filtered {Boolean} true if autostatefilter should be used
   */
  getOtherEditable: function(direction, rowIdx, filtered) {
      var me = this,
          page = me.getPageFromRecordIndex(rowIdx);
      if(filtered) {
          direction = direction + 'Filtered';
      }
      
      return me.pageMetaData[page] && me.pageMetaData[page][direction];
  }
});