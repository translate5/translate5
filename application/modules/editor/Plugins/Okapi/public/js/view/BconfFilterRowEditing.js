/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Custom Row Editor for bconf filter grid
 */
Ext.define('Editor.plugins.Okapi.view.BconfFilterRowEditing', {
    extend: 'Ext.grid.plugin.RowEditing',
    clicksToEdit: 2, // QUIRK: 1 not possible, triggers on actioncolumns TODO: limit to non actionCols, add pointerCls
    id: 'rowEditing',
    removeUnmodified: true,
    listeners: {
        beforeedit: function(editor, context){
            console.log('BconfFilterRowEditing: beforeedit ', editor, context);
        },
        validateedit: function(editor, context){
            console.log('BconfFilterRowEditing: validateedit ', editor, context);
        }
    }
});