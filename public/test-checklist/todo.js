function createTodoItem(item, isSubtask = false) {
    const itemContainer = document.createElement('div');
    itemContainer.className = `list-group-item ${isSubtask ? 'ms-3' : ''}`;

    const itemElement = document.createElement('div');
    itemElement.style.display = 'flex';
    itemElement.style.alignItems = 'center';

    // const checkbox = document.createElement('input');
    // checkbox.type = 'checkbox';
    // checkbox.className = 'form-check-input me-1';
    // checkbox.onclick = function(event) {
    //     event.stopPropagation();
    // };
    // checkbox.onchange = function() {
    //     handleCheckboxChange(item, checkbox.checked, checkbox);
    // };

    const checkboxContainer = document.createElement('div');
    checkboxContainer.className = 'checkbox-group';

    const failureCheckbox = document.createElement('input');
    failureCheckbox.type = 'checkbox';
    failureCheckbox.className = 'form-check-input me-2 failure';
    failureCheckbox.onclick = function(event) {
        event.stopPropagation();
    };

    const successCheckbox = document.createElement('input');
    successCheckbox.type = 'checkbox';
    successCheckbox.className = 'form-check-input me-2';
    successCheckbox.onclick = function(event) {
        event.stopPropagation();
    };

    successCheckbox.onchange = function() {
        handleCheckboxChange(item, successCheckbox.checked, successCheckbox, failureCheckbox ?? null);
    };
    failureCheckbox.onchange = function() {
        handleCheckboxChange(item, failureCheckbox.checked, failureCheckbox, successCheckbox);
    };

    checkboxContainer.appendChild(successCheckbox);

    const textContainer = document.createElement('div');
    textContainer.style.flexGrow = '1';
    textContainer.style.paddingLeft = '15px';

    const title = document.createElement('p');
    title.className = 'mb-1';
    title.textContent = item.check;
    textContainer.style.fontSize = '1.2rem';

    textContainer.appendChild(title);

    if (item.how_to_check) {
        const description = document.createElement('p');
        description.className = 'mb-1 small';
        description.textContent = item.how_to_check;
        description.style.fontSize = '1rem';
        description.style.color = '#808184';

        textContainer.appendChild(description);
    }

    // itemElement.appendChild(checkbox);
    itemElement.appendChild(checkboxContainer);
    itemElement.appendChild(textContainer);

    itemContainer.appendChild(itemElement);

    if (item.subchecks && item.subchecks.length > 0) {
        const expandIcon = document.createElement('span');
        expandIcon.className = 'ms-2';
        expandIcon.innerHTML = item.subchecks.length > 0 ? '&#9660;' : '';

        itemElement.appendChild(expandIcon);

        const subList = document.createElement('div');
        subList.className = 'list-group hidden';

        item.subchecks.forEach(subItem => {
            const subItemElement = createTodoItem(subItem, true);
            subList.appendChild(subItemElement);
        });

        itemContainer.appendChild(subList);

        itemElement.onclick = () => {
            subList.classList.toggle('hidden');
            expandIcon.innerHTML = subList.classList.contains('hidden') ? '&#9650;' : '&#9660;';
        };
    } else {
        checkboxContainer.appendChild(failureCheckbox);
    }

    return itemContainer;
}

function handleCheckboxChange(item, isChecked, changedCheckbox, otherCheckbox) {
    if (changedCheckbox.checked && otherCheckbox) {
        otherCheckbox.checked = false;
    }

    if (! isChecked || ! item.subchecks) {
        return;
    }

    if (item.subchecks.length > 0) {
        // Find the container of the sub-task list
        const subList = changedCheckbox.parentNode.parentNode.parentNode.querySelector('.list-group');
        // Check if all sub-task checkboxes are checked
        const allChecked = Array
            .from(subList.querySelectorAll('.checkbox-group'))
            .every(checkboxGroup => Array
                .from(checkboxGroup.querySelectorAll('input[type="checkbox"]'))
                .some(subCheckbox => subCheckbox.checked)
            );

        if (! allChecked) {
            alert('Complete all subtasks first!');
            changedCheckbox.checked = false;

            return;
        }

        const hasFails = 0 !== subList.querySelectorAll('.failure:checked').length;

        if (hasFails) {
            changedCheckbox.classList.add('failure');

            return;
        }

        changedCheckbox.classList.remove('failure');
    }
}

function renderTodoList(todos) {
    const todoList = document.getElementById('todoList');
    todos.forEach(todo => {
        const todoElement = createTodoItem(todo);
        todoList.appendChild(todoElement);
    });
}

renderTodoList(todos);

function downloadTodoList() {
    const notPrintable = document.querySelectorAll('.not-printable'); // Adjust selector if needed
    notPrintable.forEach(element => element.style.visibility = 'hidden'); // Hide the button

    window.print(); // Trigger the print dialog

    setTimeout(function() {
        notPrintable.forEach(element => element.style.visibility = 'visible');
    }, 500);
}