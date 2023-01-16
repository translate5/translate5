
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
 * @class Editor.view.segments.column.Editable
 * @extends Editor.view.ui.segments.column.Editable
 * @initalGenerated
 */
Ext.define('Editor.view.segments.column.Editable', {
  extend: 'Ext.grid.column.Column',
  alias: 'widget.editableColumn',
  mixins: ['Editor.view.segments.column.BaseMixin'],
  
  dataIndex: 'editable',
  text: 'Gesperrt',
  
  filter: {
       type: 'boolean',
       updateBuffer: 0,
       //yes / no must be flipped, since in DB its editable, but in GUI we speak from blocked (so negated)
       yesText: Ext.grid.filters.filter.Boolean.prototype.noText,
       noText: Ext.grid.filters.filter.Boolean.prototype.yesText
  },
  editor: {
    xtype: 'displayfield',
    cls: 'editable',
    //dummy Method, mit der Orginal Methode funktioniert die Anzeige der Checkbox nicht richtig
    getModelData: function() {
      return null;
    }
  },
  initComponent: function() {
    var me = this;
    me.initBaseMixin();
    me.callParent(arguments);
  },
  /**
   * rendert einen boolean Wert in eine rein visuelle Checkbox
   * @param {boolean} value
   * @returns {String}
   */
  renderer : function(value){
    var cssPrefix = Ext.baseCSSPrefix,
        cls = [cssPrefix + 'grid-checkcolumn'];

    if (! value) {
        cls.push(cssPrefix + 'grid-checkcolumn-checked');
    }
    return '<div class="' + cls.join(' ') + '">&#160;</div>';
  }
});