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
 * Own Tooltip, bindable to iframes per per boundFrame option
 * 
 * @class Editor.view.ToolTip
 * @extends Ext.tip.ToolTip
 */
Ext.define('Editor.view.ToolTip', {
	extend : 'Ext.tip.ToolTip',
	//enable own ToolTips only for the following img classes 
	delegate : 'img.ownttip', // accepts only simple selectors (no commas) so
	// define a own tooltip class
	renderTo : Ext.getBody(),
	strings: {
		severity: '##UT##Gewichtung'
	},
	listeners : {
		// Change content dynamically depending on which element triggered
		// the show.
		beforeshow : function(tip) {
			var t = tip.triggerElement,
				fly = Ext.fly(t); 
			if(fly.hasCls('qmflag')) {
				this.handleQmFlag(t, tip);
			}
			//else if hasClass for other ToolTip Types
		}
	},
	show : function() {
		var me = this;
		if (me.targetXY && me.boundFrame) {
			me.targetXY[0] += me.boundFrame.getX();
			me.targetXY[1] += me.boundFrame.getY();
		}
		return me.callParent(arguments);
	},
	onTargetOver: function(e) {
		e.preventDefault(); //prevent title tags to be shown in IE
		this.callParent(arguments);
	},
	handleQmFlag: function(t, tip) {
		var me = this, 
			qmtype,
			cache = Editor.qmFlagTypeCache,
			meta = {sevTitle: me.strings.severity};
		
		qmtype = t.className.match(/qmflag-([0-9]+)/);
		if(qmtype && qmtype.length > 1) {
			meta.cls = t.className.split(' ');
			meta.sev = Ext.StoreMgr.get('Severities').getById(meta.cls.shift());
			meta.sev = meta.sev ? meta.sev.get('text') : '';
			meta.qmid = qmtype[1];
			meta.comment = Ext.fly(t).getAttribute('data-comment');
			meta.qmtype = cache[meta.qmid] ? cache[meta.qmid] : 'Unknown Type'; //impossible => untranslated
		}
		if(!me.qmflagTpl) {
			me.qmflagTpl = new Ext.Template('<b>{qmtype}</b><br />{sevTitle}: {sev}<br />{comment}');
			me.qmflagTpl.compile();
		}
		tip.update(me.qmflagTpl.apply(meta));		
	}
});