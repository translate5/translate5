export default class CallbacksQueue {
    #items = [];
    #defaultAppendPriority = 10;  // Default priority for append
    #defaultPrependPriority = -10; // Default priority for prepend

    constructor() {
    }

    // Add element with priority
    add(callback, priority) {
        const newItem = {callback, priority};
        let contains = false;

        for (let i = 0; i < this.#items.length; i++) {
            if (this.#items[i].priority > newItem.priority) {
                this.#items.splice(i, 0, newItem);
                contains = true;

                break;
            }
        }

        if (!contains) {
            this.#items.push(newItem);
        }
    }

    // Append element at the end based on default append priority
    append(element) {
        this.add(element, this.#defaultAppendPriority);
    }

    // Prepend element at the beginning based on default prepend priority
    prepend(element) {
        this.add(element, this.#defaultPrependPriority);
    }

    /**
     * Iterable interface.
     *
     * @returns {Iterable.<Node>}
     */
    [Symbol.iterator]() {
        let index = 0;

        return {
            // Note: using an arrow function allows `this` to point to the
            // one of `[@@iterator]()` instead of `next()`
            next: () => {
                if (index < this.#items.length) {
                    return {value: this.#items[index++].callback, done: false};
                } else {
                    return {done: true};
                }
            },
        };
    }
}
