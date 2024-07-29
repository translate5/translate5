// This is a workaround for a missing scrollbar problem in Firefox
if (Ext.scrollbar._size.width === 0)
    Ext.apply(Ext.scrollbar._size, {
        width: 12,
        height: 12,
        reservedWidth: 'calc(100% - 12px)',
        reservedHeight: 'calc(100% - 12px)'
    });