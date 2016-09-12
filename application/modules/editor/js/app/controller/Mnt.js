
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
 * @class Editor.controller.Mnt
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.Mnt', {
  extend : 'Ext.app.Controller',
  views: ['MntPanel'],
  listen: {
      component: {
          '#headerPanelNorth': {
              render: 'onHeaderPanelNorthRender',
          },
          '#matchGrid': {
          }
      },
      controller: {
          '#Editor.$application': {
          },
          '#editorcontroller': {
          },
          '#ViewModes':{
          }
      }
  },
  refs:[{
	    ref : 'headerPanelNorth',
	    selector : '#headerPanelNorth'
  }],
  mntStartDate:null,
  mntCountdown:0,
  dategoback:null,
  timer:null,
  onHeaderPanelNorthRender:function(northPanel){
	  var me=this;
	  if(Editor.data.mntStartDate){
		  me.initDate(me);
		  me.timer=setInterval(function(){
			  me.checkTime(me);
		  }, 5000);
	  }
  },
  initDate:function(me){
	  me.mntStartDate=Ext.Date.parse(Editor.data.mntStartDate, 'Y-m-d H:i:s');
	  me.mntCountdown=Editor.data.mntCountdown;
	  me.dategoback = Ext.Date.subtract(me.mntStartDate, Ext.Date.MINUTE,me.mntCountdown);
  },
  checkTime:function(me){
	  var timeDiff = new Date().getTime() - me.dategoback.getTime(),
  	  minutes = Math.round(timeDiff / 60000);//minutes 
	  console.log(minutes);
	  if(minutes>=0){
		 me.injectMntPanel(me);
	  }
  },
  injectMntPanel:function(me){
	  me.getHeaderPanelNorth().add(0,{
		  xtype:'MntPanel',
		  region:'north',
		  executeAt:Ext.Date.format(me.mntStartDate,'Y-m-d H:i:s')
	  });
	  clearTimeout(me.timer);
  }
});
