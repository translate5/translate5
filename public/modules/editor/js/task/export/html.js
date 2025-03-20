document.addEventListener('DOMContentLoaded', () => {
    const table = new Table(document.getElementById('data-table'));
    TableFilterManager.initialize('data-table');

    // Add filters using the concrete implementations
    TableFilterManager.addFilter(
        new HasCommentsFilter(
            table.getThIndexByClass('thead-field_name--comments'),
            window.exportService.defaultFilters.onlyHasComments
        ));

    TableFilterManager.addFilter(
        new TranslatedStatusFilter(
            table.getThIndexByClass('thead-field_name--editStatus'),
            table.getUniqueColumnValues(table.getThIndexByClass('thead-field_name--editStatus')),
            window.exportService.defaultFilters.translatedStatus
        ));

    TableFilterManager.addFilter(
        new MatchRateFilter(
            table.getThIndexByClass('thead-field_name--matchRate'),
            table.getMinColumnValue(table.getThIndexByClass('thead-field_name--matchRate')),
            table.getMaxColumnValue(table.getThIndexByClass('thead-field_name--matchRate'))
        ));

    TableFilterManager.applyFilters();
});

// Base Filter class
class TableFilter {
    constructor(columnIndex) {
        this.columnIndex = columnIndex;
    }

    evaluate(cellValue) {
        throw new Error('evaluate method must be implemented');
    }

    createFilterElement() {
        throw new Error('createFilterElement method must be implemented');
    }

    getContainer(label) {
        const container = document.createElement('div');
        container.className = 'filter-item';

        if (label) {
            const labelElement = document.createElement('label');
            labelElement.textContent = label;
            container.appendChild(labelElement);
        }

        return container;
    }
}

// Abstract Checkbox Filter
class AbstractCheckboxFilter extends TableFilter {
    constructor(columnIndex, defaultValue = false) {
        super(columnIndex);
        this.checked = defaultValue;
    }

    createFilterElement() {
        const container = this.getContainer(this.getLabel());

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.id = `filter-${this.columnIndex}`;
        checkbox.checked = this.checked;

        checkbox.addEventListener('change', (e) => {
            this.checked = e.target.checked;
            TableFilterManager.applyFilters();
        });

        container.appendChild(checkbox);
        return container;
    }

    // Abstract methods to be implemented by concrete classes
    getLabel() {
        throw new Error('getLabel method must be implemented');
    }
}

// Abstract Range Filter
class AbstractRangeFilter extends TableFilter {
    constructor(columnIndex, min = 0, max = 100) {
        super(columnIndex);
        this.min = min;
        this.max = max;
        this.currentMin = min;
        this.currentMax = max;
    }

    createFilterElement() {
        const container = this.getContainer(this.getLabel());

        const minInput = document.createElement('input');
        minInput.type = 'number';
        minInput.min = this.min;
        minInput.max = this.max;
        minInput.value = this.min;
        minInput.placeholder = this.getMinPlaceholder();

        const maxInput = document.createElement('input');
        maxInput.type = 'number';
        maxInput.min = this.min;
        maxInput.max = this.max;
        maxInput.value = this.max;
        maxInput.placeholder = this.getMaxPlaceholder();

        const delimiter = document.createElement('span');
        delimiter.textContent = '-';
        delimiter.className = 'delimiter';

        const updateFilter = () => {
            this.currentMin = parseFloat(minInput.value) || this.min;
            this.currentMax = parseFloat(maxInput.value) || this.max;
            TableFilterManager.applyFilters();
        };

        minInput.addEventListener('input', updateFilter);
        maxInput.addEventListener('input', updateFilter);

        container.appendChild(minInput);
        container.appendChild(delimiter);
        container.appendChild(maxInput);
        return container;
    }

    // Abstract methods to be implemented by concrete classes
    getLabel() {
        throw new Error('getLabel method must be implemented');
    }

    getMinPlaceholder() {
        throw new Error('getMinPlaceholder method must be implemented');
    }

    getMaxPlaceholder() {
        throw new Error('getMaxPlaceholder method must be implemented');
    }
}

// Abstract Select Filter
class AbstractSelectFilter extends TableFilter {
    constructor(columnIndex, defaultValue = '') {
        super(columnIndex);
        this.selectedValue = defaultValue;
    }

    createFilterElement() {
        const container = this.getContainer(this.getLabel());

        const select = document.createElement('select');
        select.id = `filter-${this.columnIndex}`;

        // Add default "All" option
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = this.getAllOptionLabel();
        select.appendChild(defaultOption);

        // Add other options
        this.getOptions().forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option.value;
            optionElement.textContent = option.label;
            optionElement.selected = option.value === this.selectedValue;
            select.appendChild(optionElement);
        });

        select.addEventListener('change', (e) => {
            this.selectedValue = e.target.value;
            TableFilterManager.applyFilters();
        });

        container.appendChild(select);
        return container;
    }

    // Abstract methods to be implemented by concrete classes
    getLabel() {
        throw new Error('getLabel method must be implemented');
    }

    getAllOptionLabel() {
        throw new Error('getAllOptionLabel method must be implemented');
    }

    getOptions() {
        throw new Error('getOptions method must be implemented');
    }
}

// Example implementations of concrete filters
class HasCommentsFilter extends AbstractCheckboxFilter {
    constructor(columnIndex, defaultValue = false) {
        super(columnIndex, defaultValue);
        this.selectedValue = defaultValue;
    }

    getLabel() {
        return window.exportService.localizedStrings.filtersLabels.onlyHasComments + ': ';
    }

    evaluate(cellValue) {
        return !this.checked || cellValue.trim() !== '';
    }
}

class TranslatedStatusFilter extends AbstractSelectFilter {
    constructor(columnIndex, options, defaultValue = '') {
        super(columnIndex, defaultValue);
        this.options = options;
        this.selectedValue = defaultValue;
    }

    getLabel() {
        return window.exportService.localizedStrings.filtersLabels.segmentProcessingStatus + ': ';
    }

    getAllOptionLabel() {
        return '-- ' + window.exportService.localizedStrings.filtersLabels.alle + ' --';
    }

    getOptions() {
        return this.options.map(function (value) {
            return {value: value, label: value};
        });
    }

    evaluate(cellValue) {
        return !this.selectedValue || cellValue === this.selectedValue;
    }
}

class MatchRateFilter extends AbstractRangeFilter {
    getLabel() {
        return window.exportService.localizedStrings.filtersLabels.matchRate + ': ';
    }

    getMinPlaceholder() {
        return 'Min %';
    }

    getMaxPlaceholder() {
        return 'Max %';
    }

    evaluate(cellValue) {
        const value = parseFloat(cellValue);
        return !isNaN(value) &&
            value >= this.currentMin &&
            value <= this.currentMax;
    }
}

// Filter Manager remains the same
const TableFilterManager = {
    filters: [],
    tableElement: null,

    initialize(tableId) {
        this.tableElement = document.getElementById(tableId);
        this.createFilterContainer();
    },

    addFilter(filter) {
        this.filters.push(filter);
        const filterElement = filter.createFilterElement();
        document.getElementById('filter-container').appendChild(filterElement);
    },

    createFilterContainer() {
        const container = document.createElement('div');
        container.id = 'filter-container';
        container.className = 'filters';
        this.tableElement.parentNode.insertBefore(container, this.tableElement);
    },

    applyFilters() {
        const rows = this.tableElement.getElementsByTagName('tbody')[0].rows;

        for (let i = 0; i < rows.length; i++) {
            let showRow = true;

            for (const filter of this.filters) {
                const cell = rows[i].cells[filter.columnIndex];
                if (!filter.evaluate(cell.textContent)) {
                    showRow = false;
                    break;
                }
            }

            rows[i].style.display = showRow ? '' : 'none';
        }
    }
};

class Table {
    constructor(table) {
        this.table = table;
    }

    getThIndexByClass(className) {
        const headers = this.table.querySelectorAll('thead th');
        return Array.from(headers).findIndex(th => th.classList.contains(className));
    }

    getUniqueColumnValues(columnIndex) {
        const values = new Set();

        // Skip header row by starting from rows[1]
        const rows = this.table.getElementsByTagName('tr');
        for (let i = 1; i < rows.length; i++) {
            const cell = rows[i].cells[columnIndex];
            if (cell) {
                values.add(cell.textContent.trim());
            }
        }

        return Array.from(values);
    }

    getMinColumnValue(columnIndex) {
        const rows = this.table.getElementsByTagName('tr');
        let min = Number(rows[1].cells[columnIndex]
            .textContent
            .trim());

        for (let i = 1; i < rows.length; i++) {
            let value = Number(rows[i].cells[columnIndex]
                .textContent
                .trim());

            if (value < min) {
                min = value;
            }
        }

        return min;
    }

    getMaxColumnValue(columnIndex) {
        const rows = this.table.getElementsByTagName('tr');
        let max = Number(rows[1].cells[columnIndex]
            .textContent
            .trim());

        for (let i = 1; i < rows.length; i++) {
            let value = Number(rows[i].cells[columnIndex]
                .textContent
                .trim());

            if (value > max) {
                max = value;
            }
        }

        return max;
    }
}