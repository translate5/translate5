
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
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
 * @class Editor.plugins.SpellCheck.controller.Editor
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.SpellCheck.controller.Editor', {
  extend: 'Ext.app.Controller',
  
  //to include more/own Util files add it here in the array:
  requires: ['Editor.util.SegmentContent'],
  refs:[{
      ref: 'segmentGrid',
      selector:'#segmentgrid'
  },{
      ref: 'editorPanel',
      selector:'#SpellCheckEditorPanel'
  }],
  listen: {
      controller: {
          '#Editor': {
              beforeKeyMapUsage: 'handleEditorKeyMapUsage'
          }
      }
  },
  strings: {
  },
  init: function(){
      console.log('Hello World');
      this.callParent(arguments);
  },
  handleEditorKeyMapUsage: function(conf, area, mapOverwrite) {
      var me = this,
          ev = Ext.event.Event;
      /*
      conf.keyMapConfig['space'] = [ev.SPACE,{ctrl: false, alt: false},function(key) {
          
          console.log("Implement Here the space handler!");
          
      }, true];
      
      conf.keyMapConfig['anotherKey'] = [ev.UP,{ctrl: false, alt: false},function(key) {
          
          console.log("Implement Here the anotherKey handler!");
          
      }, true];
      */
      
  },
  howToAccessTheEditor: function() {
      var me = this,
          plug = me.getSegmentGrid().editingPlugin,
          editor = plug.editor; // → this is the row editor component
     
      editor.mainEditor; // → this is the HtmlEditor
      
      if(editor.isSourceEditing()) {
          //so we are in source editing mode (you checked before if source language is supported)
          return;
      }
  }
});
