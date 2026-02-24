import addLineBreaks from './add-line-breaks.js';

describe('addLineBreaks', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
    });

    test('returns original text when no newline images present', () => {
        const text = 'Hello world';
        const actions = [];
        const position = 5;

        const [resultText, resultPosition] = addLineBreaks(text, actions, position);

        expect(resultText).toBe('Hello world');
        expect(resultPosition).toBe(5);
    });

    test('removes all br tags from text', () => {
        const text = 'Hello<br>world<br>test';
        const actions = [];
        const position = 0;

        const [resultText, resultPosition] = addLineBreaks(text, actions, position);

        expect(resultText).not.toContain('<br>');
        expect(resultText).toBe('Helloworldtest');
    });

    test('adds br tag after img with newline class', () => {
        const text = 'Hello<img class="newline">world';
        const actions = [];
        const position = 0;

        const [resultText, resultPosition] = addLineBreaks(text, actions, position);

        expect(resultText).toContain('<img class="newline"><br>');
        expect(resultPosition).toBe(0);
    });

    test('adds br tag after img with multiple classes including newline', () => {
        const text = 'Text<img class="internal-tag single whitespace newline">more';
        const actions = [];
        const position = 3;

        const [resultText, resultPosition] = addLineBreaks(text, actions, position);

        expect(resultText).toContain('<img class="internal-tag single whitespace newline"><br>');
        expect(resultPosition).toBe(3);
    });

    test('does not add br tag after newline img inside del tag', () => {
        const text = 'Text<del><img class="newline"></del>more';
        const actions = [];
        const position = 0;

        const [resultText, resultPosition] = addLineBreaks(text, actions, position);

        // Should not add br inside del
        expect(resultText).toBe('Text<del><img class="newline"></del>more');
        expect(resultPosition).toBe(0);
    });

    test('adds br tag after newline img outside del but not inside del', () => {
        const text = '<img class="newline newline1"><del><img class="newline newline2"></del><img class="newline newline3">';
        const actions = [];
        const position = 5;

        const [resultText, resultPosition] = addLineBreaks(text, actions, position);

        // Count br tags - should be 2 (outside del tags only)
        const brCount = (resultText.match(/<br>/g) || []).length;
        expect(brCount).toBe(2);
        expect(resultPosition).toBe(5);
    });

    test('increments position when single newline img is inserted', () => {
        const text = 'Text';
        const actions = [
            {
                type: 'insert',
                content: '<img class="newline"><br>'
            }
        ];
        const position = 5;

        const [resultText, resultPosition] = addLineBreaks(text, actions, position);

        expect(resultPosition).toBe(6); // position incremented
    });

    test('does not increment position when inserting newline img with extra content', () => {
        const text = 'Text';
        const actions = [
            {
                type: 'insert',
                content: '<img class="newline">extra'
            }
        ];
        const position = 5;

        const [resultText, resultPosition] = addLineBreaks(text, actions, position);

        expect(resultPosition).toBe(5); // position not incremented
    });

    test('does not increment position when multiple actions exist', () => {
        const text = 'Text';
        const actions = [
            {
                type: 'insert',
                content: '<img class="newline">'
            },
            {
                type: 'delete',
                content: ''
            }
        ];
        const position = 5;

        const [resultText, resultPosition] = addLineBreaks(text, actions, position);

        expect(resultPosition).toBe(5); // position not incremented
    });

    test('does not increment position for non-insert action', () => {
        const text = 'Text';
        const actions = [
            {
                type: 'delete',
                content: '<img class="newline">'
            }
        ];
        const position = 5;

        const [resultText, resultPosition] = addLineBreaks(text, actions, position);

        expect(resultPosition).toBe(5); // position not incremented
    });

    test('handles multiple newline images in text', () => {
        const text = '<img class="newline" src="1"><span>text</span><img class="newline" src="2">';
        const actions = [];
        const position = 0;

        const [resultText, resultPosition] = addLineBreaks(text, actions, position);

        const brCount = (resultText.match(/<br>/g) || []).length;
        expect(brCount).toBe(2);
    });

    test('does not add duplicate br tags if br already exists after newline img', () => {
        const text = 'Text<img class="newline" src="test">more';
        const actions = [];
        const position = 0;

        // First call adds br
        const [resultText1, resultPosition1] = addLineBreaks(text, actions, position);
        expect(resultText1).toContain('<img class="newline" src="test"><br>');

        // Second call should not add another br
        const [resultText2, resultPosition2] = addLineBreaks(resultText1, actions, position);
        const brCount = (resultText2.match(/<br>/g) || []).length;
        expect(brCount).toBe(1); // Still only 1 br tag
    });

    test('handles newline img with data attributes', () => {
        const text = '<img class="internal-tag newline" src="test" data-tag="line" data-id="123">';
        const actions = [];
        const position = 2;

        const [resultText, resultPosition] = addLineBreaks(text, actions, position);

        expect(resultText).toContain('<br>');
        expect(resultPosition).toBe(2);
    });

    test('handles nested del tags', () => {
        const text = '<del><span><img class="newline" src="1.png"></span></del>';
        const actions = [];
        const position = 0;

        const [resultText, resultPosition] = addLineBreaks(text, actions, position);

        // Should not add br inside nested del
        expect(resultText).toBe('<del><span><img class="newline" src="1.png"></span></del>');
    });

    test('handles empty text', () => {
        const text = '';
        const actions = [];
        const position = 0;

        const [resultText, resultPosition] = addLineBreaks(text, actions, position);

        expect(resultText).toBe('');
        expect(resultPosition).toBe(0);
    });

    test('preserves other img elements without newline class', () => {
        const text = '<img class="mqm1" src="1"><img class="newline" src="2"><img class="mqm2" src="3">';
        const actions = [];
        const position = 0;

        const [resultText, resultPosition] = addLineBreaks(text, actions, position);

        // Only one br should be added (for newline img)
        const brCount = (resultText.match(/<br>/g) || []).length;
        expect(brCount).toBe(1);
        expect(resultText).toContain('class="mqm1"');
        expect(resultText).toContain('class="mqm2"');
    });

    test('handles whitespace around newline img', () => {
        const text = 'before <img class="newline" src="1"> after';
        const actions = [];
        const position = 1;

        const [resultText, resultPosition] = addLineBreaks(text, actions, position);

        expect(resultText).toContain('before <img class="newline" src="1"><br>&nbsp;after');
        expect(resultPosition).toBe(1);
    });
});
