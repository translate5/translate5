/*Ext.define('TMMaintenance.override.dom.Element', {
    override: 'Ext.dom.Element',
    getScrollIntoViewXY: function(container, scrollX, scrollY, align, debug) {
        var me = this,
            dom = me.dom,
            offsets, clientWidth, clientHeight;


        align = align || {};

        if (container.isRegion) {
            clientHeight = container.height;
            clientWidth = container.width;
        }
        else {
            container = Ext.getDom(container);
            clientHeight = container.clientHeight;
            clientWidth = container.clientWidth;
        }

        offsets = me.getOffsetsTo(container);
        if (debug) console.log(
            dom,
            container,
            scrollY,
            offsets[1],
            dom.offsetHeight,
            clientHeight,
            align.y,
            me.calcScrollPos(
                offsets[1] + scrollY,
                dom.offsetHeight,
                clientHeight,
                align.y
            )
        );
        return {
            y: me.calcScrollPos(offsets[1] + scrollY, dom.offsetHeight,
                scrollY, clientHeight, align.y),
            x: me.calcScrollPos(offsets[0] + scrollX, dom.offsetWidth,
                scrollX, clientWidth, align.x)
        };
    },
});
Ext.define('TMMaintenance.override.scroll.Scroller', {
    override: 'Ext.scroll.Scroller',
    privates: {
        getEnsureVisibleXY: function(el, options) {
            var position = this.getPosition(),
                viewport = this.component
                    ? this.component.getScrollableClientRegion()
                    : this.getElement(),
                newPosition, align;

            if (el && el.element && !el.isElement) {
                options = el;
                el = options.element;
            }

            options = options || {};
            align = options.align;

            if (align) {
                if (Ext.isString(align)) {
                    align = {
                        x: options.x === false ? null : align,
                        y: options.y === false ? null : align
                    };
                }
                else if (Ext.isObject(align)) {
                    if (align.x && options.x === false) {
                        align.x = null;
                    }

                    if (align.y && options.y === false) {
                        align.y = null;
                    }
                }
            }

            newPosition = Ext.fly(el).getScrollIntoViewXY(viewport, position.x, position.y, align, 'debug' in options);
            if ('debug' in options) console.log('wasPositionY', position.y, 'newPositionY', newPosition.y);
            newPosition.x = options.x === false ? position.x : newPosition.x;
            newPosition.y = options.y === false ? position.y : newPosition.y;

            return newPosition;
        }
    },
    isInView1: function(el, contains) {
        var me = this,
            c = me.component,
            result = {
                x: false,
                y: false
            },
            myEl = me.getElement(),
            elRegion, myElRegion;
        console.log('isInView');
        if (el && (contains === false || myEl.contains(el) || (c && c.owns(el)))) {
            myElRegion = myEl.getRegion();
            elRegion = Ext.fly(el).getRegion();

            result.x = elRegion.right > myElRegion.left && elRegion.left < myElRegion.right;
            result.y = elRegion.bottom > myElRegion.top && elRegion.top < myElRegion.bottom;
        }

        return result;
    },
});*/
Ext.define('TMMaintenance.override.dataview.NavigationModel', {
    override: 'Ext.dataview.NavigationModel',
    setLocation: function(location, options) {
        var me = this,
            view = me.getView(),
            oldLocation = me.location,
            animation = options && options.animation,
            scroller, child, record, itemContainer, childFloatStyle, locationView;

        if (location == null) {
            console.log('was no location');
            return me.clearLocation();
        }

        if (!location.isDataViewLocation) {
            location = this.createLocation(location);
        }

        locationView = location.view;

        // If it's a valid location, focus it.
        // Handling the consquences will happen in the onFocusMove
        // listener unless the synchronous options is passed.
        if (!location.equals(oldLocation)) {
            record = location.record;
            child = location.child;

            // If the record is not rendered, ask to scroll to it and try again
            if (record && !child) {
                // TODO: column?
                return locationView.ensureVisible(record, {
                    animation: animation,
                }).then(function() {
                    if (!me.destroyed) {
                        locationView.getNavigationModel().setLocation({
                            record: record,
                            column: location.column
                        }, options);
                    }
                });
            }

            // Work out if they are using any of the ways to get the items
            // to flow inline. In which case, moving up requires extra work.
            if (child && me.floatingItems == null) {
                child = child.isComponent ? child.el : Ext.fly(child);
                itemContainer = child.up();
                childFloatStyle = child.getStyleValue('float');

                me.floatingItems =
                    (view.getInline && view.getInline()) ||
                    child.isStyle('display', 'inline-block') ||
                    childFloatStyle === 'left' || childFloatStyle === 'right' ||
                    (itemContainer.isStyle('display', 'flex') &&
                        itemContainer.isStyle('flex-direction', 'row'));
            }

            // Use explicit scrolling rather than relying on the browser's focus behaviour.
            // Scroll on focus overscrolls. ensureVisible scrolls exactly correctly.
            scroller = locationView.getScrollable();

            if (scroller) {
                scroller.ensureVisible(location.sourceElement, {
                    animation: options && options.animation,
                    align: 'bottom?'                                                       // +
                });
            }

            // Handling the impending focus event is separated because it also needs to
            // happen in case of a focus move caused by assistive technologies.
            me.handleLocationChange(location, options);

            // Event handlers may have destroyed the view (and this)
            if (!me.destroyed) {
                me.doFocus();
            }
        }
    }
});