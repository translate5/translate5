import preserveOriginalTextIfNoModifications from './preserve-original-text-if-no-modifications.js';

describe('preserveOriginalTextIfNoModifications', () => {
    test('returns original text and position when text is not empty', () => {
        const text = 'Some content';
        const actions = [{ type: 'insert', content: ' inserted ' }];
        const position = 5;

        const [resultText, resultPosition] = preserveOriginalTextIfNoModifications(text, actions, position);

        expect(resultText).toBe('Some content');
        expect(resultPosition).toBe(5);
    });

    test('returns insertion content and Infinity position when text is empty and insertion exists', () => {
        const text = '';
        const actions = [{ type: 'insert', content: ' inserted content ' }];
        const position = 0;

        const [resultText, resultPosition] = preserveOriginalTextIfNoModifications(text, actions, position);

        expect(resultText).toBe(' inserted content ');
        expect(resultPosition).toBe(Infinity);
    });

    test('returns empty text and original position when text is empty but no insertion action', () => {
        const text = '';
        const actions = [{ type: 'delete', content: '' }];
        const position = 0;

        const [resultText, resultPosition] = preserveOriginalTextIfNoModifications(text, actions, position);

        expect(resultText).toBe('');
        expect(resultPosition).toBe(0);
    });

    test('returns empty text and original position when text is empty and actions array is empty', () => {
        const text = '';
        const actions = [];
        const position = 0;

        const [resultText, resultPosition] = preserveOriginalTextIfNoModifications(text, actions, position);

        expect(resultText).toBe('');
        expect(resultPosition).toBe(0);
    });

    test('returns insertion content when text is empty and multiple actions exist with insert', () => {
        const text = '';
        const actions = [
            { type: 'delete', content: '' },
            { type: 'insert', content: 'first insert' },
            { type: 'insert', content: 'second insert' }
        ];
        const position = 10;

        const [resultText, resultPosition] = preserveOriginalTextIfNoModifications(text, actions, position);

        // Should return the first insert action found
        expect(resultText).toBe('first insert');
        expect(resultPosition).toBe(Infinity);
    });

    test('preserves original text when it contains only whitespace', () => {
        const text = '   ';
        const actions = [{ type: 'insert', content: 'new' }];
        const position = 0;

        const [resultText, resultPosition] = preserveOriginalTextIfNoModifications(text, actions, position);

        expect(resultText).toBe('   ');
        expect(resultPosition).toBe(0);
    });

    test('handles insertion with empty content', () => {
        const text = '';
        const actions = [{ type: 'insert', content: '' }];
        const position = 5;

        const [resultText, resultPosition] = preserveOriginalTextIfNoModifications(text, actions, position);

        expect(resultText).toBe('');
        expect(resultPosition).toBe(Infinity);
    });
});

