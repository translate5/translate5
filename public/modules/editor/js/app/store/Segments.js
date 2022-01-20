
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
  remoteFilter: true,
  autoLoad: false,
  //disable automatic load by filter change on grid render, must be reset to false after first load!
  suppressNextFilter: true, 
  firstEditableRow: null,
  constructor: function() {
      var me = this;
      me.callParent(arguments);
      me.proxy.on('metachange', me.handleMetachange, me);
      me.on('clear', me.resetMeta, me);
  },
  resetMeta: function() {
      this.firstEditableRow = null;
  },
  handleMetachange: function(proxy, meta) {
      var me = this;
      if(meta.hasOwnProperty('firstEditable')) {
          me.firstEditableRow = meta.firstEditable;
      }
  },
  getFirsteditableRow: function() {
      return this.firstEditableRow;
  },
  /**
   * Buffered Stores are not made to be editable, so we have to rework some needed methods here
   * @see EXT6UPD-82
   */
  afterEdit: function(record, modifiedFieldNames) {
      this.fireEvent('update', this, record, 'edit', modifiedFieldNames);
  },
  /**
   * Retrieves our url params as used in our proxy
   */
  getParams: function(){
      var proxy = this.getProxy(), params = {};
      params[proxy.getFilterParam()] = proxy.encodeFilters(this.getFilters().items);
      params[proxy.getSortParam()] = proxy.encodeSorters(this.getSorters().items);
      if(proxy.extraParams.qualities && proxy.extraParams.qualities !== ''){
          params.qualities = proxy.extraParams.qualities;
      }
      return params;
  },
  /**
   * Retrieves only our filters as url params as used in our proxy
   */
  getFilterParams: function(){
      var proxy = this.getProxy(), params = {};
      params[proxy.getFilterParam()] = proxy.encodeFilters(this.getFilters().items);
      return params;
  },
  /**
   * retrieves the currently set quality filter
   */
  getQualityFilter: function(){
      var proxy = this.getProxy();
      if(proxy.extraParams.qualities && proxy.extraParams.qualities !== ''){
          return proxy.extraParams.qualities;
      }
      return '';
  },
  /**
   * Sets the qualityFilter, returns, if the filter changed
   */
  setQualityFilter: function(filter){
      var proxy = this.getProxy(), changed = (proxy.extraParams.qualities) ? (proxy.extraParams.qualities !== filter) : (filter !== '');
      if(filter === ''){
          delete proxy.extraParams.qualities;
      } else {
          proxy.setExtraParam('qualities', filter);
      }
      // console.log("SegmentsStore::setQualityFilter:", filter, changed);
      return changed;
  }
});