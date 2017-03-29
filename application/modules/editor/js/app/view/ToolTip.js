
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3
			 http://www.gnu.org/licenses/agpl.html

END LICENSE AND COPYRIGHT
*/

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Own Tooltip, bindable to iframes per boundFrame option
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
        severity: '#UT#Gewichtung'
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
    show : function(xy) {
        var me = this;
        if (xy && me.boundFrame) {
            xy[0] += me.boundFrame.getX();
            xy[1] += me.boundFrame.getY();
        }
        return me.callParent([xy]);
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
    },
    /**
     * instead of overriding the whole setTarget method just to add the delegated config
     * we invoke into the mon method here to inject the parameter directly
     */
    mon: function() {
        var config = arguments[1];
        if(config && Ext.isObject(config) && config.mouseover && config.mouseout && config.mousemove) {
            config.delegated = false;
        }
        this.callParent(arguments);
    }
});