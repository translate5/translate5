
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

Ext.define('Editor.view.segments.grid.ToolbarViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.segmentsToolbar',

    listen: {
        component: {
            // '#scrollToSegmentBtn': click → see controller.Editor
            '#batchOperations menuitem': {
                click: 'onBatchOperation'
            },
            '#scrollToSegmentBtn': {
                click: 'onBatchOperation'
            }
        }
    },
    getSegmentGrid: function() {
        return this.getView().up('#segmentgrid');
    },
    onBatchOperation: function(menuitem) {
        let me = this,
            grid = me.getSegmentGrid(),
            vm = grid.getViewModel(),
            params = grid.store.getFilterParams();

        if(!menuitem || !menuitem.operation) {
            return;
        }

        params = params || {filter: "[]"};
        params.qualities = grid.store.getQualityFilter();
        if(params.filter === '[]' && params.qualities === '') {
                Ext.Msg.confirm(
                    vm.get('l10n.segmentGrid.batchOperations.warnAllTitle'),
                    vm.get('l10n.segmentGrid.batchOperations.warnAllText'),
                    function(btn){
                    if (btn === 'yes'){
                        me.runBatchOperation(menuitem.operation, params);
                    }
                });
        }
        else {
            me.runBatchOperation(menuitem.operation, params);
        }
    },

    runBatchOperation: function(opName, params) {
        let me = this,
            grid = me.getSegmentGrid();

        grid.view.setBind({
            loading: '{l10n.segmentGrid.batchOperations.loading.'+opName+'}'
        });

        Ext.Ajax.request({
            url: Editor.data.restpath+'segment/'+opName+'/batch',
            method: 'post',
            params: params,
            timeout: 240000,
            scope: me,
            success: function(xhr){
                if (opName.match('lock')) {
                    var json = xhr.responseJson;
                    Editor.app.getController('Segments').updateSegmentFinishCountViewModel(
                        json.taskProgress, json.userProgress
                    );
                }
                grid.store.load({
                    callback: function(){
                        let selSeg = grid.getViewModel().get('selectedSegment'),
                            segNr = selSeg && selSeg.get('segmentNrInTask');
                        segNr && grid.focusSegment(segNr);
                    }
                });
            }
        });
    }
});
