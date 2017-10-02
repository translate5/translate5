
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
 * Fixing missing contains method for bufferedstores
 * needed for ext-6.0.0
 * recheck on update
 */
Ext.define('Ext.overrides.fixed.BufferedStore', {
    override: 'Ext.data.BufferedStore',
    contains: function(record) {
        return this.indexOf(record) > -1;
    }
});


/**
 * Fix for EXT6UPD-33
 * needed for ext-6.0.0
 * should be solved natively with ext-6.0.1
 */
Ext.define('Ext.overrides.fixed.PageMap', {
    override: 'Ext.data.PageMap',
    getByInternalId: function(internalId) {
        var index = this.indexMap[internalId];
        if (index != null) {
            return this.getAt(index);
        }
    }
});

/**
 * Fix for EXT6UPD-46
 * needed for ext-6.0.0
 * should be solved natively with ext-6.0.1
 */
Ext.define('Ext.overrides.fixed.ListFilter', {
    override: 'Ext.grid.filters.filter.List',
    getGridStoreListeners: function() {
        if(this.autoStore) {
            return this.callParent(arguments);
        }
        return {};
    }
});

/**
* @property {RegExp}
* @private
* Regular expression used for validating identifiers.
* !!!WARNING!!! This  and next override is made to allow ids starting with a digit. This is due to the bulk of legacy data
*/
Ext.validIdRe = /^[a-z0-9_][a-z0-9\-_]*$/i;
Ext.define('Ext.overrides.dom.Element', {
    override: 'Ext.dom.Element',
    
    constructor: function(dom) {
        this.validIdRe = Ext.validIdRe;
        this.callParent(arguments);
    }
});

/**
 * fixing for this bug: https://www.sencha.com/forum/showthread.php?288898-W-targetCls-is-missing.-This-may-mean-that-getTargetEl()-is-being-overridden-but-no/page3
 * needed for ext-6.0.0
 * recheck on update
 */
Ext.define('Ext.overrides.layout.container.Container', {
  override: 'Ext.layout.container.Container',

  notifyOwner: function() {
    this.owner.afterLayout(this);
  }
});

/**
 * enables the ability to set a optional menuOffset in menus
 * needed for ext-6.0.0
 * this override must be revalidated on extjs update
 */
Ext.override(Ext.menu.Item, {
    deferExpandMenu: function() {
        var me = this;

        if (!me.menu.rendered || !me.menu.isVisible()) {
            me.parentMenu.activeChild = me.menu;
            me.menu.parentItem = me;
            me.menu.parentMenu = me.menu.ownerCt = me.parentMenu;
            me.menu.showBy(me, me.menuAlign, me.menuOffset);
        }
    }
});

/**
 * TRANSLATE-834: Triton Theme: Tooltip on columns is missing
 * All columns should have a tooltip with the same content as the title when nothing other is configured
 */
Ext.override(Ext.grid.column.Column, {
    initConfig: function(config) {
        if(config.tooltip === undefined) {
            config.tooltip = Ext.String.htmlEncode(config.text||this.text);
        }
        return this.callParent([config]);
    }
});

/**
 * Fixing EXT6UPD-131 (fixed natively in ext-6.0.1, must be removed then!)
 */
Ext.override(Ext.grid.filters.filter.TriFilter, {
    deactivate: function () {
        var me = this,
            filters = me.filter,
            f, filter, value;

        if (!me.countActiveFilters() || me.preventFilterRemoval) {
            return;
        }

        me.preventFilterRemoval = true;

        for (f in filters) {
            filter = filters[f];

            value = filter.getValue();
            if (value || value === 0) {
                me.removeStoreFilter(filter);
            }
        }

        me.preventFilterRemoval = false;
    }
});

/**
 * Fix for EXTJS-18481
 * needed for ext-6.0.0
 * should be solved natively with ext-6.0.1
 */
Ext.define('MyApp.overrides.data.request.Ajax', {
    override: 'Ext.data.request.Ajax',
    /** * Overrideing this method as getResponse header does not work * @param xhr * @returns {{request: Egain.overrides.data.request.Ajax, requestId: *, status: *, statusText: *, getResponseHeader: (Ext.data.request.Base.privates._getHeader|Function), getAllResponseHeaders: (Ext.data.request.Base.privates._getHeaders|Function)}|*} */
    createResponse: function(xhr) {
        var me = this,
            isXdr = me.isXdr,
            headers = {},
            lines = isXdr ? [] : xhr.getAllResponseHeaders().replace(/\r\n/g, '\n').split('\n'),
            count = lines.length,
            line, index, key, response, byteArray;
        while (count--) {
            line = lines[count];
            index = line.indexOf(':');
            if (index >= 0) {
                key = line.substr(0, index).toLowerCase();
                if (line.charAt(index + 1) == ' ') {
                    ++index;
                }
                headers[key] = line.substr(index + 1);
            }
        }
        response = {
            request: me,
            requestId: me.id,
            status: xhr.status,
            statusText: xhr.statusText,
            getResponseHeader: function(name) {
                return headers[name.toLowerCase()];
            },
            getAllResponseHeaders: function() {
                return headers;
            }
        };
        if (isXdr) {
            me.processXdrResponse(response, xhr);
        }
        if (me.binary) {
            response.responseBytes = me.getByteArray(xhr);
        } else {
            // an error is thrown when trying to access responseText or responseXML 
            // on an xhr object with responseType of 'arraybuffer', so only attempt 
            // to set these properties in the response if we're not dealing with 
            // binary data
            response.responseText = xhr.responseText;
            response.responseXML = xhr.responseXML;
        }
        return response;
    },
});

Ext.override(Ext.util.CSS, {
    /***
     * Add a custom css to the given html page
     */
    createStyleSheetToWindow : function(window,cssText, id) {
        var ss,
            head = window.getElementsByTagName("head")[0],
            styleEl = window.createElement("style");

        styleEl.setAttribute("type", "text/css");
        if (id) {
           styleEl.setAttribute("id", id);
        }

        if (Ext.isIE) {
           head.appendChild(styleEl);
           ss = styleEl.styleSheet;
           ss.cssText = cssText;
        } else {
            try{
                styleEl.appendChild(window.createTextNode(cssText));
            } catch(e) {
               styleEl.cssText = cssText;
            }
            head.appendChild(styleEl);
            ss = styleEl.styleSheet ? styleEl.styleSheet : (styleEl.sheet || window.styleSheets[window.styleSheets.length-1]);
        }
        this.cacheStyleSheet(ss);
        return ss;
    },
})