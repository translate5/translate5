/**
 * German translation
 */
Ext.Date.monthNames = [
    "Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September",
    "Oktober", "November", "Dezember"
];

Ext.Date.defaultFormat = 'd.m.Y';
Ext.Date.defaultTimeFormat = 'H:i';

Ext.Date.getShortMonthName = function(month) {
    return Ext.Date.monthNames[month].substring(0, 3);
};

Ext.Date.monthNumbers = {
    Jan: 0,
    Feb: 1,
    "M\u00e4r": 2,
    Apr: 3,
    Mai: 4,
    Jun: 5,
    Jul: 6,
    Aug: 7,
    Sep: 8,
    Okt: 9,
    Nov: 10,
    Dez: 11
};

Ext.Date.getMonthNumber = function(name) {
    return Ext.Date.monthNumbers[name.substring(0, 1).toUpperCase() + name.substring(1, 3)
        .toLowerCase()];
};

Ext.Date.dayNames = [
    "Sonntag", "Montag", "Dienstag", "Mittwoch",
    "Donnerstag", "Freitag", "Samstag"
];

Ext.Date.getShortDayName = function(day) {
    return Ext.Date.dayNames[day].substring(0, 3);
};
Ext.util.Format.__number = Ext.util.Format.number;

Ext.util.Format.number = function(v, format) {
    return Ext.util.Format.__number(v, format || "0.000,00/i");
};

Ext.apply(Ext.util.Format, {
    thousandSeparator: '.',
    decimalSeparator: ',',
    currencySign: '\u20ac',
    // German Euro
    dateFormat: 'd.m.Y'
});


Ext.define('Ext.locale.de.Panel', {
    override: 'Ext.Panel',

    config: {
        standardButtons: {
            ok: {
                text: 'OK'
            },
            abort: {
                text: 'Abbrechen'
            },
            retry: {
                text: 'Wiederholen'
            },
            ignore: {
                text: 'Ignorieren'
            },
            yes: {
                text: 'Ja'
            },
            no: {
                text: 'Nein'
            },
            cancel: {
                text: 'Abbrechen'
            },
            apply: {
                text: 'Anwenden'
            },
            save: {
                text: 'Speichern'
            },
            submit: {
                text: 'Absenden'
            },
            help: {
                text: 'Hilfe'
            },
            close: {
                text: 'Schließen'
            }
        },
        closeToolText: 'Panel schließen'
    }
});

Ext.define('Ext.locale.de.picker.Date', {
    override: 'Ext.picker.Date',

    config: {
        doneButton: 'Fertig',
        monthText: 'Monat',
        dayText: 'Tag',
        yearText: 'Jahr'
    }
});

Ext.define('Ext.locale.de.picker.Picker', {
    override: 'Ext.picker.Picker',

    config: {
        doneButton: 'Erledigt',
        cancelButton: 'Abbrechen'
    }
});

Ext.define('Ext.locale.de.panel.Date', {
    override: 'Ext.panel.Date',

    config: {
        nextText: 'Nächster Monat (Strg/Control + Rechts)',
        prevText: 'Vorheriger Monat (Strg/Control + Links)',
        buttons: {
            footerTodayButton: {
                text: "Heute"
            }
        }
    }
});

Ext.define('Ext.locale.en.panel.Collapser', {
    override: 'Ext.panel.Collapser',

    config: {
        collapseToolText: "Panel reduzieren",
        expandToolText: "Panel erweitern"
    }
});

Ext.define('Ext.locale.de.field.Field', {
    override: 'Ext.field.Field',

    config: {
        requiredMessage: 'Dieses Feld darf nich leer sein',
        validationMessage: 'Ist im falschen Format'
    }
});

Ext.define('Ext.locale.de.field.Number', {
    override: 'Ext.field.Number',

    decimalsText: 'Die maximale Dezimalstelle ist {0}',
    minValueText: 'Der Mindestwert für dieses Feld ist {0}',
    maxValueText: 'Der Maximalwert für dieses Feld ist {0}',
    badFormatMessage: 'Der Wert ist keine Zahl'
});

Ext.define('Ext.locale.de.field.Text', {
    override: 'Ext.field.Text',

    badFormatMessage: 'Der Wert stimmt nicht mit dem erforderlichen Format überein',
    config: {
        requiredMessage: 'Dieses Feld darf nich leer sein',
        validationMessage: 'Ist im falschen Format'
    }
});

Ext.define('Ext.locale.de.Dialog', {
    override: 'Ext.Dialog',

    config: {
        maximizeTool: {
            tooltip: "Maximiere auf Vollbild"
        },
        restoreTool: {
            tooltip: "Wiederherstellen auf Originalgröße"
        }
    }
});

Ext.define("Ext.locale.de.field.FileButton", {
    override: "Ext.field.FileButton",

    config: {
        text: 'Durchsuche...'
    }
});

Ext.define('Ext.locale.de.dataview.List', {
    override: 'Ext.dataview.List',

    config: {
        loadingText: 'Lade Daten ...'
    }
});

Ext.define('Ext.locale.de.dataview.EmptyText', {
    override: 'Ext.dataview.EmptyText',

    config: {
        html: 'Keine Daten zum Anzeigen'
    }
});

Ext.define('Ext.locale.de.dataview.Abstract', {
    override: 'Ext.dataview.Abstract',

    config: {
        loadingText: 'Lade Daten ...'
    }
});

Ext.define("Ext.locale.de.LoadMask", {
    override: "Ext.LoadMask",

    config: {
        message: 'Lade Daten ...'
    }
});

Ext.define('Ext.locale.de.dataview.plugin.ListPaging', {
    override: 'Ext.dataview.plugin.ListPaging',

    config: {
        loadMoreText: 'Mehr laden...',
        noMoreRecordsText: 'Keine weiteren Aufzeichnungen'
    }
});

Ext.define("Ext.locale.de.dataview.DataView", {
    override: "Ext.dataview.DataView",

    config: {
        emptyText: ""
    }
});

Ext.define('Ext.locale.de.field.Date', {
    override: 'Ext.field.Date',

    minDateMessage: 'Das Datum in diesem Feld muss nach dem {0} liegen',
    maxDateMessage: 'Das Datum in diesem Feld muss vor dem {0} liegen'
});

Ext.define("Ext.locale.de.grid.menu.SortAsc", {
    override: "Ext.grid.menu.SortAsc",

    config: {
        text: "Aufsteigend sortieren"
    }
});

Ext.define("Ext.locale.de.grid.menu.SortDesc", {
    override: "Ext.grid.menu.SortDesc",

    config: {
        text: "Absteigend sortieren"
    }
});

Ext.define("Ext.locale.de.grid.menu.GroupByThis", {
    override: "Ext.grid.menu.GroupByThis",

    config: {
        text: "Nach diesem Feld gruppieren"
    }
});

Ext.define("Ext.locale.de.grid.menu.ShowInGroups", {
    override: "Ext.grid.menu.ShowInGroups",

    config: {
        text: "In Gruppen anzeigen"
    }
});

Ext.define("Ext.locale.de.grid.menu.Columns", {
    override: "Ext.grid.menu.Columns",

    config: {
        text: "Spalten"
    }
});

Ext.define('Ext.locale.de.data.validator.Presence', {
    override: 'Ext.data.validator.Presence',

    config: {
        message: 'Muss anwesend sein'
    }
});

Ext.define('Ext.locale.de.data.validator.Format', {
    override: 'Ext.data.validator.Format',

    config: {
        message: 'Ist im falschen Format'
    }
});

Ext.define('Ext.locale.de.data.validator.Email', {
    override: 'Ext.data.validator.Email',

    config: {
        message: 'Ist keine gültige E-Mail-Adresse'
    }
});

Ext.define('Ext.locale.de.data.validator.Phone', {
    override: 'Ext.data.validator.Phone',

    config: {
        message: 'Ist keine gültige Telefonnummer'
    }
});

Ext.define('Ext.locale.de.data.validator.Number', {
    override: 'Ext.data.validator.Number',

    config: {
        message: 'Ist keine Zahl'
    }
});

Ext.define('Ext.locale.de.data.validator.Url', {
    override: 'Ext.data.validator.Url',

    config: {
        message: 'Ist keine gültige URL'
    }
});

Ext.define('Ext.locale.de.data.validator.Range', {
    override: 'Ext.data.validator.Range',

    config: {
        nanMessage: 'Muss numerisch sein',
        minOnlyMessage: 'Der Mindestwert für dieses Feld ist {0}',
        maxOnlyMessage: 'Der Maximalwert für dieses Feld ist {0}',
        bothMessage: 'Muss zwischen {0} und {1} liegen'
    }
});

Ext.define('Ext.locale.de.data.validator.Bound', {
    override: 'Ext.data.validator.Bound',

    config: {
        emptyMessage: 'Muss anwesend sein',
        minOnlyMessage: 'Wert muss größer als {0} sein',
        maxOnlyMessage: 'Wert muss kleiner als {0} sein',
        bothMessage: 'Der Wert muss zwischen {0} und {1} liegen.'
    }
});

Ext.define('Ext.locale.de.data.validator.CIDRv4', {
    override: 'Ext.data.validator.CIDRv4',

    config: {
        message: 'Ist kein gültiger CIDR-Block'
    }
});

Ext.define('Ext.locale.de.data.validator.CIDRv6', {
    override: 'Ext.data.validator.CIDRv6',

    config: {
        message: 'Ist kein gültiger CIDR-Block'
    }
});

Ext.define('Ext.locale.de.data.validator.Currency', {
    override: 'Ext.data.validator.Currency',

    config: {
        message: 'Ist kein gültiger Währungsbetrag'
    }

});

Ext.define('Ext.locale.de.data.validator.DateTime', {
    override: 'Ext.data.validator.DateTime',

    config: {
        message: 'Ist kein gültige Datum und Uhrzeit'
    }
});

Ext.define('Ext.locale.de.data.validator.Exclusion', {
    override: 'Ext.data.validator.Exclusion',

    config: {
        message: 'Ist ein Wert, der ausgeschlossen wurde'
    }
});

Ext.define('Ext.locale.de.data.validator.IPAddress', {
    override: 'Ext.data.validator.IPAddress',

    config: {
        message: 'Ist keine gültige IP-Adresse'
    }
});

Ext.define('Ext.locale.de.data.validator.Inclusion', {
    override: 'Ext.data.validator.Inclusion',

    config: {
        message: 'Ist nicht in der Liste der zulässigen Werte enthalten'
    }
});

Ext.define('Ext.locale.de.data.validator.Time', {
    override: 'Ext.data.validator.Time',

    config: {
        message: 'Ist keine gültige Zeit'
    }
});

Ext.define('Ext.locale.de.data.validator.Date', {
    override: 'Ext.data.validator.Date',

    config: {
        message: "Ist kein valides Datum"
    }
});

Ext.define('Ext.locale.de.data.validator.Length', {
    override: 'Ext.data.validator.Length',

    config: {
        minOnlyMessage: 'Die Länge muss mindestens {0} sein',
        maxOnlyMessage: 'Die Länge darf nicht mehr als {0} sein',
        bothMessage: 'Die Länge muss zwischen {0} und {1} liegen'
    }
});

Ext.define('Ext.locale.de.ux.colorpick.Selector', {
    override: 'Ext.ux.colorpick.Selector',

    okButtonText: 'OK',
    cancelButtonText: 'Stornieren'
});

// This is needed until we can refactor all of the locales into individual files
Ext.define("Ext.locale.de.Component", {
    override: "Ext.Component"
});

Ext.define("Ext.locale.de.grid.filters.menu.Base", {
    override: "Ext.grid.filters.menu.Base",

    config: {
        text: "Filter"
    }
});

Ext.define('Ext.locale.de.grid.locked.Grid', {
    override: 'Ext.grid.locked.Grid',

    config: {
        columnMenu: {
            items: {
                region: {
                    text: 'Region'
                }
            }
        },
        regions: {
            left: {
                menuLabel: 'Verschlossen (Links)'
            },
            center: {
                menuLabel: 'Freigeschaltet'
            },
            right: {
                menuLabel: 'Verschlossen (Recht)'
            }
        }
    }
});

Ext.define("Ext.locale.de.grid.plugin.RowDragDrop", {
    override: "Ext.grid.plugin.RowDragDrop",
    dragText: "{0} Zeile(n) ausgewählt"
});
