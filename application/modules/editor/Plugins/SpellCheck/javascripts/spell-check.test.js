import SpellCheck from './spell-check.js';

// --- Global mocks required by spell-check.js ---

global.Editor = {
    util: {
        HtmlClasses: {
            CSS_CLASSNAME_SPELLCHECK: 't5spellcheck',
        },
    },
    data: {
        l10n: {
            SpellCheck: {
                nodeTitle: 'Press Ctrl+R to select proposals',
            },
        },
        plugins: {
            SpellCheck: {
                cssMap: {
                    misspelling: 't5misspelling',
                    grammar: 't5grammar',
                    style: 't5style',
                },
            },
        },
    },
};

// --- Helpers ---

function buildEditor() {
    return {
        getInternalTagsPositions: () => ({}),
    };
}

// ============================================================
// prepareTextForSpellCheck
// ============================================================

describe('SpellCheck.prepareTextForSpellCheck', () => {
    let spellCheck;

    beforeEach(() => {
        spellCheck = new SpellCheck();
    });

    test('returns plain text unchanged', () => {
        expect(spellCheck.prepareTextForSpellCheck('Hello world')).toBe('Hello world');
    });

    test('strips spellcheck span wrappers and keeps inner text', () => {
        const input = '<span class="t5spellcheck t5misspelling ownttip">misspeled</span> word';
        expect(spellCheck.prepareTextForSpellCheck(input)).toBe('misspeled word');
    });

    test('converts <br> to newline', () => {
        expect(spellCheck.prepareTextForSpellCheck('line one<br>line two')).toBe('line one\nline two');
    });

    test('strips <del> nodes entirely', () => {
        expect(spellCheck.prepareTextForSpellCheck('keep<del> deleted</del> this')).toBe('keep this');
    });

    test('strips <img> nodes', () => {
        expect(spellCheck.prepareTextForSpellCheck('before<img src="x.png">after')).toBe('beforeafter');
    });

    test('replaces whitespace <img> with a space', () => {
        const img = '<img class="whitespace" src="x.png">';
        expect(spellCheck.prepareTextForSpellCheck(`before${img}after`)).toBe('before after');
    });

    test('does not replace newline whitespace images with space', () => {
        const img = '<img class="whitespace newline" src="x.png">';
        // newline images are not top-level IMG whitespace replacements — they remain stripped
        const result = spellCheck.prepareTextForSpellCheck(`before${img}after`);
        expect(result).toBe('beforeafter');
    });

    test('trims surrounding whitespace', () => {
        expect(spellCheck.prepareTextForSpellCheck('  hello  ')).toBe('hello');
    });

    test('unescapes HTML entities', () => {
        expect(spellCheck.prepareTextForSpellCheck('Tom &amp; Jerry')).toBe('Tom & Jerry');
    });

    test('handles empty string', () => {
        expect(spellCheck.prepareTextForSpellCheck('')).toBe('');
    });
});

// ============================================================
// cleanupSpellcheckNodes
// ============================================================

describe('SpellCheck.cleanupSpellcheckNodes', () => {
    let spellCheck;

    beforeEach(() => {
        spellCheck = new SpellCheck();
    });

    test('unwraps a single spellcheck span', () => {
        const input = '<span class="t5spellcheck ownttip" data-spellcheck-activematchindex="0" data-qtip="x">misspeled</span>';
        expect(spellCheck.cleanupSpellcheckNodes(input)).toBe('misspeled');
    });

    test('unwraps multiple spellcheck spans', () => {
        const input =
            '<span class="t5spellcheck ownttip" data-spellcheck-activematchindex="0" data-qtip="x">one</span> ' +
            '<span class="t5spellcheck ownttip" data-spellcheck-activematchindex="1" data-qtip="x">two</span>';
        expect(spellCheck.cleanupSpellcheckNodes(input)).toBe('one two');
    });

    test('preserves content that is not wrapped in spellcheck spans', () => {
        expect(spellCheck.cleanupSpellcheckNodes('plain text')).toBe('plain text');
    });

    test('preserves inner HTML of the span (tags inside)', () => {
        const input = '<span class="t5spellcheck ownttip" data-spellcheck-activematchindex="0" data-qtip="x">bold</span>';
        expect(spellCheck.cleanupSpellcheckNodes(input)).toBe('bold');
    });

    test('unwraps spellcheck span containing an <ins> node, keeping the ins intact', () => {
        // The spellcheck wrapper is removed, but the inner <ins> is preserved as-is
        const input = '<span class="t5spellcheck ownttip" data-spellcheck-activematchindex="0" data-qtip="x"><ins class="trackchanges">added</ins></span>';
        expect(spellCheck.cleanupSpellcheckNodes(input)).toBe('<ins class="trackchanges">added</ins>');
    });

    test('unwraps spellcheck span containing a <del> node, keeping the del intact', () => {
        // The spellcheck wrapper is removed, but the inner <del> is preserved as-is
        const input = '<span class="t5spellcheck ownttip" data-spellcheck-activematchindex="0" data-qtip="x"><del class="trackchanges">removed</del></span>';
        expect(spellCheck.cleanupSpellcheckNodes(input)).toBe('<del class="trackchanges">removed</del>');
    });

    test('unwraps spellcheck span containing mixed ins and text', () => {
        const input = '<span class="t5spellcheck ownttip" data-spellcheck-activematchindex="0" data-qtip="x">mis<ins class="trackchanges">spelled</ins></span>';
        expect(spellCheck.cleanupSpellcheckNodes(input)).toBe('mis<ins class="trackchanges">spelled</ins>');
    });

    test('unwraps spellcheck span nested inside an <ins> node', () => {
        // The outer <ins> is not a spellcheck node so it is preserved;
        // the inner spellcheck span is unwrapped leaving just its text content
        const input = '<ins class="trackchanges"><span class="t5spellcheck ownttip" data-spellcheck-activematchindex="0" data-qtip="x">misspeled</span></ins>';
        expect(spellCheck.cleanupSpellcheckNodes(input)).toBe('<ins class="trackchanges">misspeled</ins>');
    });

    test('unwraps spellcheck span nested inside a <del> node', () => {
        // Same as above but with <del> — the del is kept, the spellcheck span is removed
        const input = '<del class="trackchanges"><span class="t5spellcheck ownttip" data-spellcheck-activematchindex="0" data-qtip="x">misspeled</span></del>';
        expect(spellCheck.cleanupSpellcheckNodes(input)).toBe('<del class="trackchanges">misspeled</del>');
    });

});

// ============================================================
// transformMatches
// ============================================================

describe('SpellCheck.transformMatches', () => {
    let spellCheck;

    beforeEach(() => {
        spellCheck = new SpellCheck();
    });

    function buildMatch(overrides = {}) {
        return {
            offset: 0,
            context: { length: 5 },
            message: 'Spelling mistake',
            replacements: [{ value: 'Hello' }, { value: 'Helo' }],
            rule: {
                issueType: 'misspelling',
                urls: [{ value: 'https://example.com' }],
            },
            ...overrides,
        };
    }

    test('maps replacements to plain value strings', () => {
        const [result] = spellCheck.transformMatches([buildMatch()], 'Hello world');
        expect(result.replacements).toEqual(['Hello', 'Helo']);
    });

    test('maps infoURLs to plain value strings', () => {
        const [result] = spellCheck.transformMatches([buildMatch()], 'Hello world');
        expect(result.infoURLs).toEqual(['https://example.com']);
    });

    test('returns empty infoURLs when rule has no urls', () => {
        const match = buildMatch();
        delete match.rule.urls;
        const [result] = spellCheck.transformMatches([match], 'Hello world');
        expect(result.infoURLs).toEqual([]);
    });

    test('applies correct cssClassErrorType from cssMap', () => {
        const [result] = spellCheck.transformMatches([buildMatch()], 'Hello world');
        expect(result.cssClassErrorType).toBe('t5misspelling');
    });

    test('falls back to empty string for unknown issueType', () => {
        const match = buildMatch({ rule: { issueType: 'unknown', urls: [] } });
        const [result] = spellCheck.transformMatches([match], 'Hello world');
        expect(result.cssClassErrorType).toBe('');
    });

    test('assigns sequential matchIndex values', () => {
        const matches = [buildMatch({ offset: 0 }), buildMatch({ offset: 6 })];
        const results = spellCheck.transformMatches(matches, 'Hello world');
        expect(results[0].matchIndex).toBe(0);
        expect(results[1].matchIndex).toBe(1);
    });

    test('calculates correct range for a plain text match', () => {
        // "Hello" starts at offset 0, context.length = 5
        const [result] = spellCheck.transformMatches([buildMatch({ offset: 0 })], 'Hello world');
        expect(result.range).toEqual({ start: 0, end: 5 });
    });

    test('calculates correct range when match starts mid-string', () => {
        // "world" at offset 6, context.length = 5
        const match = buildMatch({ offset: 6, context: { length: 5 } });
        const [result] = spellCheck.transformMatches([match], 'Hello world');
        expect(result.range).toEqual({ start: 6, end: 11 });
    });

    test('adjusts range when a deletion node precedes the matched word', () => {
        // Editor HTML: "<del>Hi </del>world"
        // Text sent to LanguageTool: "world" (del content stripped, 5 chars)
        // LanguageTool reports "world" at offset 0, length 5.
        //
        // In #getRangeForMatch the deletion occupies editor positions {start:0, end:3}:
        //   biasedStart = 0 - 0 = 0 → NOT < matchStart(0) → start is NOT shifted → start = 0
        //   biasedEnd   = 3 - 0 - 3 = 0 → IS < matchEnd(5)   → end IS shifted by 3   → end = 5 + 3 = 8
        //
        // So "world" maps to editor range {start: 0, end: 8}.
        const html = '<del>Hi </del>world';
        const sc = new SpellCheck();

        const matchAtStart = buildMatch({ offset: 0, context: { length: 5 } });
        const [resultAtStart] = sc.transformMatches([matchAtStart], html);
        expect(resultAtStart.range).toEqual({ start: 0, end: 8 });
    });

    test('adjusts range when a deletion node is strictly before the matched word', () => {
        // Editor HTML: "<del>Hi </del>world foo"
        // Text sent to LanguageTool: "world foo" — "foo" starts at offset 6.
        // The deletion "Hi " (3 chars) is before offset 6 in the biased text view,
        // so the editor range for "foo" shifts by 3 → {start: 9, end: 12}.
        const html = '<del>Hi </del>world foo';
        const sc = new SpellCheck();

        const matchFoo = buildMatch({ offset: 6, context: { length: 3 } });
        const [result] = sc.transformMatches([matchFoo], html);
        expect(result.range).toEqual({ start: 9, end: 12 });
    });

    test('deletion in the middle: match before it is not shifted', () => {
        // Editor HTML: "Hello <del>bad </del>world"
        // Logical offsets: text "Hello "(6), del "bad "(4) → {start:6, end:10}, text "world"
        // LT sees: "Hello world" → "Hello" at offset 0, len 5
        //
        // del {6,10} len=4:
        //   biasedStart = 6-0=6  < matchStart(0)? No  → deletionsBeforeStartLength stays 0
        //   biasedEnd   = 10-0-4=6 < matchEnd(5)?  No  → deletionsBeforeEndLength stays 0
        // → start=0, end=5  (no shift — deletion is after the match)
        const html = 'Hello <del>bad </del>world';
        const sc = new SpellCheck();

        const [result] = sc.transformMatches([buildMatch({ offset: 0, context: { length: 5 } })], html);
        expect(result.range).toEqual({ start: 0, end: 5 });
    });

    test('deletion in the middle: match after it is shifted in end only when deletion is between start and end', () => {
        // Editor HTML: "Hello <del>bad </del>world"
        // Logical offsets: text "Hello "(6), del "bad "(4) → {start:6, end:10}, text "world"
        // LT sees: "Hello world" → "world" at offset 6, len 5
        //
        // del {6,10} len=4:
        //   biasedStart = 6-0=6  < matchStart(6)? No  → deletionsBeforeStartLength stays 0
        //   biasedEnd   = 10-0-4=6 < matchEnd(11)? Yes → deletionsBeforeEndLength = 4
        // → start=6+0=6, end=11+4=15
        const html = 'Hello <del>bad </del>world';
        const sc = new SpellCheck();

        const [result] = sc.transformMatches([buildMatch({ offset: 6, context: { length: 5 } })], html);
        expect(result.range).toEqual({ start: 6, end: 15 });
    });

    test('two deletions: match between them shifts end only by the first deletion', () => {
        // Editor HTML: "<del>AA </del>Hello <del>BB </del>world"
        // Logical offsets: del1 "AA "(3) → {0,3}, text "Hello "(6), del2 "BB "(3) → {9,12}, text "world"
        // LT sees: "Hello world" → "Hello" at offset 0, len 5
        //
        // del1 {0,3} len=3:
        //   biasedStart = 0-0=0 < 0? No  → deletionsBeforeStartLength stays 0
        //   biasedEnd   = 3-0-3=0 < 5?  Yes → deletionsBeforeEndLength = 3
        // del2 {9,12} len=3:
        //   biasedStart = 9-0=9 < 0? No
        //   biasedEnd   = 12-3-3=6 < 5? No
        // → start=0, end=5+3=8
        const html = '<del>AA </del>Hello <del>BB </del>world';
        const sc = new SpellCheck();

        const [result] = sc.transformMatches([buildMatch({ offset: 0, context: { length: 5 } })], html);
        expect(result.range).toEqual({ start: 0, end: 8 });
    });

    test('two deletions: match after both is shifted in start and end by both deletions', () => {
        // Editor HTML: "<del>AA </del>Hello <del>BB </del>world"
        // Logical offsets: del1 "AA "(3) → {0,3}, text "Hello "(6), del2 "BB "(3) → {9,12}, text "world"
        // LT sees: "Hello world" → "world" at offset 6, len 5
        //
        // del1 {0,3} len=3:
        //   biasedStart = 0-0=0 < 6?  Yes → deletionsBeforeStartLength = 3
        //   biasedEnd   = 3-0-3=0 < 11? Yes → deletionsBeforeEndLength = 3
        // del2 {9,12} len=3:
        //   biasedStart = 9-3=6 < 6?  No
        //   biasedEnd   = 12-3-3=6 < 11? Yes → deletionsBeforeEndLength = 6
        // → start=6+3=9, end=11+6=17
        const html = '<del>AA </del>Hello <del>BB </del>world';
        const sc = new SpellCheck();

        const [result] = sc.transformMatches([buildMatch({ offset: 6, context: { length: 5 } })], html);
        expect(result.range).toEqual({ start: 9, end: 17 });
    });

    test('insertion only: match after it is not shifted (ins content is included in LT text)', () => {
        // Editor HTML: "Hello <ins>great </ins>world"
        // ins is not a del → no deletionsPositions, no offset shifts at all.
        // LT sees the full text including "great " → "world" at offset 12, len 5
        // → start=12, end=17
        const html = 'Hello <ins>great </ins>world';
        const sc = new SpellCheck();

        const [result] = sc.transformMatches([buildMatch({ offset: 12, context: { length: 5 } })], html);
        expect(result.range).toEqual({ start: 12, end: 17 });
    });

    test('insertion only: match before it is not shifted', () => {
        // Editor HTML: "Hello <ins>great </ins>world"
        // LT sees: "Hello great world" → "Hello" at offset 0, len 5
        // No deletions → no shift.
        // → start=0, end=5
        const html = 'Hello <ins>great </ins>world';
        const sc = new SpellCheck();

        const [result] = sc.transformMatches([buildMatch({ offset: 0, context: { length: 5 } })], html);
        expect(result.range).toEqual({ start: 0, end: 5 });
    });

    test('deletion and insertion combined: match after both is shifted only by the deletion', () => {
        // Editor HTML: "<del>AA </del>Hello <ins>great </ins>world"
        // Logical offsets: del1 "AA "(3) → {0,3}, text "Hello "(6), ins "great "(6), text "world"
        // LT sees: "Hello great world" (del stripped, ins kept) → "world" at offset 12, len 5
        //
        // del1 {0,3} len=3:
        //   biasedStart = 0-0=0 < 12? Yes → deletionsBeforeStartLength = 3
        //   biasedEnd   = 3-0-3=0 < 17? Yes → deletionsBeforeEndLength = 3
        // → start=12+3=15, end=17+3=20
        const html = '<del>AA </del>Hello <ins>great </ins>world';
        const sc = new SpellCheck();

        const [result] = sc.transformMatches([buildMatch({ offset: 12, context: { length: 5 } })], html);
        expect(result.range).toEqual({ start: 15, end: 20 });
    });

    test('deletion and insertion combined: match on insertion content is not shifted', () => {
        // Editor HTML: "<del>AA </del>Hello <ins>great </ins>world"
        // LT sees: "Hello great world" → "great" at offset 6, len 5
        //
        // del1 {0,3} len=3:
        //   biasedStart = 0-0=0 < 6?  Yes → deletionsBeforeStartLength = 3
        //   biasedEnd   = 3-0-3=0 < 11? Yes → deletionsBeforeEndLength = 3
        // → start=6+3=9, end=11+3=14
        const html = '<del>AA </del>Hello <ins>great </ins>world';
        const sc = new SpellCheck();

        const [result] = sc.transformMatches([buildMatch({ offset: 6, context: { length: 5 } })], html);
        expect(result.range).toEqual({ start: 9, end: 14 });
    });

});

// ============================================================
// applyMatches
// ============================================================

describe('SpellCheck.applyMatches', () => {
    const QTIP = 'Press Ctrl+R to select proposals';

    function buildMatch(start, end, index = 0, cssClass = 't5misspelling') {
        return {
            matchIndex: index,
            range: { start, end },
            cssClassErrorType: cssClass,
        };
    }

    function spellcheckSpan(text, index, cssClass = 't5misspelling') {
        return `<span class="t5spellcheck ${cssClass} ownttip" data-spellcheck-activematchindex="${index}" data-qtip="${QTIP}" data-t5qfp="false">${text}</span>`;
    }

    test('wraps a single match at the start of the string', () => {
        // Logical: "Hello world" — "Hello" at 0–5, " world" at 5–11
        const content = 'Hello world';
        const spellCheck = new SpellCheck(buildEditor(content));

        expect(spellCheck.applyMatches(content, [buildMatch(0, 5, 0)])).toBe(
            `${spellcheckSpan('Hello', 0)} world`
        );
    });

    test('wraps a single match in the middle, preserving prefix and suffix', () => {
        // Logical: "Hello world" — "Hello " at 0–6, "world" at 6–11
        const content = 'Hello world';
        const spellCheck = new SpellCheck(buildEditor(content));

        expect(spellCheck.applyMatches(content, [buildMatch(6, 11, 0)])).toBe(
            `Hello ${spellcheckSpan('world', 0)}`
        );
    });

    test('wraps two consecutive matches with text between and after them', () => {
        // Logical: "Hello world foo" — "Hello" at 0–5, "world" at 6–11, " foo" at 11–15
        const content = 'Hello world foo';
        const spellCheck = new SpellCheck(buildEditor(content));

        expect(spellCheck.applyMatches(content, [buildMatch(0, 5, 0), buildMatch(6, 11, 1)])).toBe(
            `${spellcheckSpan('Hello', 0)} ${spellcheckSpan('world', 1)} foo`
        );
    });

    test('uses matchIndex from the match object for the data-spellcheck-activematchindex attribute', () => {
        const content = 'Hello world';
        const spellCheck = new SpellCheck(buildEditor(content));

        expect(spellCheck.applyMatches(content, [buildMatch(6, 11, 7)])).toBe(
            `Hello ${spellcheckSpan('world', 7)}`
        );
    });

    test('applies the cssClassErrorType from the match to the span class', () => {
        const content = 'Hello world';
        const spellCheck = new SpellCheck(buildEditor(content));

        expect(spellCheck.applyMatches(content, [buildMatch(0, 5, 0, 't5grammar')])).toBe(
            `${spellcheckSpan('Hello', 0, 't5grammar')} world`
        );
    });

    test('replaces leading &nbsp; before the match with a regular space', () => {
        // "&nbsp;" is a single logical character, "Hello" at 1–6
        const content = '&nbsp;Hello';
        const spellCheck = new SpellCheck(buildEditor(content));

        expect(spellCheck.applyMatches(content, [buildMatch(1, 6, 0)])).toBe(
            ` ${spellcheckSpan('Hello', 0)}`
        );
    });

    test('preserves a lone trailing &nbsp; without replacing it', () => {
        // "Hello" at 0–5, "&nbsp;" at logical position 5 (single char)
        const content = 'Hello&nbsp;';
        const spellCheck = new SpellCheck(buildEditor(content));

        expect(spellCheck.applyMatches(content, [buildMatch(0, 5, 0)])).toBe(
            `${spellcheckSpan('Hello', 0)}&nbsp;`
        );
    });

    test('returns original string for an empty matches array', () => {
        const content = 'Hello world';
        const spellCheck = new SpellCheck(buildEditor(content));

        expect(spellCheck.applyMatches(content, [])).toBe('Hello world');
    });

    test('wraps a word that follows a <del> node, keeping the del in the prefix', () => {
        // <del> is a transparent container: "old " = 4 logical chars (0–4)
        // "new " at 4–8, "word" at 8–12
        const DEL = '<del class="trackchanges">old </del>';
        const content = `${DEL}new word`;
        const spellCheck = new SpellCheck(buildEditor(content));

        expect(spellCheck.applyMatches(content, [buildMatch(8, 12, 0)])).toBe(
            `${DEL}new ${spellcheckSpan('word', 0)}`
        );
    });

    test('wraps a word that follows an <ins> node, keeping the ins in the prefix', () => {
        // <ins> is transparent: "added " = 6 logical chars (0–6)
        // "misspeled" at 6–15
        const INS = '<ins class="trackchanges">added </ins>';
        const content = `${INS}misspeled`;
        const spellCheck = new SpellCheck(buildEditor(content));

        expect(spellCheck.applyMatches(content, [buildMatch(6, 15, 0)])).toBe(
            `${INS}${spellcheckSpan('misspeled', 0)}`
        );
    });

    test('wraps a match that spans across an <ins> node including its content', () => {
        // "before " at 0–7, <ins> content "added " at 7–13, " after" at 13–19
        // Match covers the ins content: 7–13
        const INS = '<ins class="trackchanges">added </ins>';
        const content = `before ${INS} after`;
        const spellCheck = new SpellCheck(buildEditor(content));

        expect(spellCheck.applyMatches(content, [buildMatch(7, 13, 0)])).toBe(
            `before ${spellcheckSpan(INS, 0)} after`
        );
    });

    test('match whose range is entirely a <del> node emits the del plain without a spellcheck span', () => {
        // "keep " at 0–5, <del> content "old " at 5–9, " this" at 9–14
        // Match covers only the del content: 5–9.
        // Because the entire match content is a leading deletion it is stripped from the span
        // and emitted as plain text — no spellcheck span is created.
        const DEL = '<del class="trackchanges">old </del>';
        const content = `keep ${DEL} this`;
        const spellCheck = new SpellCheck(buildEditor(content));

        expect(spellCheck.applyMatches(content, [buildMatch(5, 9, 0)])).toBe(
            `keep ${DEL} this`
        );
    });

    test('wraps words around <img> internal tags, keeping img in prefix and suffix', () => {
        // <img> = 1 logical unit; "Hello " at 1–7; second <img> at 7; "world" at 8–13
        const IMG = '<img class="internal-tag single" data-length="1">';
        const content = `${IMG}Hello ${IMG}world`;
        const spellCheck = new SpellCheck(buildEditor(content));

        expect(spellCheck.applyMatches(content, [buildMatch(1, 7, 0), buildMatch(8, 13, 1)])).toBe(
            `${IMG}${spellcheckSpan('Hello ', 0)}${IMG}${spellcheckSpan('world', 1)}`
        );
    });

    test('wraps a word followed by an <img> internal tag, img appears in the trailing suffix', () => {
        // "misspeled" at 0–9, " " at 9, <img> at 10
        const IMG = '<img class="internal-tag single" data-length="1">';
        const content = `misspeled ${IMG}`;
        const spellCheck = new SpellCheck(buildEditor());

        expect(spellCheck.applyMatches(content, [buildMatch(0, 9, 0)])).toBe(
            `${spellcheckSpan('misspeled', 0)} ${IMG}`
        );
    });

    test('two matches with an <ins> node between them', () => {
        // "Hello" at 0–5, " " at 5, <ins> content "great " at 6–12, "world" at 12–17
        const INS = '<ins class="trackchanges">great </ins>';
        const content = `Hello ${INS}world`;
        const spellCheck = new SpellCheck(buildEditor());

        expect(spellCheck.applyMatches(content, [buildMatch(0, 5, 0), buildMatch(12, 17, 1)])).toBe(
            `${spellcheckSpan('Hello', 0)} ${INS}${spellcheckSpan('world', 1)}`
        );
    });

    test('two matches with a <del> node between them', () => {
        // "Hello" at 0–5, " " at 5, <del> content "old " at 6–10, "world" at 10–15
        const DEL = '<del class="trackchanges">old </del>';
        const content = `Hello ${DEL}world`;
        const spellCheck = new SpellCheck(buildEditor(content));

        expect(spellCheck.applyMatches(content, [buildMatch(0, 5, 0), buildMatch(10, 15, 1)])).toBe(
            `${spellcheckSpan('Hello', 0)} ${DEL}${spellcheckSpan('world', 1)}`
        );
    });

    // ---- term-node guard (#isInsideTermNode) ----

    test('match entirely inside a <span class="term"> is not wrapped in a spellcheck node', () => {
        // Logical positions: open term (transparent), 'm'(0)…'d'(8) = "misspeled", close term, ' '(9), 'a'(10)…'r'(14)
        // #isInsideTermNode returns true for every char in [0,9) → remainder is emitted as-is
        const TERM = '<span class="term">misspeled</span>';
        const content = `${TERM} after`;
        const spellCheck = new SpellCheck(buildEditor(content));

        expect(spellCheck.applyMatches(content, [buildMatch(0, 9, 0)])).toBe(
            `${TERM} after`
        );
    });

    test('match before a term span is wrapped normally', () => {
        // "misspeled" at 0–9 (outside term), space at 9, "Terminus" at 10–18 (inside term)
        // #isInsideTermNode returns false for [0,9) → normal spellcheck wrapping
        const TERM = '<span class="term">Terminus</span>';
        const content = `misspeled ${TERM}`;
        const spellCheck = new SpellCheck(buildEditor(content));

        expect(spellCheck.applyMatches(content, [buildMatch(0, 9, 0)])).toBe(
            `${spellcheckSpan('misspeled', 0)} ${TERM}`
        );
    });

    test('match after a term span is wrapped normally', () => {
        // "Terminus" at 0–8 (inside term), space at 8, "misspeled" at 9–18 (outside term)
        // #isInsideTermNode returns false for [9,18) → normal spellcheck wrapping
        const TERM = '<span class="term">Terminus</span>';
        const content = `${TERM} misspeled`;
        const spellCheck = new SpellCheck(buildEditor(content));

        expect(spellCheck.applyMatches(content, [buildMatch(9, 18, 0)])).toBe(
            `${TERM} ${spellcheckSpan('misspeled', 0)}`
        );
    });

    test('two matches: match inside term is skipped, match outside term is wrapped', () => {
        // "misspeled" at 0–9 (inside term), space at 9, "world" at 10–15 (outside term)
        const TERM = '<span class="term">misspeled</span>';
        const content = `${TERM} world`;
        const spellCheck = new SpellCheck(buildEditor(content));

        expect(spellCheck.applyMatches(content, [buildMatch(0, 9, 0), buildMatch(10, 15, 1)])).toBe(
            `${TERM} ${spellcheckSpan('world', 1)}`
        );
    });

    test('match inside term nested in a track-change <ins> is not wrapped', () => {
        // "misspeled" lives inside <ins><span class="term">…</span></ins>
        // #isInsideTermNode detects span.term in the open-stack and returns true
        const content = '<ins class="trackchanges"><span class="term">misspeled</span></ins> after';
        const spellCheck = new SpellCheck(buildEditor(content));

        expect(spellCheck.applyMatches(content, [buildMatch(0, 9, 0)])).toBe(
            '<ins class="trackchanges"><span class="term">misspeled</span></ins> after'
        );
    });

    test('match inside term with multiple CSS classes is not wrapped (classList.contains check)', () => {
        // Real-world term spans carry additional classes alongside "term"
        // classList.contains('term') must still match correctly
        const TERM = '<span class="transFound term preferred">misspeled</span>';
        const content = `${TERM} after`;
        const spellCheck = new SpellCheck(buildEditor(content));

        expect(spellCheck.applyMatches(content, [buildMatch(0, 9, 0)])).toBe(
            `${TERM} after`
        );
    });

    test('match partially overlapping a term span: even one char inside term prevents wrapping', () => {
        // "te"  at 0–2 (outside term), "rm" at 2–4 (inside term), "word" at 4–8
        // Match [1, 3) covers 'e' (outside) and 'r' (inside) — #isInsideTermNode returns true
        // at the first char inside the term, so no spellcheck span is created for the match
        const content = 'te<span class="term">rm</span>word';
        const spellCheck = new SpellCheck(buildEditor(content));

        const result = spellCheck.applyMatches(content, [buildMatch(1, 3, 0)]);

        // The match is NOT wrapped in a spellcheck span
        expect(result).not.toContain('t5spellcheck');
        // The prefix 't' and the remaining content are still present
        expect(result).toContain('t');
        expect(result).toContain('r');
    });

    test('real life example with multiple matches, del and ins nodes', () => {
        // Logical position accounting (transparent containers, text chars = 1):
        //   "Diese Datei ist Teil der " = 25 chars → 0–25
        //   DEL1 content "p"            =  1 char  → 25–26
        //   DEL2 content "hp-online-Dokumentation " = 24 chars → 26–50
        //   INS1 content "php"          =  3 chars → 50–53
        //   INS2 content "-online-Dokumentation" = 21 chars → 53–74
        //   ". Ihre … durchgeführten " =119 chars → 74–193
        //   "winalign-Project"          = 16 chars → 193–209
        //   " basiert … für "           =154 chars → 209–363
        //   "translate"                 =  9 chars → 363–372
        //   "5."                        =  2 chars → 372–374
        const DEL = '<del class="trackchanges ownttip deleted" data-usertrackingid="1689" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2026-03-20T14:39:12+02:00">p</del>'
            + '<del class="trackchanges ownttip deleted" data-usertrackingid="1689" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2026-03-20T15:50:36+02:00">hp-online-Dokumentation </del>';
        const INS = '<ins class="trackchanges ownttip" data-usertrackingid="1689" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2026-03-20T15:50:07+02:00">php</ins>'
            + '<ins class="trackchanges ownttip" data-usertrackingid="1689" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2026-03-20T15:47:30+02:00">-online-Dokumentation</ins>';
        const content = `Diese Datei ist Teil der ${DEL}${INS}. Ihre Übersetzung ist Übersetzung durch 123 eine Vorübersetzung entstanden, die auf einem sehr schnell durchgeführten winalign-Project basiert und in keiner Art und Weise dem State of the Art eines Übersetzungsprojekts entspricht. Sein einziger Zweck ist die Erzeugung von Demo-Daten für translate5.`;
        const spellCheck = new SpellCheck(buildEditor(content));

        const result = spellCheck.applyMatches(content, [
            buildMatch(25, 74, 0),   // wraps the entire del+ins block
            buildMatch(193, 209, 1), // wraps "winalign-Project"
            buildMatch(363, 372, 2), // wraps "translate"
        ]);

        expect(result).toBe(
            `Diese Datei ist Teil der ${DEL}${spellcheckSpan(INS, 0)}. Ihre Übersetzung ist Übersetzung durch 123 eine Vorübersetzung entstanden, die auf einem sehr schnell durchgeführten ${spellcheckSpan('winalign-Project', 1)} basiert und in keiner Art und Weise dem State of the Art eines Übersetzungsprojekts entspricht. Sein einziger Zweck ist die Erzeugung von Demo-Daten für ${spellcheckSpan('translate', 2)}5.`
        );
    });

    test('real life example with multiple matches, do not wrap term', () => {
        const content = `Diese Datei ist Teil der php-online-Dokumentation. Ihre Übersetzung ist Übersetzung durch eine Vorübersetzung entstanden, die auf einem sehr schnell durchgeführten winalign-Project basiert und in keiner Art und Weise dem State of the Art eines Übersetzungsprojekts entspricht.<ins class="trackchanges ownttip" data-usertrackingid="4679" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2026-04-13T12:30:21+03:00"> 1234567</ins> Sein einziger Zweck ist die Erzeugung von Demo-Daten für <span class="term admittedTerm exact" title="">translate5</span>.`;
        const spellCheck = new SpellCheck(buildEditor(content));

        const result = spellCheck.applyMatches(content, [
            buildMatch(25, 49, 0),   // php-online-Dokumentation
            buildMatch(164, 180, 1), // winalign-Project
            buildMatch(342, 351, 2), // translate
        ]);

        expect(result).toBe(
            `Diese Datei ist Teil der ${spellcheckSpan('php-online-Dokumentation', 0)}. Ihre Übersetzung ist Übersetzung durch eine Vorübersetzung entstanden, die auf einem sehr schnell durchgeführten ${spellcheckSpan('winalign-Project', 1)} basiert und in keiner Art und Weise dem State of the Art eines Übersetzungsprojekts entspricht.<ins class="trackchanges ownttip" data-usertrackingid="4679" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2026-04-13T12:30:21+03:00"> 1234567</ins> Sein einziger Zweck ist die Erzeugung von Demo-Daten für <span class="term admittedTerm exact" title="">translate5</span>.`
        );
    });

    test('real life example with line break', () => {
        const content = `<img class="internal-tag single whitespace newline" src="" alt="↵" id="tag-image-whitespace1" title="&lt;1/&gt;: Newline" data-length="1" data-pixellength="0" data-tag-number="1"><br><img class="internal-tag single whitespace" src="" alt="→→→→→" id="tag-image-whitespace2" title="&lt;2/&gt;: 5 tab characters" data-length="5" data-pixellength="0" data-tag-number="2">This file is a based on a part of the php-online-Documentation. It's translation is done by a pretranslation based on a very fast winalign-Project and is not at all state of the translation art. It's only purpose is the generation of demo-data for translate5.`;
        const spellCheck = new SpellCheck(buildEditor());

        const result = spellCheck.applyMatches(content, [
            buildMatch(66, 70, 0),
            buildMatch(96, 110, 1),
        ]);

        expect(result).toBe(
            `<img class="internal-tag single whitespace newline" src="" alt="↵" id="tag-image-whitespace1" title="<1/>: Newline" data-length="1" data-pixellength="0" data-tag-number="1"><br><img class="internal-tag single whitespace" src="" alt="→→→→→" id="tag-image-whitespace2" title="<2/>: 5 tab characters" data-length="5" data-pixellength="0" data-tag-number="2">This file is a based on a part of the php-online-Documentation. ${spellcheckSpan('It\'s', 0)} translation is done by a ${spellcheckSpan('pretranslation', 1)} based on a very fast winalign-Project and is not at all state of the translation art. It's only purpose is the generation of demo-data for translate5.`
        );
    });

    test('real life example with insert in the end', () => {
        const text = `Hello world,`;
        const word1 = 'Hello';
        const word2 = 'worl';
        const ins = `<ins class="trackchanges ownttip" data-usertrackingid="2083" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2026-04-25T13:07:02+03:00"> ${word1}&nbsp;${word2}</ins>`

        const content = text + ins;

        const spellCheck = new SpellCheck(buildEditor());

        const result = spellCheck.applyMatches(content, [
            buildMatch(19, 23, 0),
        ]);

        expect(result).toBe(
            `${text}<ins class="trackchanges ownttip" data-usertrackingid="2083" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2026-04-25T13:07:02+03:00"> ${word1}&nbsp;</ins>${spellcheckSpan('<ins class="trackchanges ownttip" data-usertrackingid="2083" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2026-04-25T13:07:02+03:00">' + word2 + '</ins>', 0)}`
        );
    });

    test('real life example with insert in the end and 2 matches', () => {
        const text = `Hello world,`;
        const word1 = 'Helo';
        const word2 = 'worl';
        const ins = `<ins class="trackchanges ownttip" data-usertrackingid="2083" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2026-04-25T13:07:02+03:00"> ${word1}&nbsp;${word2}</ins>`

        const content = text + ins;

        const spellCheck = new SpellCheck(buildEditor());

        const result = spellCheck.applyMatches(content, [
            buildMatch(13, 17, 0),
            buildMatch(18, 22, 1),
        ]);

        expect(result).toBe(
            `${text}<ins class="trackchanges ownttip" data-usertrackingid="2083" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2026-04-25T13:07:02+03:00"> </ins>${spellcheckSpan('<ins class="trackchanges ownttip" data-usertrackingid="2083" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2026-04-25T13:07:02+03:00">' + word1 + '</ins>', 0)}<ins class="trackchanges ownttip" data-usertrackingid="2083" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2026-04-25T13:07:02+03:00">&nbsp;</ins>${spellcheckSpan('<ins class="trackchanges ownttip" data-usertrackingid="2083" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2026-04-25T13:07:02+03:00">' + word2 + '</ins>', 1)}`
        );
    });
});
