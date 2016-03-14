
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
 * @class Editor.model.Comment
 * @extends Ext.data.Model
 */
Ext.define('Editor.plugins.mtComparEval.model.Taskmeta', {
  extend: 'Ext.data.Model',
  STATE_IMPORTING: 'importing',
  STATE_IMPORTED: 'imported',
  fields: [
    {name: 'taskGuid', type: 'string', persist: false},
    {name: 'mtCompareEvalState', type: 'string', persist: true},
    {name: 'mtCompareURL', type: 'string', persist: false},
    {name: 'mtCompareEvalId', type: 'string', persist: false}
  ],
  /**
   * since state could be null or "notsent" for not sent state, we check this in a separate method
   * @return {Boolean}
   */
  isNotImported: function() {
      var me = this,
          state = me.get('mtCompareEvalState');
      return state !== me.STATE_IMPORTING && state !== me.STATE_IMPORTED;
  },
  isImporting: function() {
      return this.get('mtCompareEvalState') === this.STATE_IMPORTING;
  },
  isImported: function() {
      return this.get('mtCompareEvalState') === this.STATE_IMPORTED;
  },
  idProperty: 'taskGuid',
  proxy : {
    type : 'rest',
    url: Editor.data.restpath+'taskmeta',
    reader : {
      rootProperty: 'rows',
      type : 'json'
    },
    writer: {
      encode: true,
      rootProperty: 'data',
      writeAllFields: false
    }
  }
});