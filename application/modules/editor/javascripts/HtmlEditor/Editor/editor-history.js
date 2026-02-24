/**
 * Manages undo/redo history for the editor
 */
export default class EditorHistory {
    #history = [];
    #historyIndex = -1;
    #maxHistorySize = 100;

    /**
     * Save current editor state to history
     *
     * @param {string} data - The editor content
     * @param {number} cursorPosition - The cursor position
     */
    saveSnapshot(data, cursorPosition) {
        // If we're in the middle of history, remove all forward states
        if (this.#historyIndex < this.#history.length - 1) {
            this.#history = this.#history.slice(0, this.#historyIndex + 1);
        }

        // Add new state
        this.#history.push({
            data: data,
            cursorPosition: cursorPosition
        });

        // Limit history size
        if (this.#history.length > this.#maxHistorySize) {
            this.#history.shift();
        } else {
            this.#historyIndex++;
        }
    }

    /**
     * Get snapshot at current history index
     *
     * @returns {{data: string, cursorPosition: number}|null}
     */
    getCurrentSnapshot() {
        if (this.#historyIndex < 0 || this.#historyIndex >= this.#history.length) {
            return null;
        }

        return this.#history[this.#historyIndex];
    }

    /**
     * Move to previous state in history
     *
     * @returns {boolean} - True if moved successfully
     */
    moveToPrevious() {
        if (this.#historyIndex > 0) {
            this.#historyIndex--;

            return true;
        }

        return false;
    }

    /**
     * Move to next state in history
     *
     * @returns {boolean} - True if moved successfully
     */
    moveToNext() {
        if (this.#historyIndex < this.#history.length - 1) {
            this.#historyIndex++;

            return true;
        }

        return false;
    }

    /**
     * Check if undo is possible
     *
     * @returns {boolean}
     */
    canUndo() {
        return this.#historyIndex > 0;
    }

    /**
     * Check if redo is possible
     *
     * @returns {boolean}
     */
    canRedo() {
        return this.#historyIndex < this.#history.length - 1;
    }

    /**
     * Clear all history
     */
    clear() {
        this.#history = [];
        this.#historyIndex = -1;
    }

    /**
     * Get current history size
     *
     * @returns {number}
     */
    size() {
        return this.#history.length;
    }

    /**
     * Get current history index
     *
     * @returns {number}
     */
    getCurrentIndex() {
        return this.#historyIndex;
    }
}
