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

Ext.define('Editor.plugins.Okapi.view.fprm.component.CodefinderViewModel', {
    extend : 'Ext.app.ViewModel',
    alias : 'viewmodel.codefinder',
    data: {
        finderEnabled: 0,
        finderSelection: null,
        finderSelectionEditing: false,
        finderStoreData: []
    },
    stores: {
        finderStore: {
            type: 'json',
            storeId: 'finderStore',
            data: '{finderStoreData}',
        }
    },
    formulas: {
        finderSelectionModified: function(get) {

            // Make sure formula is re-evaluated each time selected record
            // changed or 0-prop changed for currently selected record
            get('finderSelection') && get('finderSelection.0');

            // Check whether 0-key exists in codeFinderSelection.modified
            return !! get('finderSelection') && get('finderSelection').getModified('0') !== undefined;
        },

        moveUpDisabled: function(get) {
            if (!get('finderEnabled') || !get('finderSelection') || get('finderSelectionEditing')) {
                return true;
            } else {
                return get('finderSelection').getId() === get('finderStore').first().getId()
            }
        },

        moveDownDisabled: function(get) {
            if (!get('finderEnabled') || !get('finderSelection') || get('finderSelectionEditing')) {
                return true;
            } else {
                return get('finderSelection').getId() === get('finderStore').last().getId()
            }
        },
    }
});