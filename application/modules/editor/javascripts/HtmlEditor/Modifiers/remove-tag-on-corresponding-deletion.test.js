import removeTagOnCorrespondingDeletion from './remove-tag-on-corresponding-deletion.js';

describe('removeTagOnCorrespondingDeletion', () => {
    let mockTagsConversion;
    let mockRichTextEditor;

    // Helper to create mock functions
    const createMockFn = (returnValue) => {
        const fn = (...args) => {
            fn.calls.push(args);
            if (typeof fn.implementation === 'function') {
                return fn.implementation(...args);
            }
            return returnValue;
        };
        fn.calls = [];
        fn.mockReturnValue = (value) => {
            fn.implementation = () => value;
            return fn;
        };
        fn.mockImplementation = (impl) => {
            fn.implementation = impl;
            return fn;
        };
        return fn;
    };

    beforeEach(() => {
        // Mock tagsConversion object
        mockTagsConversion = {
            isInternalTagNode: createMockFn(false),
            isMQMNode: createMockFn(false),
            isTrackChangesDelNode: createMockFn(false)
        };

        document.body.innerHTML = '';
    });

    afterEach(() => {
        // Reset mock functions
        mockTagsConversion.isInternalTagNode = createMockFn(false);
        mockTagsConversion.isMQMNode = createMockFn(false);
        mockTagsConversion.isTrackChangesDelNode = createMockFn(false);
    });

    test('returns original data when no actions provided', () => {
        const rawData = 'Hello world';
        const actions = [];
        const position = 5;

        const [resultText, resultPosition] = removeTagOnCorrespondingDeletion(
            rawData,
            actions,
            position,
            mockTagsConversion
        );

        expect(resultText).toBe('Hello world');
        expect(resultPosition).toBe(5);
    });

    test('returns original data when actions have no type', () => {
        const rawData = '<img src="test" class="internal-tag">';
        const actions = [{ content: [] }];
        const position = 0;

        const [resultText, resultPosition] = removeTagOnCorrespondingDeletion(
            rawData,
            actions,
            position,
            mockTagsConversion
        );

        expect(resultText).toBe('<img src="test" class="internal-tag">');
        expect(resultPosition).toBe(0);
    });

    test('returns original data when action has no correspondingDeletion', () => {
        const rawData = '<img src="test" class="internal-tag">';
        const actions = [
            {
                type: 'delete',
                content: []
            }
        ];
        const position = 0;

        const [resultText, resultPosition] = removeTagOnCorrespondingDeletion(
            rawData,
            actions,
            position,
            mockTagsConversion
        );

        expect(resultText).toBe('<img src="test" class="internal-tag">');
        expect(resultPosition).toBe(0);
    });

    test('removes matching internal tag from document', () => {
        const imgTag = document.createElement('img');
        imgTag.src = 'tag';
        imgTag.className = 'internal-tag single';
        imgTag.setAttribute('data-tag-number', '1');

        const rawData = `Text <img src="tag" class="internal-tag single" data-tag-number="1"> more text`;

        const mockDeletion = {
            toDom: createMockFn(imgTag.cloneNode(true))
        };

        const actions = [
            {
                type: 'delete',
                correspondingDeletion: true,
                content: [mockDeletion]
            }
        ];

        mockTagsConversion.isInternalTagNode.mockReturnValue(true);
        mockTagsConversion.isTrackChangesDelNode.mockReturnValue(false);

        const [resultText, resultPosition] = removeTagOnCorrespondingDeletion(
            rawData,
            actions,
            0,
            mockTagsConversion
        );

        expect(resultText).not.toContain('data-tag-number="1"');
        expect(mockDeletion.toDom.calls.length).toBeGreaterThan(0);
        expect(mockTagsConversion.isInternalTagNode.calls.length).toBeGreaterThan(0);
    });

    test('removes img tag from parent wrapper when not internal tag node', () => {
        const wrapperDiv = document.createElement('div');
        wrapperDiv.className = 'tag-wrapper';
        const imgTag = document.createElement('img');
        imgTag.src = 'tag';
        imgTag.className = 'tag';
        wrapperDiv.appendChild(imgTag);

        const rawData = `Text <img src="tag.png" class="tag"> more`;

        const mockDeletion = {
            toDom: createMockFn(wrapperDiv.cloneNode(true))
        };

        const actions = [
            {
                type: 'delete',
                correspondingDeletion: true,
                content: [mockDeletion]
            }
        ];

        mockTagsConversion.isInternalTagNode.mockReturnValue(false);
        mockTagsConversion.isMQMNode.mockReturnValue(false);
        mockTagsConversion.isTrackChangesDelNode.mockReturnValue(false);

        const [resultText, resultPosition] = removeTagOnCorrespondingDeletion(
            rawData,
            actions,
            0,
            mockTagsConversion
        );

        expect(mockDeletion.toDom.calls.length).toBeGreaterThan(0);
        expect(mockTagsConversion.isInternalTagNode.calls.length).toBeGreaterThan(0);
        expect(mockTagsConversion.isMQMNode.calls.length).toBeGreaterThan(0);
    });

    test('skips tags inside del track changes nodes', () => {
        const imgTag = document.createElement('img');
        imgTag.src = 'tag';
        imgTag.className = 'internal-tag';

        const rawData = `<del class="trackchanges"><img src="tag" class="internal-tag"></del><img src="tag" class="internal-tag">`;

        const mockDeletion = {
            toDom: createMockFn(imgTag.cloneNode(true))
        };

        const actions = [
            {
                type: 'delete',
                correspondingDeletion: true,
                content: [mockDeletion]
            }
        ];

        mockTagsConversion.isInternalTagNode.mockReturnValue(true);
        mockTagsConversion.isTrackChangesDelNode.mockImplementation((node) => {
            return node && node.tagName === 'DEL' && node.classList.contains('trackchanges');
        });

        const [resultText, resultPosition] = removeTagOnCorrespondingDeletion(
            rawData,
            actions,
            0,
            mockTagsConversion
        );

        // Should still contain the img inside del
        expect(resultText).toContain('<del class="trackchanges"><img src="tag" class="internal-tag"></del>');
        // But the one outside del should be removed
        expect((resultText.match(/internal-tag/g) || []).length).toBe(1);
    });

    test('handles multiple actions with corresponding deletions', () => {
        const img1 = document.createElement('img');
        img1.src = 'tag1';
        img1.className = 'internal-tag';
        img1.setAttribute('data-tag-number', '1');

        const img2 = document.createElement('img');
        img2.src = 'tag2';
        img2.className = 'internal-tag';
        img2.setAttribute('data-tag-number', '2');

        const rawData = `<img src="tag1" class="internal-tag" data-tag-number="1"><img src="tag2" class="internal-tag" data-tag-number="2">`;

        const mockDeletion1 = {
            toDom: createMockFn(img1.cloneNode(true))
        };

        const mockDeletion2 = {
            toDom: createMockFn(img2.cloneNode(true))
        };

        const actions = [
            {
                type: 'delete',
                correspondingDeletion: true,
                content: [mockDeletion1]
            },
            {
                type: 'delete',
                correspondingDeletion: true,
                content: [mockDeletion2]
            }
        ];

        mockTagsConversion.isInternalTagNode.mockReturnValue(true);
        mockTagsConversion.isTrackChangesDelNode.mockReturnValue(false);

        const [resultText, resultPosition] = removeTagOnCorrespondingDeletion(
            rawData,
            actions,
            0,
            mockTagsConversion
        );

        // Both tags should be removed
        expect(resultText).not.toContain('data-tag-number="1"');
        expect(resultText).not.toContain('data-tag-number="2"');
        expect(mockDeletion1.toDom.calls.length).toBeGreaterThan(0);
        expect(mockDeletion2.toDom.calls.length).toBeGreaterThan(0);
    });

    test('handles MQM nodes', () => {
        const mqmSpan = document.createElement('span');
        mqmSpan.className = 'mqm-issue';
        const imgTag = document.createElement('img');
        imgTag.src = 'mqm.png';
        mqmSpan.appendChild(imgTag);

        const rawData = `<p><img src="mqm"></p>`;

        const mockDeletion = {
            toDom: createMockFn(mqmSpan.cloneNode(true))
        };

        const actions = [
            {
                type: 'delete',
                correspondingDeletion: true,
                content: [mockDeletion]
            }
        ];

        mockTagsConversion.isInternalTagNode.mockReturnValue(false);
        mockTagsConversion.isMQMNode.mockReturnValue(true);
        mockTagsConversion.isTrackChangesDelNode.mockReturnValue(false);

        const [resultText, resultPosition] = removeTagOnCorrespondingDeletion(
            rawData,
            actions,
            0,
            mockTagsConversion
        );

        expect(mockDeletion.toDom.calls.length).toBeGreaterThan(0);
        expect(mockTagsConversion.isMQMNode.calls.length).toBeGreaterThan(0);
    });

    test('preserves position parameter through processing', () => {
        const rawData = 'Text';
        const actions = [];
        const position = 42;

        const [resultText, resultPosition] = removeTagOnCorrespondingDeletion(
            rawData,
            actions,
            position,
            mockTagsConversion
        );

        expect(resultPosition).toBe(42);
    });

    test('handles empty document', () => {
        const rawData = '';
        const actions = [];
        const position = 0;

        const [resultText, resultPosition] = removeTagOnCorrespondingDeletion(
            rawData,
            actions,
            position,
            mockTagsConversion
        );

        expect(resultText).toBe('');
        expect(resultPosition).toBe(0);
    });

    test('stops at first matching tag and does not remove duplicates', () => {
        const imgTag = document.createElement('img');
        imgTag.src = 'tag';
        imgTag.className = 'internal-tag';

        // Two identical tags
        const rawData = `<img src="tag" class="internal-tag"><img src="tag" class="internal-tag">`;

        const mockDeletion = {
            toDom: createMockFn(imgTag.cloneNode(true))
        };

        const actions = [
            {
                type: 'delete',
                correspondingDeletion: true,
                content: [mockDeletion]
            }
        ];

        mockTagsConversion.isInternalTagNode.mockReturnValue(true);
        mockTagsConversion.isTrackChangesDelNode.mockReturnValue(false);

        const [resultText, resultPosition] = removeTagOnCorrespondingDeletion(
            rawData,
            actions,
            0,
            mockTagsConversion
        );

        // Should remove only the first matching tag
        const imgCount = (resultText.match(/internal-tag/g) || []).length;
        expect(imgCount).toBe(1);
    });
});

