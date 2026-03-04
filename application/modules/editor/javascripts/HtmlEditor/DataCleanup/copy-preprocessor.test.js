import {describe, test, expect, beforeEach, afterEach, jest} from '@jest/globals';
import copyPreprocessor from './copy-preprocessor.js';

describe('copyPreprocessor', () => {
    let mockTagsConversion;
    let mockDataTransformer;

    beforeEach(() => {
        // Mock TagsConversion
        mockTagsConversion = {
            isInternalTagNode: jest.fn()
        };

        // Mock DataTransformer
        mockDataTransformer = {
            reverseTransform: jest.fn()
        };
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    describe('basic functionality', () => {
        test('returns original html when no img tags present', () => {
            const html = 'Hello world';

            const result = copyPreprocessor(html, mockTagsConversion, mockDataTransformer);

            expect(result).toBe(html);
            expect(mockTagsConversion.isInternalTagNode).not.toHaveBeenCalled();
            expect(mockDataTransformer.reverseTransform).not.toHaveBeenCalled();
        });

        test('returns original html when img tag is not a whitespace node', () => {
            const html = 'Before<img src="test.jpg" class="regular-image">After';
            mockTagsConversion.isInternalTagNode.mockReturnValue(false);

            const result = copyPreprocessor(html, mockTagsConversion, mockDataTransformer);

            expect(result).toBe(html);
        });

        test('processes whitespace node and replaces it with reversed content', () => {
            const html = 'Before<img class="internal-tag whitespace" data-value="&nbsp;">After';
            mockTagsConversion.isInternalTagNode.mockReturnValue(true);
            mockDataTransformer.reverseTransform.mockReturnValue({
                data: '<div><span class="newline"></span></div>'
            });

            const result = copyPreprocessor(html, mockTagsConversion, mockDataTransformer);
            expect(result).toBe('Before<div><span class="newline"></span></div>After');
        });
    });

    describe('whitespace node processing', () => {
        test('removes whitespace img tag when reverseTransform returns null', () => {
            const html = 'Before<img class="internal-tag whitespace">After';
            mockTagsConversion.isInternalTagNode.mockReturnValue(true);
            mockDataTransformer.reverseTransform.mockReturnValue(null);

            const result = copyPreprocessor(html, mockTagsConversion, mockDataTransformer);

            expect(result).toBe('BeforeAfter');
            expect(result).not.toContain('<img');
        });

        test('removes whitespace img tag when reverseTransform returns undefined data', () => {
            const html = 'Before<img class="internal-tag whitespace"/>After';
            mockTagsConversion.isInternalTagNode.mockReturnValue(true);
            mockDataTransformer.reverseTransform.mockReturnValue({data: undefined});

            const result = copyPreprocessor(html, mockTagsConversion, mockDataTransformer);

            expect(result).not.toContain('<img');
        });

        test('removes whitespace img tag when reverseTransform returns empty string', () => {
            const html = 'Before<img class="internal-tag whitespace"/>After';
            mockTagsConversion.isInternalTagNode.mockReturnValue(true);
            mockDataTransformer.reverseTransform.mockReturnValue({data: ''});

            const result = copyPreprocessor(html, mockTagsConversion, mockDataTransformer);

            expect(result).not.toContain('<img');
        });

        test('passes cloned img element to reverseTransform', () => {
            const html = '<img class="internal-tag whitespace" data-test="value"/>';
            mockTagsConversion.isInternalTagNode.mockReturnValue(true);
            mockDataTransformer.reverseTransform.mockReturnValue({data: '<div><span>test</span></div>'});

            const result = copyPreprocessor(html, mockTagsConversion, mockDataTransformer);

            expect(result).toBe('<div><span>test</span></div>');
        });

        test('multiple whitespaces', () => {
            const html = 'Hello<img class="internal-tag whitespace" data-test="value"/> World<img class="internal-tag whitespace" data-test="value2"/>!';
            mockTagsConversion.isInternalTagNode.mockReturnValue(true);
            mockDataTransformer.reverseTransform
                .mockReturnValueOnce({data: '<div><span>test1</span></div>'})
                .mockReturnValueOnce({data: '<div><span>test2</span></div>'});

            const result = copyPreprocessor(html, mockTagsConversion, mockDataTransformer);

            expect(result).toBe('Hello<div><span>test1</span></div> World<div><span>test2</span></div>!');
        });
    });

    describe('real life cases', () => {
        test('test multiple whitespace nodes partially in tracked changes', () => {
            const html = `Hello<img class="internal-tag single whitespace newline" alt="↵" id="tag-image-whitespace1" title="&lt;1/&gt;: Newline" data-length="1" data-pixellength="0" data-tag-number="1"><br>World<ins class="trackchanges ownttip" data-usertrackingid="1300" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2026-02-25T19:35:42+02:00">Hello<img class="internal-tag single whitespace newline" alt="↵" id="tag-image-whitespace1" title="&lt;1/&gt;: Newline" data-length="1" data-pixellength="0" data-tag-number="1"></ins><br><ins class="trackchanges ownttip" data-usertrackingid="1300" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2026-02-25T19:35:42+02:00">World</ins>`;

            mockTagsConversion.isInternalTagNode.mockReturnValue(true);
            mockDataTransformer.reverseTransform.mockReturnValue({data: '<div><span class="newline"></span></div>'});

            const result = copyPreprocessor(html, mockTagsConversion, mockDataTransformer);

            expect(result).toBe(`Hello<div><span class="newline"></span></div><br>World<ins class="trackchanges ownttip" data-usertrackingid="1300" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2026-02-25T19:35:42+02:00">Hello<div><span class="newline"></span></div></ins><br><ins class="trackchanges ownttip" data-usertrackingid="1300" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2026-02-25T19:35:42+02:00">World</ins>`);
        });
    });
});
