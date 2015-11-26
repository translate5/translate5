Ext.define('Editor.view.segments.RowEditorNew', {
    extend: 'Ext.grid.RowEditor',
    alias: 'widget.roweditornew',
    
    onViewScroll: function(){
        
    },
    
    calculateEditorTop: function(rowTop) {
        return 50;
    },
    
    syncButtonPosition: function(scrollDelta) {
        var me = this,
            floatingButtons = me.getFloatingButtons();
            
        floatingButtons.setButtonPosition('top'); // just to hide them

        return scrollDelta;
    }
});