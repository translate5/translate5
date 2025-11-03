
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

/**
 * @class Editor.store.admin.task.Jobs
 * @extends Ext.data.Store
 */
Ext.define('Editor.store.admin.task.Jobs', {
    requires: [
        'Editor.model.admin.TaskUserAssoc',
    ],
    extend : 'Ext.data.Store',
    model: 'Editor.model.admin.TaskUserAssoc',
    remoteFilter: false,
    pageSize: false,
    autoLoad: false,
    logProxyUrl: function (msg) {
        console.log(
            msg + ': ',
            this.getProxy ?
                (this.getProxy()
                    ? this.getProxy().getUrl()
                    : 'no proxy object')
                : 'no getProxy method'
        );
    },
    load: function(options) {
        this.logProxyUrl('load1');                                                        // +
        var me = this;
        // Legacy option. Specifying a function was allowed.
        if (typeof options === 'function') {
            options = {
                callback: options
            };
        } else {
            // We may mutate the options object in setLoadOptions.
            options = options ? Ext.Object.chain(options) : {};
        }
        me.pendingLoadOptions = options;
        // If we are configured to load asynchronously (the default for async proxies)
        // then schedule a flush, unless one is already scheduled.
        if (me.getAsynchronousLoad()) {
            if (!me.loadTimer) {
                this.logProxyUrl('load2');                                               // +
                me.loadTimer = Ext.asap(me.flushLoad, me);
            }
        } else // If we are configured to load synchronously (the default for sync proxies)
            // then flush the load now.
        {
            this.logProxyUrl('load3');                                                   // +
            me.flushLoad();
        }
        return me;
    },
    /**
     * Called when the event handler which called the {@link #method-load} method exits.
     */
    flushLoad: function() {
        var me = this,
            options = me.pendingLoadOptions,
            operation;

        this.logProxyUrl('flushLoad1');                                                  // +
        // If it gets called programatically before the timer fired, the listener will need cancelling.
        me.clearLoadTask();
        if (!options) {
            return;
        }
        this.logProxyUrl('flushLoad2');                                                  // +
        me.setLoadOptions(options);
        if (me.getRemoteSort() && options.sorters) {
            me.fireEvent('beforesort', me, options.sorters);
            this.logProxyUrl('flushLoad3');                                              // +
        }
        operation = Ext.apply({
            internalScope: me,
            internalCallback: me.onProxyLoad,
            scope: me
        }, options);
        me.lastOptions = operation;
        operation = me.createOperation('read', operation);
        this.logProxyUrl('flushLoad4');
        if (me.fireEvent('beforeload', me, operation) !== false) {
            this.logProxyUrl('flushLoad5');                                              // +
            me.onBeforeLoad(operation);
            this.logProxyUrl('flushLoad6');                                              // +
            me.loading = true;
            operation.execute();
        }
    },
});
