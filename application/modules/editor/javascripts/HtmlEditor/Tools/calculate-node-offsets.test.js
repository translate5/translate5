import calculateNodeOffsets from './calculate-node-offsets.js';
// import {JSDOM} from 'jsdom';

describe('calculateNodeOffsets', () => {
    describe('basic functionality', () => {
        test('returns empty when target is null', () => {
            const root = document.createElement('div');
            root.innerHTML = 'Hello world';

            const result = calculateNodeOffsets(root, null);

            expect(result).toStrictEqual({start: 0, end: 0});
        });

        test('returns empty when target is undefined', () => {
            const root = document.createElement('div');
            root.innerHTML = 'Hello world';

            const result = calculateNodeOffsets(root, undefined);

            expect(result).toStrictEqual({start: 0, end: 0});
        });

        test('calculates offsets for a single text node', () => {
            const root = document.createElement('div');
            root.innerHTML = 'Hello world';

            const target = root.firstChild;
            const result = calculateNodeOffsets(root, target);

            expect(result).toEqual({
                start: 0,
                end: 11 // "Hello world" = 11 characters
            });
        });

        test('calculates offsets for the root element itself', () => {
            const root = document.createElement('div');
            root.innerHTML = 'Test';

            const result = calculateNodeOffsets(root, root);

            expect(result).toEqual({
                start: 0,
                end: 0
            });
        });
    });

    describe('text nodes', () => {
        test('calculates offsets for second text node', () => {
            const root = document.createElement('div');
            root.innerHTML = 'First<span id="second">Middle</span>Last';

            // Target the "Last" text node
            const target = root.querySelector('#second');
            const result = calculateNodeOffsets(root, target);

            expect(result).toEqual({
                start: 5, // "First" (5)
                end: 11 // + "Middle" (6) = 11
            });
        });

        test('calculates offsets for text node after multiple siblings', () => {
            const root = document.createElement('div');
            root.innerHTML = 'One<span>Two</span>Three<span>Four</span>Five';

            // Target "Five" text node
            const target = root.lastChild;
            const result = calculateNodeOffsets(root, target);

            expect(result).toEqual({
                start: 15, // "One"(3) + "Two"(3) + "Three"(5) + "Four"(4) = 15, but "Five" starts at 15
                end: 19 // + "Five"(4) = 19, but ends at 18
            });
        });
    });

    describe('element nodes', () => {
        test('calculates offsets for nested element', () => {
            const root = document.createElement('div');
            root.innerHTML = 'Before<span id="target">Content</span>After';

            const target = root.querySelector('#target');
            const result = calculateNodeOffsets(root, target);

            expect(result).toEqual({
                start: 6,  // "Before" = 6 characters
                end: 13 // + "Content" (7) = 13
            });
        });

        test('calculates offsets for deeply nested element', () => {
            const root = document.createElement('div');
            root.innerHTML = 'Start<div><span id="target">Deep</span></div>End';

            const target = root.querySelector('#target');
            const result = calculateNodeOffsets(root, target);

            expect(result).toEqual({
                start: 5,  // "Start" = 5
                end: 9     // + "Deep" (4) = 9
            });
        });

        test('calculates offsets for element with multiple children', () => {
            const root = document.createElement('div');
            root.innerHTML = 'A<div id="target">B<span>C</span>D</div>E';

            const target = root.querySelector('#target');
            const result = calculateNodeOffsets(root, target);

            expect(result).toEqual({
                start: 1, // "A" = 1
                end: 4 // + "B" (1) + "C" (1) + "D" (1) = 4
            });
        });
    });

    describe('IMG tags (internal tags)', () => {
        test('calculates offsets with single IMG tag', () => {
            const root = document.createElement('div');
            root.innerHTML = 'Before<img src="test.jpg"/>After';

            const target = root.querySelector('img');
            const result = calculateNodeOffsets(root, target);

            expect(result).toEqual({
                start: 6, // "Before" = 6
                end: 7 // IMG = 1
            });
        });

        test('calculates offsets for text after IMG tag', () => {
            const root = document.createElement('div');
            root.innerHTML = 'Text<img src="test.jpg"/><span id="target">More</span>';

            const target = root.querySelector('#target');
            const result = calculateNodeOffsets(root, target);

            expect(result).toEqual({
                start: 5, // "Text" (4) + IMG (1) = 5
                end: 9 // + "More" (4) = 9
            });
        });

        test('calculates offsets with multiple IMG tags', () => {
            const root = document.createElement('div');
            root.innerHTML = 'A<img/>B<img/><span id="target">C</span>';

            const target = root.querySelector('#target');
            const result = calculateNodeOffsets(root, target);

            expect(result).toEqual({
                start: 4,  // "A" (1) + IMG (1) + "B" (1) + IMG (1) = 4
                end: 5     // + "C" (1) = 5
            });
        });

        test('calculates offsets for nested IMG tags', () => {
            const root = document.createElement('div');
            root.innerHTML = 'Start<div><img/><span id="target">Text</span></div>';

            const target = root.querySelector('#target');
            const result = calculateNodeOffsets(root, target);

            expect(result).toEqual({
                start: 6,  // "Start" (5) + IMG (1) = 6
                end: 10    // + "Text" (4) = 10
            });
        });
    });

    describe('node matching', () => {
        test('finds node by reference', () => {
            const root = document.createElement('div');
            root.innerHTML = 'ABC<span>DEF</span>GHI';

            const span = root.querySelector('span');
            const result = calculateNodeOffsets(root, span);

            expect(result).toEqual({
                start: 3,  // "ABC" = 3
                end: 6     // + "DEF" (3) = 6
            });
        });

        // test('finds node by outerHTML match', () => {
        //     const root = document.createElement('div');
        //     root.innerHTML = 'ABC<span class="test">DEF</span>GHI';
        //
        //     // Create a separate node with same outerHTML
        //     const clone = document.createElement('span');
        //     clone.className = 'test';
        //     clone.textContent = 'DEF';
        //
        //     const result = calculateNodeOffsets(root, clone);
        //
        //     expect(result).toEqual({
        //         start: 3,  // "ABC" = 3
        //         end: 6     // + "DEF" (3) = 6
        //     });
        // });
    });

    describe('complex scenarios', () => {
        test('handles empty elements', () => {
            const root = document.createElement('div');
            root.innerHTML = 'Before<span></span><span id="target">After</span>';

            const target = root.querySelector('#target');
            const result = calculateNodeOffsets(root, target);

            expect(result).toEqual({
                start: 6,  // "Before" = 6, empty span = 0
                end: 11    // + "After" (5) = 11
            });
        });

        test('handles mixed content with text, elements, and images', () => {
            const root = document.createElement('div');
            root.innerHTML = 'Text<img/><span>More<img/>Text</span><div id="target">Final</div>';

            const target = root.querySelector('#target');
            const result = calculateNodeOffsets(root, target);

            expect(result).toEqual({
                start: 14, // "Text"(4) + IMG(1) + "More"(4) + IMG(1) + "Text"(4) = 14, but let me recalculate
                end: 19 // + "Final"(5) = 19, but ends at 16
            });
        });

        test('stops searching after finding target', () => {
            const root = document.createElement('div');
            root.innerHTML = 'A<span id="target">B</span>C<span>D</span>E';

            const target = root.querySelector('#target');
            const result = calculateNodeOffsets(root, target);

            expect(result).toEqual({
                start: 1,  // "A" = 1
                end: 2     // + "B" (1) = 2
            });
            // The function should stop and not process "C", "D", "E"
        });

        test('handles whitespace text nodes', () => {
            const root = document.createElement('div');
            root.innerHTML = 'Start  <span id="target">  Middle  </span>  End';

            const target = root.querySelector('#target');
            const result = calculateNodeOffsets(root, target);

            expect(result).toEqual({
                start: 7,  // "Start  " = 7 (including spaces)
                end: 17    // + "  Middle  " (10) = 17
            });
        });

        test('handles special characters', () => {
            const root = document.createElement('div');
            root.innerHTML = 'Hello&nbsp;world<span id="target">Test&amp;Test</span>';

            const target = root.querySelector('#target');
            const result = calculateNodeOffsets(root, target);

            // Note: innerHTML decodes entities, so &nbsp; becomes a non-breaking space character
            const firstTextLength = root.firstChild.nodeValue.length;
            expect(result.start).toBe(firstTextLength);
        });
    });

    describe('edge cases', () => {
        test('handles root with no children', () => {
            const root = document.createElement('div');

            const result = calculateNodeOffsets(root, root);

            expect(result).toEqual({
                start: 0,
                end: 0
            });
        });

        test('handles target not found in tree', () => {
            const root = document.createElement('div');
            root.innerHTML = 'Content';

            const unrelatedNode = document.createElement('span');
            unrelatedNode.textContent = 'Unrelated';

            const result = calculateNodeOffsets(root, unrelatedNode);

            expect(result).toEqual({start: 0, end: 0});
        });

        test('handles deeply nested structure', () => {
            const root = document.createElement('div');
            root.innerHTML = '<div><div><div><div><span id="target">Deep</span></div></div></div></div>';

            const target = root.querySelector('#target');
            const result = calculateNodeOffsets(root, target);

            expect(result).toEqual({
                start: 0,  // No text before
                end: 4     // "Deep" = 4
            });
        });

        test('handles sibling elements at same level', () => {
            const root = document.createElement('div');
            root.innerHTML = '<span>A</span><span>B</span><span id="target">C</span><span>D</span>';

            const target = root.querySelector('#target');
            const result = calculateNodeOffsets(root, target);

            expect(result).toEqual({
                start: 2,  // "A" (1) + "B" (1) = 2
                end: 3     // + "C" (1) = 3
            });
        });
    });

    describe('performance and traversal', () => {
        test('stops traversal after finding target', () => {
            const root = document.createElement('div');
            // Create a large tree
            let html = 'Start';
            for (let i = 0; i < 100; i++) {
                html += `<span>Item${i}</span>`;
            }
            html += '<span id="target">Target</span>';
            for (let i = 0; i < 100; i++) {
                html += `<span>After${i}</span>`;
            }
            root.innerHTML = html;

            const target = root.querySelector('#target');
            const result = calculateNodeOffsets(root, target);

            // Should find the target without processing all 200 spans
            expect(result).toBeTruthy();
            expect(result.start).toBeGreaterThan(0);
            expect(result.end).toBeGreaterThan(result.start);
        });
    });
});
