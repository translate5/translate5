
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

Ext.define('Editor.view.segments.GridViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.segmentsGrid',

    listen: {
        component: {
            '#toggleTaskDesc':{
                toggle:'onToggleTaskDesc'
            },
            '#segmentgrid': {
                select:'onSegmentGridSelect',
                filterchange: 'onGridFilterChange'
            },
            '#segmentgrid gridcolumn[filter]': {
                afterrender: 'onGridColumnAfterRender',
                hide: 'onGridColumnHide',
                show: 'onGridColumnShow'
            }
        }
    },

    activeFilters: [],

    /**
     * @param {Ext.selection.RowModel} rowmodel
     * @param {Editor.model.Segment} segment
     */
    onSegmentGridSelect: function(rowmodel, segment) {
        this.getViewModel().set('selectedSegment', segment);
    },
    onToggleTaskDesc: function(btn, toggled) {
        btn.setText(toggled ? btn.hideText : btn.showText);
        this.getView().down('#taskDescPanel').setVisible(toggled);
    },

    onGridColumnAfterRender: function(column) {
        let fset = column.up('grid').down('fieldset'),
            text = ~['contentColumn','contentEditableColumn','commentsColumn','usernameColumn'].indexOf(column.xtype),
            ctor = {
                xtype: text ? 'textfield' : 'displayfield',
                disabled: !text,
                name: column.dataIndex || column.stateId,
                fieldLabel: Ext.util.Format.stripTags(column.text),
                fieldLabelFit: true,
                hidden: column.hidden,
                labelStyle: text ? '' : 'min-height: 24px;',
                fieldStyle: text ? '' : 'min-height: 24px;',
                checkChangeBuffer: 500,
                listeners: {
                    change: (ff, value) => ff.up('grid')
                        .down(`gridcolumn[dataIndex=${ff.name}], gridcolumn[stateId=${ff.name}]`)
                        .filter.setValue(value)
                }
            };

        // Make sure filter-fields for text filters appear first,
        // so that when the remaining ones will be shown/hidden
        // the major filter-fields won't jump horizontally within the fieldset
        if (text) {
            fset.insert(fset.query('textfield[disabled=false]').length, ctor);
        } else {
            fset.add(ctor);
        }
    },

    onGridFilterChange: function(store, filters) {

        // Get property name for which the filters are currently in use
        this.activeFilters = {}; filters.forEach(filter => this.activeFilters[filter.config.property] = filter);

        //
        let column, values, grid = this.getView();

        // Make sure the fields are kept shown only for the in-use filters, except for textfield
        grid.query('fieldset > field').forEach(ff => {

            // Get corresponding grid column
            column = grid.down(`gridcolumn[dataIndex=${ff.name}], gridcolumn[stateId=${ff.name}]`);

            // If filter is currently active or corresponding grid column is not hidden
            if (ff.name in this.activeFilters || column.isVisible()) {

                // Prevent recursion
                ff.suspendEvent('change');

                // Show field
                ff.setHidden(false);

                // If filter type is 'list'
                if (column.filter.type.match(/list|workflowStep/)) {

                    if (column.filter.options) {

                        // Get foreground values based on background ones
                        values = column.filter.filter.getValue().map(
                            value => Ext.Array.findBy(column.filter.options, option => option.id === value).label
                        );
                    } else if (column.filter.store) {

                        if (column.filter.store.getCount() === 0) {
                            column.filter.store.rejectChanges();
                        }

                        // Get foreground values based on background ones
                        values = column.filter.filter.getValue().map(
                            value => column.filter.store.getById(value).get('label')
                        );
                    }

                    // Set to field as comma-separated list
                    ff.setValue(values.join(', '));

                } else if (column.filter.type === 'string') {
                    ff.setValue(column.filter.filter.getValue());
                } else if (column.filter.type === 'boolean') {
                    ff.setValue(column.filter.filter.getValue() ? column.filter.yesText : column.filter.noText);
                } else if (column.filter.type === 'numeric') {

                    // Setup shortcuts
                    let eq = column.filter.filter.eq.getValue(),
                        gt = column.filter.filter.gt.getValue(),
                        lt = column.filter.filter.lt.getValue(),
                        value = [];

                    // Setup human-readable value
                    if (eq !== null) {
                        value.push(`= ${eq}`);
                    } else {
                        if (gt !== null) value.push(`> ${gt}`);
                        if (lt !== null) value.push(`< ${lt}`);
                    }
                    ff.setValue(value.join(', '));
                }

                // Resume change-event to get back fieldset's field working
                ff.resumeEvent('change');

            // Else keep hidden
            } else {
                ff.setHidden(ff.disabled);
            }
        });
    },

    onGridColumnHide: function(column) {
        let field = this.getView().down(`#filterToolbar field[name/="${column.stateId}|${column.dataIndex}"]`);

        if (!(field.name in this.activeFilters)) {
            field.hide();
        }
    },

    onGridColumnShow: function(column) {
        this.getView().down(`#filterToolbar field[name/="${column.stateId}|${column.dataIndex}"]`).show();
    }
});
