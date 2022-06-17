import ClassicEditor from '@ckeditor/ckeditor5-editor-classic/src/classiceditor';
import {Essentials} from "@ckeditor/ckeditor5-essentials";
import {Paragraph} from "@ckeditor/ckeditor5-paragraph";
import SourceEditing from '@ckeditor/ckeditor5-source-editing/src/sourceediting';
import ImageInline from "@ckeditor/ckeditor5-image/src/imageinline";
import {GeneralHtmlSupport} from "@ckeditor/ckeditor5-html-support";

function create(element) {
    return ClassicEditor.create(
        element,
        {
            plugins: [Essentials, Paragraph, GeneralHtmlSupport, SourceEditing, ImageInline],
            toolbar: [ 'sourceEditing'],
            htmlSupport: {
                allow: [
                    {
                        name: /.*/,
                        attributes: true,
                        classes: true,
                        styles: true
                    }
                ],
            },
        }
    );
}

export default create;
