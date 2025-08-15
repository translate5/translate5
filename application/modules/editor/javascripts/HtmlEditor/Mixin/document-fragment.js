import escapeHtml from '../Tools/escape-html.js';

export default DocumentFragment = {
    toHTMLString() {
        let html = '',
            fragment = this;

        // Function to process a node and its children recursively
        function processNode(node) {
            if (node.is('element')) {
                // Opening tag
                html += `<${node.name}`;

                // Attributes
                if (node.getAttributes()) {
                    for (const [key, value] of node.getAttributes()) {
                        html += ` ${key}="${escapeHtml(value)}"`;
                    }
                }

                html += '>';

                // Children
                for (const child of node.getChildren()) {
                    processNode(child);
                }

                // Closing tag
                html += `</${node.name}>`;
            } else if (node.is('text')) {
                // Text node
                html += escapeHtml(node.data);
            }
        }

        // Start processing from the root children of the DocumentFragment
        for (const child of fragment.getChildren()) {
            processNode(child);
        }

        return html;
    }
}