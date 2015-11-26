Ext.define('Editor.view.segments.RowEditingNew', {
    extend: 'Ext.grid.plugin.RowEditing',
    alias: 'plugin.segments.roweditingnew',
    requires: [
        'Editor.view.segments.RowEditorNew'
    ],
    initEditor: function() {
        return new Ext.grid.RowEditorNew(this.initEditorConfig());
    }
});