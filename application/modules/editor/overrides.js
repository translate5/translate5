
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
    }
})

/**
 * Fix for TRANSLATE-1041 / EXTJS-24549 / https://www.sencha.com/forum/showthread.php?338435-ext-all-debug-js-206678-Uncaught-TypeError-cell-focus-is-not-a-function
 * needed for ext-6.2.0
 * should be solved natively with next version
 */
Ext.override(Ext.view.Table, {
    privates: {
        setActionableMode: function(enabled, position) {
            var me = this,
                navModel = me.getNavigationModel(),
                activeEl,
                actionables = me.grid.actionables,
                len = actionables.length,
                i, record, column,
                isActionable = false,
                lockingPartner, cell;
            // No mode change.
            // ownerGrid's call will NOT fire mode change event upon false return.
            if (me.actionableMode === enabled) {
                // If we're not actinoable already, or (we are actionable already at that position) return false.
                // Test using mandatory passed position because we may not have an actionPosition if we are 
                // the lockingPartner of an actionable view that contained the action position.
                //
                // If we being told to go into actionable mode but at another position, we must continue.
                // This is just actionable navigation.
                if (!enabled || position.isEqual(me.actionPosition)) {
                    return false;
                }
            }
            // If this View or its lockingPartner contains the current focus position, then make the tab bumpers tabbable
            // and move them to surround the focused row.
            if (enabled) {
                if (position && (position.view === me || (position.view === (lockingPartner = me.lockingPartner) && lockingPartner.actionableMode))) {
                    isActionable = me.activateCell(position);
                }
                // Did not enter actionable mode.
                // ownerGrid's call will NOT fire mode change event upon false return.
                return isActionable;
            } else {
                // Capture before exiting from actionable mode moves focus
                activeEl = Ext.fly(Ext.Element.getActiveElement());
                // Blur the focused descendant, but do not trigger focusLeave.
                // This is so that when the focus is restored to the cell which contained
                // the active content, it will not be a FocusEnter from the universe.
                if (me.el.contains(activeEl) && !Ext.fly(activeEl).is(me.getCellSelector())) {
                    // Row to return focus to.
                    record = (me.actionPosition && me.actionPosition.record) || me.getRecord(activeEl);
                    column = me.getHeaderByCell(activeEl.findParent(me.getCellSelector()));
                    cell = position && position.getCell();
                    // Do not allow focus to fly out of the view when the actionables are deactivated
                    // (and blurred/hidden). Restore focus to the cell in which actionable mode is active.
                    // Note that the original position may no longer be valid, e.g. when the record
                    // was removed.
                    if (!position || !cell) {
                        position = new Ext.grid.CellContext(me).setPosition(record || 0, column || 0);
                        cell = position.getCell();
                    }
                    // Ext.grid.NavigationModel#onFocusMove will NOT react and navigate because the actionableMode
                    // flag is still set at this point.
                    
                    //THIS IS THE FIXED LINE:
                    cell && cell.focus();
                    //ORIGINAL: just cell.focus();
                    
                    // Let's update the activeEl after focus here
                    activeEl = Ext.fly(Ext.Element.getActiveElement());
                    // If that focus triggered handlers (eg CellEditor after edit handlers) which
                    // programatically moved focus somewhere, and the target cell has been unfocused, defer to that,
                    // null out position, so that we do not navigate to that cell below.
                    // See EXTJS-20395
                    if (!(me.el.contains(activeEl) && activeEl.is(me.getCellSelector()))) {
                        position = null;
                    }
                }
                // We are exiting actionable mode.
                // Tell all registered Actionables about this fact if they need to know.
                for (i = 0; i < len; i++) {
                    if (actionables[i].deactivate) {
                        actionables[i].deactivate();
                    }
                }
                // If we had begun action (we may be a dormant lockingPartner), make any tabbables untabbable
                if (me.actionRow) {
                    me.actionRow.saveTabbableState({
                        skipSelf: true,
                        includeSaved: false
                    });
                }
                if (me.destroyed) {
                    return false;
                }
                // These flags MUST be set before focus restoration to the owning cell.
                // so that when Ext.grid.NavigationModel#setPosition attempts to exit actionable mode, we don't recurse.
                me.actionableMode = me.ownerGrid.actionableMode = false;
                me.actionPosition = navModel.actionPosition = me.actionRow = null;
                // Push focus out to where it was requested to go.
                if (position) {
                    navModel.setPosition(position);
                }
            }
        }
    }
});