/**
 * English translation
 */

Ext.Date.monthNames = [
    "January", "February", "March", "April", "May", "June",
    "July", "August", "September", "October", "November", "December"
];

Ext.Date.defaultFormat = 'm/d/Y';
Ext.Date.defaultTimeFormat = 'h:i A';

Ext.Date.getShortMonthName = function(month) {
    return Ext.Date.monthNames[month].substring(0, 3);
};

Ext.Date.monthNumbers = {
    January: 0,
    Jan: 0,
    February: 1,
    Feb: 1,
    March: 2,
    Mar: 2,
    April: 3,
    Apr: 3,
    May: 4,
    June: 5,
    Jun: 5,
    July: 6,
    Jul: 6,
    August: 7,
    Aug: 7,
    September: 8,
    Sep: 8,
    October: 9,
    Oct: 9,
    November: 10,
    Nov: 10,
    December: 11,
    Dec: 11
};

Ext.Date.getMonthNumber = function(name) {
    return Ext.Date.monthNumbers[name.substring(0, 1).toUpperCase() + name.substring(1, 3)
        .toLowerCase()];
};

Ext.Date.dayNames = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

Ext.Date.getShortDayName = function(day) {
    return Ext.Date.dayNames[day].substring(0, 3);
};
Ext.util.Format.__number = Ext.util.Format.number;

Ext.util.Format.number = function(v, format) {
    return Ext.util.Format.__number(v, format || "0,000.00/i");
};

Ext.apply(Ext.util.Format, {
    thousandSeparator: ',',
    decimalSeparator: '.',
    currencySign: '$',
    dateFormat: 'm/d/Y'
});

Ext.define('Ext.locale.en.Panel', {
    override: 'Ext.Panel',

    config: {
        standardButtons: {
            ok: {
                text: 'OK'
            },
            abort: {
                text: 'Abort'
            },
            retry: {
                text: 'Retry'
            },
            ignore: {
                text: 'Ignore'
            },
            yes: {
                text: 'Yes'
            },
            no: {
                text: 'No'
            },
            cancel: {
                text: 'Cancel'
            },
            apply: {
                text: 'Apply'
            },
            save: {
                text: 'Save'
            },
            submit: {
                text: 'Submit'
            },
            help: {
                text: 'Help'
            },
            close: {
                text: 'Close'
            }
        },
        closeToolText: 'Close panel'
    }
});

Ext.define('Ext.locale.en.picker.Date', {
    override: 'Ext.picker.Date',

    config: {
        doneButton: 'Done',
        monthText: 'Month',
        dayText: 'Day',
        yearText: 'Year'
    }
});

Ext.define('Ext.locale.en.picker.Picker', {
    override: 'Ext.picker.Picker',

    config: {
        doneButton: 'Done',
        cancelButton: 'Cancel'
    }
});

Ext.define('Ext.locale.en.panel.Date', {
    override: 'Ext.panel.Date',

    config: {
        nextText: 'Next Month (Control+Right)',
        prevText: 'Previous Month (Control+Left)',
        buttons: {
            footerTodayButton: {
                text: "Today"
            }
        }
    }
});

Ext.define('Ext.locale.en.panel.Collapser', {
    override: 'Ext.panel.Collapser',

    config: {
        collapseToolText: "Collapse panel",
        expandToolText: "Expand panel"
    }
});

Ext.define('Ext.locale.en.field.Field', {
    override: 'Ext.field.Field',

    config: {
        requiredMessage: 'This field is required',
        validationMessage: 'Is in the wrong format'
    }
});

Ext.define('Ext.locale.en.field.Number', {
    override: 'Ext.field.Number',

    decimalsText: 'The maximum decimal places is {0}',
    minValueText: 'The minimum value for this field is {0}',
    maxValueText: 'The maximum value for this field is {0}',
    badFormatMessage: 'Value is not a valid number'
});

Ext.define('Ext.locale.en.field.Text', {
    override: 'Ext.field.Text',

    badFormatMessage: 'Value does not match the required format',
    config: {
        requiredMessage: 'This field is required',
        validationMessage: 'Is in the wrong format'
    }
});

Ext.define('Ext.locale.en.Dialog', {
    override: 'Ext.Dialog',

    config: {
        maximizeTool: {
            tooltip: "Maximize to fullscreen"
        },
        restoreTool: {
            tooltip: "Restore to original size"
        }
    }
});

Ext.define("Ext.locale.en.field.FileButton", {
    override: "Ext.field.FileButton",

    config: {
        text: 'Browse...'
    }
});

Ext.define('Ext.locale.en.dataview.List', {
    override: 'Ext.dataview.List',

    config: {
        loadingText: 'Loading...'
    }
});

Ext.define('Ext.locale.en.dataview.EmptyText', {
    override: 'Ext.dataview.EmptyText',

    config: {
        html: 'No data to display'
    }
});

Ext.define('Ext.locale.en.dataview.Abstract', {
    override: 'Ext.dataview.Abstract',

    config: {
        loadingText: 'Loading...'
    }
});

Ext.define("Ext.locale.en.LoadMask", {
    override: "Ext.LoadMask",

    config: {
        message: 'Loading...'
    }
});

Ext.define('Ext.locale.en.dataview.plugin.ListPaging', {
    override: 'Ext.dataview.plugin.ListPaging',

    config: {
        loadMoreText: 'Load More...',
        noMoreRecordsText: 'No More Records'
    }
});

Ext.define("Ext.locale.en.dataview.DataView", {
    override: "Ext.dataview.DataView",

    config: {
        emptyText: ""
    }
});

Ext.define('Ext.locale.en.field.Date', {
    override: 'Ext.field.Date',

    minDateMessage: 'The date in this field must be equal to or after {0}',
    maxDateMessage: 'The date in this field must be equal to or before {0}'
});

Ext.define("Ext.locale.en.grid.menu.SortAsc", {
    override: "Ext.grid.menu.SortAsc",

    config: {
        text: "Sort Ascending"
    }
});

Ext.define("Ext.locale.en.grid.menu.SortDesc", {
    override: "Ext.grid.menu.SortDesc",

    config: {
        text: "Sort Descending"
    }
});

Ext.define("Ext.locale.en.grid.menu.GroupByThis", {
    override: "Ext.grid.menu.GroupByThis",

    config: {
        text: "Nach diesem Feld gruppieren"
    }
});

Ext.define("Ext.locale.en.grid.menu.ShowInGroups", {
    override: "Ext.grid.menu.ShowInGroups",

    config: {
        text: "Show in groups"
    }
});

Ext.define("Ext.locale.en.grid.menu.Columns", {
    override: "Ext.grid.menu.Columns",

    config: {
        text: "Columns"
    }
});

Ext.define('Ext.locale.en.data.validator.Presence', {
    override: 'Ext.data.validator.Presence',

    config: {
        message: 'Must be present'
    }
});

Ext.define('Ext.locale.en.data.validator.Format', {
    override: 'Ext.data.validator.Format',

    config: {
        message: 'Is in the wrong format'
    }
});

Ext.define('Ext.locale.en.data.validator.Email', {
    override: 'Ext.data.validator.Email',

    config: {
        message: 'Is not a valid email address'
    }
});

Ext.define('Ext.locale.en.data.validator.Phone', {
    override: 'Ext.data.validator.Phone',

    config: {
        message: 'Is not a valid phone number'
    }
});

Ext.define('Ext.locale.en.data.validator.Number', {
    override: 'Ext.data.validator.Number',

    config: {
        message: 'Is not a valid number'
    }
});

Ext.define('Ext.locale.en.data.validator.Url', {
    override: 'Ext.data.validator.Url',

    config: {
        message: 'Is not a valid URL'
    }
});

Ext.define('Ext.locale.en.data.validator.Range', {
    override: 'Ext.data.validator.Range',

    config: {
        nanMessage: 'Must be numeric',
        minOnlyMessage: 'Must be at least {0}',
        maxOnlyMessage: 'Must be no more than than {0}',
        bothMessage: 'Must be between {0} and {1}'
    }
});

Ext.define('Ext.locale.en.data.validator.Bound', {
    override: 'Ext.data.validator.Bound',

    config: {
        emptyMessage: 'Must be present',
        minOnlyMessage: 'Value must be greater than {0}',
        maxOnlyMessage: 'Value must be less than {0}',
        bothMessage: 'Value must be between {0} and {1}'
    }
});

Ext.define('Ext.locale.en.data.validator.CIDRv4', {
    override: 'Ext.data.validator.CIDRv4',

    config: {
        message: 'Is not a valid CIDR block'
    }
});

Ext.define('Ext.locale.en.data.validator.CIDRv6', {
    override: 'Ext.data.validator.CIDRv6',

    config: {
        message: 'Is not a valid CIDR block'
    }
});

Ext.define('Ext.locale.en.data.validator.Currency', {
    override: 'Ext.data.validator.Currency',

    config: {
        message: 'Is not a valid currency amount'
    }

});

Ext.define('Ext.locale.en.data.validator.DateTime', {
    override: 'Ext.data.validator.DateTime',

    config: {
        message: 'Is not a valid date and time'
    }
});

Ext.define('Ext.locale.en.data.validator.Exclusion', {
    override: 'Ext.data.validator.Exclusion',

    config: {
        message: 'Is a value that has been excluded'
    }
});

Ext.define('Ext.locale.en.data.validator.IPAddress', {
    override: 'Ext.data.validator.IPAddress',

    config: {
        message: 'Is not a valid IP address'
    }
});

Ext.define('Ext.locale.en.data.validator.Inclusion', {
    override: 'Ext.data.validator.Inclusion',

    config: {
        message: 'Is not in the list of acceptable values'
    }
});

Ext.define('Ext.locale.en.data.validator.Time', {
    override: 'Ext.data.validator.Time',

    config: {
        message: 'Is not a valid time'
    }
});

Ext.define('Ext.locale.en.data.validator.Date', {
    override: 'Ext.data.validator.Date',

    config: {
        message: "Is not a valid date"
    }
});

Ext.define('Ext.locale.en.data.validator.Length', {
    override: 'Ext.data.validator.Length',

    config: {
        minOnlyMessage: 'Length must be at least {0}',
        maxOnlyMessage: 'Length must be no more than {0}',
        bothMessage: 'Length must be between {0} and {1}'
    }
});

Ext.define('Ext.locale.en.ux.colorpick.Selector', {
    override: 'Ext.ux.colorpick.Selector',

    okButtonText: 'OK',
    cancelButtonText: 'Cancel'
});

// This is needed until we can refactor all of the locales into individual files
Ext.define("Ext.locale.en.Component", {
    override: "Ext.Component"
});

Ext.define("Ext.locale.en.grid.filters.menu.Base", {
    override: "Ext.grid.filters.menu.Base",

    config: {
        text: "Filter"
    }
});

Ext.define('Ext.locale.en.grid.locked.Grid', {
    override: 'Ext.grid.locked.Grid',

    config: {
        columnMenu: {
            items: {
                region: {
                    text: 'Locked'
                }
            }
        },
        regions: {
            left: {
                menuLabel: 'Locked (Left)'
            },
            center: {
                menuLabel: 'Unlocked'
            },
            right: {
                menuLabel: 'Locked (Right)'
            }
        }
    }
});

Ext.define("Ext.locale.en.grid.plugin.RowDragDrop", {
    override: "Ext.grid.plugin.RowDragDrop",
    dragText: "{0} selected row{1}"
});
