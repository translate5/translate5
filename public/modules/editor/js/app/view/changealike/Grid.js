
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
 * @class Editor.view.changealike.Grid
 * @extends Editor.view.ui.changealike.Grid
 */
Ext.define('Editor.view.changealike.Grid', {
  extend: 'Editor.view.ui.changealike.Grid',
  alias: 'widget.changealikeGrid',
  plugins: ['gridfilters'],
  store: 'AlikeSegments',
  id: 'changealike-grid',
  /**
   * sets and displays the records given as loaded operation, does also selectAll items
   * @param {Editor.model.AlikeSegment} records
   */
  setAlikes: function(records) {
      var me = this, toBeSelected = [];
      me.store.removeAll();
      if(me.filters) {
          me.filters.clearFilters();
      }
      me.store.loadRecords(records);

      // If repetitions, that are target-only repetitions - should be excluded from pre-selection
      if (Editor.app.getUserConfig('alike.deselectTargetOnly')) {

          // Foreach repetition
          records.forEach(record => {

              // If current repetitions is NOT target-only repetition
              if (!record.isTargetOnly()) {

                  // Make sure it to be pre-selected
                  toBeSelected.push(record);
              }
          });

          // If we have something to be selected - do select
          if (toBeSelected.length) {
              me.getSelectionModel().select(toBeSelected);
          }

      // Else just select all repetiions by default
      } else {
          me.getSelectionModel().selectAll();
      }
  }
});