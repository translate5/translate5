export default class Ruler {
    constructor(editorElement) {
        this.rulerElement = null;
        this.createRuler(editorElement);
    }

    /**
     * Creates the hidden "ruler" div that is used to measure text length
     */
    createRuler(editorElement) {
        this.rulerElement = document.createElement('div');
        this.rulerElement.classList.add('textmetrics');
        this.rulerElement.setAttribute('role', 'presentation');
        this.rulerElement.dataset.sticky = true;
        this.rulerElement.style.position = 'absolute';
        this.rulerElement.style.left = '-1000px';
        this.rulerElement.style.top = '-1000px';
        this.rulerElement.style.visibility = 'hidden';

        editorElement.appendChild(this.rulerElement);
    }

    /**
     * Measures the passed internal tag's data evaluating the width of the span
     *
     * @param {String} text
     */
    measureWidth(text) {
        this.rulerElement.innerHTML = text;

        return Math.ceil(this.rulerElement.getBoundingClientRect().width);
    }
}
