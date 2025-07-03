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
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.plugins.Okapi.view.fprm.component.CodefinderViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.codefinder',
    control: {
        'button#add': {
            click: 'onRuleAddClick'
        },
        'button#remove': {
            click: 'onRuleRemoveClick'
        },
        'button#modify': {
            click: 'onRuleModifyClick'
        },
        'button#discard': {
            click: 'onRuleDiscardClick'
        },
        'button#accept': {
            click: 'onRuleAcceptClick'
        },
        'button#up': {
            click: 'onRuleUpClick'
        },
        'button#down': {
            click: 'onRuleDownClick'
        },
        'grid': {
            selectionchange: 'syncSample'
        },
        'checkbox': {
            change: 'syncSample'
        },
        'textareafield#sample': {
            change: 'syncSample'
        },
        'textareafield#result': {
            afterrender: 'syncSample'
        }
    },

    onRuleAddClick: function () {
        const grid = this.getView().down('grid'),
            store = this.getViewModel().get('finderStore');
        if (store.last()?.get('0').trim() !== '') {
            store.add({0: ''});
        }
        grid.getSelectionModel().select(store.getCount() - 1);
        this.onRuleModifyClick();
    },

    onRuleRemoveClick: function() {
        const grid = this.getView().down('grid'),
            selectedRecord = grid.getSelectionModel().getSelection()[0];
        selectedRecord.store.remove(selectedRecord);
        grid.getSelectionModel().select(0);
        grid.getView().focusRow(0);
        this.syncSample();
    },

    onRuleModifyClick: function() {
        this.getViewModel().set('finderSelectionEditing', true);
    },

    onRuleAcceptClick: function() {
        try {
            const re = new RegExp(this.getView().down('#expr').getValue());
        } catch(e) {
            return;
        }
        const vm = this.getViewModel();
        vm.get('finderSelection').commit();
        vm.set('finderSelectionEditing', false);
        this.syncSample();
    },

    onRuleDiscardClick: function() {
        const vm = this.getViewModel();
        vm.get('finderSelection').reject();
        vm.set('finderSelectionEditing', false);
    },

    onRuleUpClick: function() {
        const grid = this.getView().down('grid'),
            selectedRecord = grid.getSelectionModel().getSelection()[0],
            newIdx = selectedRecord.store.indexOf(selectedRecord)-1;
        if (newIdx >= 0) {
            const val = selectedRecord.get('0'), swapRecord = selectedRecord.store.getAt(newIdx);
            selectedRecord.set('0', swapRecord.get('0'));
            swapRecord.set('0', val);
            grid.getSelectionModel().select(newIdx);
            grid.getView().focusRow(newIdx);
            this.syncSample();
        }
    },

    onRuleDownClick: function() {
        const grid = this.getView().down('grid'),
            selectedRecord = grid.getSelectionModel().getSelection()[0],
            newIdx = selectedRecord.store.indexOf(selectedRecord)+1;
        if (newIdx < selectedRecord.store.getCount()) {
            const val = selectedRecord.get('0'), swapRecord = selectedRecord.store.getAt(newIdx);
            selectedRecord.set('0', swapRecord.get('0'));
            swapRecord.set('0', val);
            grid.getSelectionModel().select(newIdx);
            grid.getView().focusRow(newIdx);
            this.syncSample();
        }
    },

    syncSample: function() {
        const rules = [];
        if(this.getView().down('#useAllRulesWhenTesting').checked){
            const store = this.getViewModel().get('finderStore');
            store.each(function (rec) {
                rules.push(rec.get('0'));
            });
        } else {
            let grid = this.getView().down('grid'),
                selectedRecord = grid.getSelectionModel().getSelection()[0];
            if(!selectedRecord) {
                const store = grid.getStore();
                if(store.getCount() === 1) {
                    selectedRecord = store.getAt(0);
                }
            }
            if(selectedRecord){
                rules.push(selectedRecord.get('0'));
            }
        }

        let rule, re, idx = 1, str = this.getView().down('#sample').getValue();

        for (rule of rules) {
            re = new RegExp(rule, 'g');
            str = str.replace(re, function () {
                return "<" + (idx++) + "/>";
            });
        }
        this.getView().down('#result').setValue(str);
    }

});