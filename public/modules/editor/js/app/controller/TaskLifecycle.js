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
 * Will manage the task unlock when the browser tab is closed. When user did close the task without
 * leaving it via the UI, the task will stay locked by this user for some time. This also can block other features
 * like send to human revision etc ...
 */
Ext.define('Editor.controller.TaskLifecycle', {
    extend: 'Ext.app.Controller',

    init: function() {
        // in case logout on windows close is active, we will sendBeacon in the shared.js to unlock the task to
        // reason is there is not garantie that this beforeunload event is goint go be triggered.

        if (Editor.data.logoutOnWindowClose) {
            return;
        }
        this.listenToBrowserEvents();
    },

    /**
     * Sets up native browser listeners.
     */
    listenToBrowserEvents: function() {
        var me = this;

        // Fallback Trigger: Handles specifically the "X" button on desktop browsers
        window.addEventListener('beforeunload', function() {
            me.emergencyUnlock();
        });
    },

    /**
     * Performs the "Fire and Forget" unlock. No UI masks, No confirmation dialogs, No waiting.
     */
    emergencyUnlock: function() {
        var task = Editor.data.task;

        if (!task || !task.getId()) {
            return;
        }

        var url = Editor.data.restpath + 'taskunlock/' + task.getId();

        var payload = {
            "id": task.getId()
        };

        var formData = new FormData();

        formData.append('data', JSON.stringify(payload));

        if (Editor.data.csrfToken) {
            formData.append('CsrfToken', Editor.data.csrfToken);
        }

        navigator.sendBeacon(url, formData);
    }
});
