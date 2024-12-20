
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

Ext.define('Editor.view.task.LogInfoColumn', {
    extend: 'Ext.grid.column.Column',
    requires: [
        'Editor.view.ui.GridLogIcon',
    ],
    alias: 'widget.taskLogInfoColumn',
    tdCls: 'log-info-column',
    dataIndex: 'logInfo',
    stateId: 'logInfo',
    text: Editor.data.l10n.log.logColumnText,
    renderer: function (value, metaData){
        return metaData.column.renderIcons.call(this, value, metaData);
    },
    renderIcons: function (value, metaDataForTooltip) {
        if (!value?.length){
            return '';
        }

        try {
            const messages = value.reduce((acc, logEntry) => {
                const icon = Editor.view.ui.GridLogIcon.getIconByLevel(logEntry.level);
                acc.collected += `${icon}<span class="counter">${logEntry.count}</span>`;

                const formattedMessages = logEntry.message
                    .split('|')
                    .map(msg => `<li>${icon} ${msg}</li>`);

                if (metaDataForTooltip) {
                    acc.messageList.push(...formattedMessages);
                }
                return acc;
            }, { collected: '', messageList: [] });

            if (metaDataForTooltip) {
                metaDataForTooltip.tdAttr = `data-qtip="${Ext.String.htmlEncode(messages.messageList.join(''))}"`;
            }

            return messages.collected;
        } catch (e) {
            console.log(e);
            return '';
        }
    }
});
