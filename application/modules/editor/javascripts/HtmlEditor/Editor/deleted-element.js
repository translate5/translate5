// export default class DeletedElement {
//     static TYPE = {
//         INTERNAL_TAG: 'internal-tag',
//         TEXT: 'text',
//         INSERT_TAG: 'insert-tag',
//         DELETE_TAG: 'delete-tag',
//     };
//
//     #data;
//     #attributes;
//     #type;
//     #parent;
//
//     constructor(data, attributes, elementName, parent = null) {
//         this.#data = data;
//         this.#attributes = attributes;
//         this.#type = this.#getType(elementName);
//         this.#parent = parent;
//     }
//
//     get data() {
//         return this.#data;
//     }
//
//     get length() {
//         return this.#data?.length || 1;
//     }
//
//     isInternalTag() {
//         return this.#type === DeletedElement.TYPE.INTERNAL_TAG;
//     }
//
//     isText() {
//         return this.#type === DeletedElement.TYPE.TEXT;
//     }
//
//     isInsertDeleteTag() {
//         return this.#type === DeletedElement.TYPE.INSERT_DELETE_TAG;
//     }
//
//     /**
//      * @param {HTMLElement|Text} child
//      * @param {string[]} skipParentClasses
//      * @returns {HTMLElement|Text}
//      */
//     toDom(child= null, skipParentClasses = []) {
//         let element;
//
//         if (this.#type === DeletedElement.TYPE.TEXT) {
//             element = document.createTextNode('');
//         } else {
//             element = document.createElement(this.#getTag());
//         }
//
//         for (const [key, value] of Object.entries(this.#attributes)) {
//             element.setAttribute(key, value);
//         }
//
//         // Set text content if #data is not null
//         if (this.#data) {
//             element.textContent = this.#data;
//         }
//
//         if (child) {
//             element.appendChild(child);
//         }
//
//         if (this.#parent) {
//             const classlist = this.#parent.attributes.class.split(' ');
//             const shouldSkip = classlist.some(className => skipParentClasses.includes(className));
//
//             if (!shouldSkip) {
//                 return this.#parent.toDom(element);
//             }
//         }
//
//         return element;
//     }
//
//     #getTag() {
//         switch (this.#type) {
//             case DeletedElement.TYPE.INTERNAL_TAG:
//                 return 'img';
//             case DeletedElement.TYPE.INSERT_TAG:
//                 return 'ins';
//             case DeletedElement.TYPE.DELETE_TAG:
//                 return 'del';
//             default:
//                 return null;
//         }
//     }
//
//     #getType(name) {
//         switch (name) {
//             case 'imageInline':
//                 return DeletedElement.TYPE.INTERNAL_TAG;
//             case 'htmlIns':
//                 return DeletedElement.TYPE.INSERT_TAG;
//             case 'htmlDel':
//                 return DeletedElement.TYPE.DELETE_TAG;
//             default:
//                 return DeletedElement.TYPE.TEXT;
//         }
//     }
// }
