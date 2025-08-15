export default class ModelNode {
    static TYPE = {
        INTERNAL_TAG: 'internal-tag',
        TEXT: 'text',
        INSERT_TAG: 'insert-tag',
        DELETE_TAG: 'delete-tag',
        SPAN: 'span',
    };

    #data;
    #attributes;
    #type;
    #parent;

    constructor(data, attributes, elementName, parent = null) {
        this.#data = data;
        this.#attributes = attributes;
        this.#type = this.#getType(elementName);
        this.#parent = parent;
    }

    get data() {
        return this.#data;
    }

    get length() {
        return this.#data?.length || 1;
    }

    isInternalTag() {
        return this.#type === ModelNode.TYPE.INTERNAL_TAG;
    }

    isText() {
        return this.#type === ModelNode.TYPE.TEXT;
    }

    isInsertDeleteTag() {
        return this.#type === ModelNode.TYPE.INSERT_DELETE_TAG;
    }

    /**
     * @param {HTMLElement|Text} child
     * @param {string[]} skipParentClasses
     * @returns {HTMLElement|Text}
     */
    toDom(child = null, skipParentClasses = []) {
        let element;

        if (this.#type === ModelNode.TYPE.TEXT) {
            element = document.createTextNode('');
        } else {
            element = document.createElement(this.#getTag());
        }

        for (const [key, value] of Object.entries(this.#attributes)) {
            try {
                element.setAttribute(key, value);
            } catch (e) {
                console.log('Error setting attribute', key, value, e);
            }
        }

        // Set text content if #data is not null
        if (this.#data) {
            element.textContent = this.#data;
        }

        if (child) {
            element.appendChild(child);
        }

        if (this.#parent) {
            const classlist = this.#parent.#attributes.class.split(' ');
            const shouldSkip = classlist.some(className => skipParentClasses.includes(className));

            if (shouldSkip) {
                return this.#parent.toDomSkipCurrent(element, skipParentClasses);
            }

            return this.#parent.toDom(element, skipParentClasses);
        }

        return element;
    }

    toDomSkipCurrent(child = null, skipParentClasses = []) {
        if (!this.#parent) {
            return child;
        }

        const classlist = this.#parent.#attributes.class.split(' ');
        const shouldSkip = classlist.some(className => skipParentClasses.includes(className));

        if (shouldSkip) {
            return this.#parent.toDomSkipCurrent(child, skipParentClasses);
        }

        return this.#parent.toDom(child, skipParentClasses);
    }

    #getTag() {
        switch (this.#type) {
            case ModelNode.TYPE.INTERNAL_TAG:
                return 'img';
            case ModelNode.TYPE.INSERT_TAG:
                return 'ins';
            case ModelNode.TYPE.DELETE_TAG:
                return 'del';
            case ModelNode.TYPE.SPAN:
                return 'span';
            default:
                return null;
        }
    }

    #getType(name) {
        switch (name) {
            case 'imageInline':
                return ModelNode.TYPE.INTERNAL_TAG;
            case 'htmlIns':
                return ModelNode.TYPE.INSERT_TAG;
            case 'htmlDel':
                return ModelNode.TYPE.DELETE_TAG;
            case 'htmlSpan':
                return ModelNode.TYPE.SPAN;
            default:
                return ModelNode.TYPE.TEXT;
        }
    }
}
