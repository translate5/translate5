import { splitNode, splitNodeByChild } from './split-node.js';

describe('splitNode', () => {
    test('cut text node in the middle', () => {
        const text = document.createTextNode('Hello');
        const [left, right] = splitNode(text, 2);
        expect(left.nodeType).toBe(Node.TEXT_NODE);
        expect(right.nodeType).toBe(Node.TEXT_NODE);
        expect(left.data).toBe('He');
        expect(right.data).toBe('llo');
    });

    test('insertion containing only tags is split in the middle', () => {
        document.body.innerHTML = `<ins class="trackchanges ownttip" data-usertrackingid="67914" data-usercssnr="usernr1" data-workflowstep="firsttranslation1" data-timestamp="2025-09-01T11:38:45+02:00"><img class="internal-tag single" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22\\'Open%20Sans\\',%20\\'Helvetica%20Neue\\',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;3/&amp;gt;%3C/text%3E%3C/svg%3E" alt="…" id="tag-image-internal-tag single3" title="…" data-length="-1" data-pixellength="0" data-tag-number="3"><img class="internal-tag single" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2226%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22\\'Open%20Sans\\',%20\\'Helvetica%20Neue\\',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E-40%3C/text%3E%3C/svg%3E" alt="-40" id="tag-image-internal-tag single6" title="&lt;6/&gt; CP: default simple" data-length="3" data-pixellength="0" data-tag-number="6"></ins>`;
        const div = document.querySelector('ins');
        const [left, right] = splitNode(div, 1);
        expect(left.outerHTML).toBe("<ins class=\"trackchanges ownttip\" data-usertrackingid=\"67914\" data-usercssnr=\"usernr1\" data-workflowstep=\"firsttranslation1\" data-timestamp=\"2025-09-01T11:38:45+02:00\"><img class=\"internal-tag single\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22\\'Open%20Sans\\',%20\\'Helvetica%20Neue\\',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;3/&amp;gt;%3C/text%3E%3C/svg%3E\" alt=\"…\" id=\"tag-image-internal-tag single3\" title=\"…\" data-length=\"-1\" data-pixellength=\"0\" data-tag-number=\"3\"></ins>");
        expect(right.outerHTML).toBe("<ins class=\"trackchanges ownttip\" data-usertrackingid=\"67914\" data-usercssnr=\"usernr1\" data-workflowstep=\"firsttranslation1\" data-timestamp=\"2025-09-01T11:38:45+02:00\"><img class=\"internal-tag single\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2226%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22\\'Open%20Sans\\',%20\\'Helvetica%20Neue\\',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E-40%3C/text%3E%3C/svg%3E\" alt=\"-40\" id=\"tag-image-internal-tag single6\" title=\"<6/> CP: default simple\" data-length=\"3\" data-pixellength=\"0\" data-tag-number=\"6\"></ins>");
    });

    test('spellcheck containing only tags is split in the at first', () => {
        document.body.innerHTML = `<span class="t5spellcheck t5misspelling ownttip" data-spellcheck-activematchindex="8" data-qtip="&lt;b&gt;Press Ctrl+R&lt;/b&gt; to select proposals or right-click this word. Cursor has to be inside error."><img class="internal-tag open" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2230%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;6&amp;gt;%3C/text%3E%3C/svg%3E" alt="&lt;bpt id=&quot;6&quot; ctype=&quot;x-dnt&quot;&gt;&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_5d541fd9f029c45bd55f8914241227f1&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;&lt;/bpt&gt;" id="tag-image-internal-tag open6" title="&lt;bpt id=&quot;6&quot; ctype=&quot;x-dnt&quot;&gt;&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_5d541fd9f029c45bd55f8914241227f1&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;&lt;/bpt&gt;" data-length="-1" data-pixellength="0" data-tag-number="6"><img class="internal-tag open" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2230%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;8&amp;gt;%3C/text%3E%3C/svg%3E" alt="&lt;bpt id=&quot;8&quot; ctype=&quot;x-dnt&quot;&gt;&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_4b0a39baf029c45bd55f89143e651182&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;&lt;/bpt&gt;" id="tag-image-internal-tag open8" title="&lt;bpt id=&quot;8&quot; ctype=&quot;x-dnt&quot;&gt;&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_4b0a39baf029c45bd55f89143e651182&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;&lt;/bpt&gt;" data-length="-1" data-pixellength="0" data-tag-number="8"><img class="internal-tag single" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;7/&amp;gt;%3C/text%3E%3C/svg%3E" alt="DGC-...-GF/-KF/-FA" id="tag-image-internal-tag single7" title="DGC-...-GF/-KF/-FA" data-length="-1" data-pixellength="0" data-tag-number="7"><img class="internal-tag close" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;/6&amp;gt;%3C/text%3E%3C/svg%3E" alt="&lt;ept id=&quot;6&quot;>&amp;lt;/dnt&amp;gt;&lt;/ept&gt;" id="tag-image-internal-tag close6" title="&lt;ept id=&quot;6&quot;>&amp;lt;/dnt&amp;gt;&lt;/ept&gt;" data-length="-1" data-pixellength="0" data-tag-number="6"> </span>`;
        const div = document.querySelector('span');
        const [left, right] = splitNode(div, 1);
        expect(left.outerHTML).toBe("<span class=\"t5spellcheck t5misspelling ownttip\" data-spellcheck-activematchindex=\"8\" data-qtip=\"<b>Press Ctrl+R</b> to select proposals or right-click this word. Cursor has to be inside error.\"><img class=\"internal-tag open\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2230%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;6&amp;gt;%3C/text%3E%3C/svg%3E\" alt=\"<bpt id=&quot;6&quot; ctype=&quot;x-dnt&quot;>&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_5d541fd9f029c45bd55f8914241227f1&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;</bpt>\" id=\"tag-image-internal-tag open6\" title=\"<bpt id=&quot;6&quot; ctype=&quot;x-dnt&quot;>&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_5d541fd9f029c45bd55f8914241227f1&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;</bpt>\" data-length=\"-1\" data-pixellength=\"0\" data-tag-number=\"6\"></span>");
        expect(right.outerHTML).toBe("<span class=\"t5spellcheck t5misspelling ownttip\" data-spellcheck-activematchindex=\"8\" data-qtip=\"<b>Press Ctrl+R</b> to select proposals or right-click this word. Cursor has to be inside error.\"><img class=\"internal-tag open\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2230%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;8&amp;gt;%3C/text%3E%3C/svg%3E\" alt=\"<bpt id=&quot;8&quot; ctype=&quot;x-dnt&quot;>&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_4b0a39baf029c45bd55f89143e651182&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;</bpt>\" id=\"tag-image-internal-tag open8\" title=\"<bpt id=&quot;8&quot; ctype=&quot;x-dnt&quot;>&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_4b0a39baf029c45bd55f89143e651182&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;</bpt>\" data-length=\"-1\" data-pixellength=\"0\" data-tag-number=\"8\"><img class=\"internal-tag single\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;7/&amp;gt;%3C/text%3E%3C/svg%3E\" alt=\"DGC-...-GF/-KF/-FA\" id=\"tag-image-internal-tag single7\" title=\"DGC-...-GF/-KF/-FA\" data-length=\"-1\" data-pixellength=\"0\" data-tag-number=\"7\"><img class=\"internal-tag close\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;/6&amp;gt;%3C/text%3E%3C/svg%3E\" alt=\"<ept id=&quot;6&quot;>&amp;lt;/dnt&amp;gt;</ept>\" id=\"tag-image-internal-tag close6\" title=\"<ept id=&quot;6&quot;>&amp;lt;/dnt&amp;gt;</ept>\" data-length=\"-1\" data-pixellength=\"0\" data-tag-number=\"6\"> </span>");
    });

    test('spellcheck containing only tags is split at second', () => {
        document.body.innerHTML = `<span class="t5spellcheck t5misspelling ownttip" data-spellcheck-activematchindex="8" data-qtip="&lt;b&gt;Press Ctrl+R&lt;/b&gt; to select proposals or right-click this word. Cursor has to be inside error."><img class="internal-tag open" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2230%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;6&amp;gt;%3C/text%3E%3C/svg%3E" alt="&lt;bpt id=&quot;6&quot; ctype=&quot;x-dnt&quot;&gt;&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_5d541fd9f029c45bd55f8914241227f1&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;&lt;/bpt&gt;" id="tag-image-internal-tag open6" title="&lt;bpt id=&quot;6&quot; ctype=&quot;x-dnt&quot;&gt;&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_5d541fd9f029c45bd55f8914241227f1&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;&lt;/bpt&gt;" data-length="-1" data-pixellength="0" data-tag-number="6"><img class="internal-tag open" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2230%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;8&amp;gt;%3C/text%3E%3C/svg%3E" alt="&lt;bpt id=&quot;8&quot; ctype=&quot;x-dnt&quot;&gt;&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_4b0a39baf029c45bd55f89143e651182&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;&lt;/bpt&gt;" id="tag-image-internal-tag open8" title="&lt;bpt id=&quot;8&quot; ctype=&quot;x-dnt&quot;&gt;&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_4b0a39baf029c45bd55f89143e651182&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;&lt;/bpt&gt;" data-length="-1" data-pixellength="0" data-tag-number="8"><img class="internal-tag single" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;7/&amp;gt;%3C/text%3E%3C/svg%3E" alt="DGC-...-GF/-KF/-FA" id="tag-image-internal-tag single7" title="DGC-...-GF/-KF/-FA" data-length="-1" data-pixellength="0" data-tag-number="7"><img class="internal-tag close" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;/6&amp;gt;%3C/text%3E%3C/svg%3E" alt="&lt;ept id=&quot;6&quot;>&amp;lt;/dnt&amp;gt;&lt;/ept&gt;" id="tag-image-internal-tag close6" title="&lt;ept id=&quot;6&quot;>&amp;lt;/dnt&amp;gt;&lt;/ept&gt;" data-length="-1" data-pixellength="0" data-tag-number="6"> </span>`;
        const div = document.querySelector('span');
        const [left, right] = splitNode(div, 2);
        expect(left.outerHTML).toBe("<span class=\"t5spellcheck t5misspelling ownttip\" data-spellcheck-activematchindex=\"8\" data-qtip=\"<b>Press Ctrl+R</b> to select proposals or right-click this word. Cursor has to be inside error.\"><img class=\"internal-tag open\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2230%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;6&amp;gt;%3C/text%3E%3C/svg%3E\" alt=\"<bpt id=&quot;6&quot; ctype=&quot;x-dnt&quot;>&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_5d541fd9f029c45bd55f8914241227f1&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;</bpt>\" id=\"tag-image-internal-tag open6\" title=\"<bpt id=&quot;6&quot; ctype=&quot;x-dnt&quot;>&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_5d541fd9f029c45bd55f8914241227f1&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;</bpt>\" data-length=\"-1\" data-pixellength=\"0\" data-tag-number=\"6\"><img class=\"internal-tag open\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2230%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;8&amp;gt;%3C/text%3E%3C/svg%3E\" alt=\"<bpt id=&quot;8&quot; ctype=&quot;x-dnt&quot;>&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_4b0a39baf029c45bd55f89143e651182&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;</bpt>\" id=\"tag-image-internal-tag open8\" title=\"<bpt id=&quot;8&quot; ctype=&quot;x-dnt&quot;>&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_4b0a39baf029c45bd55f89143e651182&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;</bpt>\" data-length=\"-1\" data-pixellength=\"0\" data-tag-number=\"8\"></span>");
        expect(right.outerHTML).toBe("<span class=\"t5spellcheck t5misspelling ownttip\" data-spellcheck-activematchindex=\"8\" data-qtip=\"<b>Press Ctrl+R</b> to select proposals or right-click this word. Cursor has to be inside error.\"><img class=\"internal-tag single\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;7/&amp;gt;%3C/text%3E%3C/svg%3E\" alt=\"DGC-...-GF/-KF/-FA\" id=\"tag-image-internal-tag single7\" title=\"DGC-...-GF/-KF/-FA\" data-length=\"-1\" data-pixellength=\"0\" data-tag-number=\"7\"><img class=\"internal-tag close\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;/6&amp;gt;%3C/text%3E%3C/svg%3E\" alt=\"<ept id=&quot;6&quot;>&amp;lt;/dnt&amp;gt;</ept>\" id=\"tag-image-internal-tag close6\" title=\"<ept id=&quot;6&quot;>&amp;lt;/dnt&amp;gt;</ept>\" data-length=\"-1\" data-pixellength=\"0\" data-tag-number=\"6\"> </span>");
    });

    test('spellcheck containing only tags is split at third', () => {
        document.body.innerHTML = `<span class="t5spellcheck t5misspelling ownttip" data-spellcheck-activematchindex="8" data-qtip="&lt;b&gt;Press Ctrl+R&lt;/b&gt; to select proposals or right-click this word. Cursor has to be inside error."><img class="internal-tag open" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2230%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;6&amp;gt;%3C/text%3E%3C/svg%3E" alt="&lt;bpt id=&quot;6&quot; ctype=&quot;x-dnt&quot;&gt;&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_5d541fd9f029c45bd55f8914241227f1&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;&lt;/bpt&gt;" id="tag-image-internal-tag open6" title="&lt;bpt id=&quot;6&quot; ctype=&quot;x-dnt&quot;&gt;&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_5d541fd9f029c45bd55f8914241227f1&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;&lt;/bpt&gt;" data-length="-1" data-pixellength="0" data-tag-number="6"><img class="internal-tag open" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2230%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;8&amp;gt;%3C/text%3E%3C/svg%3E" alt="&lt;bpt id=&quot;8&quot; ctype=&quot;x-dnt&quot;&gt;&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_4b0a39baf029c45bd55f89143e651182&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;&lt;/bpt&gt;" id="tag-image-internal-tag open8" title="&lt;bpt id=&quot;8&quot; ctype=&quot;x-dnt&quot;&gt;&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_4b0a39baf029c45bd55f89143e651182&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;&lt;/bpt&gt;" data-length="-1" data-pixellength="0" data-tag-number="8"><img class="internal-tag single" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;7/&amp;gt;%3C/text%3E%3C/svg%3E" alt="DGC-...-GF/-KF/-FA" id="tag-image-internal-tag single7" title="DGC-...-GF/-KF/-FA" data-length="-1" data-pixellength="0" data-tag-number="7"><img class="internal-tag close" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;/6&amp;gt;%3C/text%3E%3C/svg%3E" alt="&lt;ept id=&quot;6&quot;>&amp;lt;/dnt&amp;gt;&lt;/ept&gt;" id="tag-image-internal-tag close6" title="&lt;ept id=&quot;6&quot;>&amp;lt;/dnt&amp;gt;&lt;/ept&gt;" data-length="-1" data-pixellength="0" data-tag-number="6"> </span>`;
        const div = document.querySelector('span');
        const [left, right] = splitNode(div, 3);
        expect(left.outerHTML).toBe("<span class=\"t5spellcheck t5misspelling ownttip\" data-spellcheck-activematchindex=\"8\" data-qtip=\"<b>Press Ctrl+R</b> to select proposals or right-click this word. Cursor has to be inside error.\"><img class=\"internal-tag open\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2230%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;6&amp;gt;%3C/text%3E%3C/svg%3E\" alt=\"<bpt id=&quot;6&quot; ctype=&quot;x-dnt&quot;>&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_5d541fd9f029c45bd55f8914241227f1&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;</bpt>\" id=\"tag-image-internal-tag open6\" title=\"<bpt id=&quot;6&quot; ctype=&quot;x-dnt&quot;>&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_5d541fd9f029c45bd55f8914241227f1&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;</bpt>\" data-length=\"-1\" data-pixellength=\"0\" data-tag-number=\"6\"><img class=\"internal-tag open\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2230%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;8&amp;gt;%3C/text%3E%3C/svg%3E\" alt=\"<bpt id=&quot;8&quot; ctype=&quot;x-dnt&quot;>&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_4b0a39baf029c45bd55f89143e651182&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;</bpt>\" id=\"tag-image-internal-tag open8\" title=\"<bpt id=&quot;8&quot; ctype=&quot;x-dnt&quot;>&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_4b0a39baf029c45bd55f89143e651182&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;</bpt>\" data-length=\"-1\" data-pixellength=\"0\" data-tag-number=\"8\"><img class=\"internal-tag single\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;7/&amp;gt;%3C/text%3E%3C/svg%3E\" alt=\"DGC-...-GF/-KF/-FA\" id=\"tag-image-internal-tag single7\" title=\"DGC-...-GF/-KF/-FA\" data-length=\"-1\" data-pixellength=\"0\" data-tag-number=\"7\"></span>");
        expect(right.outerHTML).toBe("<span class=\"t5spellcheck t5misspelling ownttip\" data-spellcheck-activematchindex=\"8\" data-qtip=\"<b>Press Ctrl+R</b> to select proposals or right-click this word. Cursor has to be inside error.\"><img class=\"internal-tag close\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;/6&amp;gt;%3C/text%3E%3C/svg%3E\" alt=\"<ept id=&quot;6&quot;>&amp;lt;/dnt&amp;gt;</ept>\" id=\"tag-image-internal-tag close6\" title=\"<ept id=&quot;6&quot;>&amp;lt;/dnt&amp;gt;</ept>\" data-length=\"-1\" data-pixellength=\"0\" data-tag-number=\"6\"> </span>");
    });

    test('spellcheck containing only tags is split at fourth', () => {
        document.body.innerHTML = `<span class="t5spellcheck t5misspelling ownttip" data-spellcheck-activematchindex="8" data-qtip="&lt;b&gt;Press Ctrl+R&lt;/b&gt; to select proposals or right-click this word. Cursor has to be inside error."><img class="internal-tag open" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2230%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;6&amp;gt;%3C/text%3E%3C/svg%3E" alt="&lt;bpt id=&quot;6&quot; ctype=&quot;x-dnt&quot;&gt;&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_5d541fd9f029c45bd55f8914241227f1&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;&lt;/bpt&gt;" id="tag-image-internal-tag open6" title="&lt;bpt id=&quot;6&quot; ctype=&quot;x-dnt&quot;&gt;&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_5d541fd9f029c45bd55f8914241227f1&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;&lt;/bpt&gt;" data-length="-1" data-pixellength="0" data-tag-number="6"><img class="internal-tag open" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2230%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;8&amp;gt;%3C/text%3E%3C/svg%3E" alt="&lt;bpt id=&quot;8&quot; ctype=&quot;x-dnt&quot;&gt;&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_4b0a39baf029c45bd55f89143e651182&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;&lt;/bpt&gt;" id="tag-image-internal-tag open8" title="&lt;bpt id=&quot;8&quot; ctype=&quot;x-dnt&quot;&gt;&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_4b0a39baf029c45bd55f89143e651182&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;&lt;/bpt&gt;" data-length="-1" data-pixellength="0" data-tag-number="8"><img class="internal-tag single" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;7/&amp;gt;%3C/text%3E%3C/svg%3E" alt="DGC-...-GF/-KF/-FA" id="tag-image-internal-tag single7" title="DGC-...-GF/-KF/-FA" data-length="-1" data-pixellength="0" data-tag-number="7"><img class="internal-tag close" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;/6&amp;gt;%3C/text%3E%3C/svg%3E" alt="&lt;ept id=&quot;6&quot;>&amp;lt;/dnt&amp;gt;&lt;/ept&gt;" id="tag-image-internal-tag close6" title="&lt;ept id=&quot;6&quot;>&amp;lt;/dnt&amp;gt;&lt;/ept&gt;" data-length="-1" data-pixellength="0" data-tag-number="6"> </span>`;
        const div = document.querySelector('span');
        const [left, right] = splitNode(div, 4);
        expect(left.outerHTML).toBe("<span class=\"t5spellcheck t5misspelling ownttip\" data-spellcheck-activematchindex=\"8\" data-qtip=\"<b>Press Ctrl+R</b> to select proposals or right-click this word. Cursor has to be inside error.\"><img class=\"internal-tag open\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2230%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;6&amp;gt;%3C/text%3E%3C/svg%3E\" alt=\"<bpt id=&quot;6&quot; ctype=&quot;x-dnt&quot;>&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_5d541fd9f029c45bd55f8914241227f1&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;</bpt>\" id=\"tag-image-internal-tag open6\" title=\"<bpt id=&quot;6&quot; ctype=&quot;x-dnt&quot;>&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_5d541fd9f029c45bd55f8914241227f1&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;</bpt>\" data-length=\"-1\" data-pixellength=\"0\" data-tag-number=\"6\"><img class=\"internal-tag open\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2230%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;8&amp;gt;%3C/text%3E%3C/svg%3E\" alt=\"<bpt id=&quot;8&quot; ctype=&quot;x-dnt&quot;>&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_4b0a39baf029c45bd55f89143e651182&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;</bpt>\" id=\"tag-image-internal-tag open8\" title=\"<bpt id=&quot;8&quot; ctype=&quot;x-dnt&quot;>&amp;lt;dnt y.commentable=&amp;quot;true&amp;quot; y.id=&amp;quot;ID_4b0a39baf029c45bd55f89143e651182&amp;quot; y.linktarget=&amp;quot;true&amp;quot;&amp;gt;</bpt>\" data-length=\"-1\" data-pixellength=\"0\" data-tag-number=\"8\"><img class=\"internal-tag single\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;7/&amp;gt;%3C/text%3E%3C/svg%3E\" alt=\"DGC-...-GF/-KF/-FA\" id=\"tag-image-internal-tag single7\" title=\"DGC-...-GF/-KF/-FA\" data-length=\"-1\" data-pixellength=\"0\" data-tag-number=\"7\"><img class=\"internal-tag close\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;/6&amp;gt;%3C/text%3E%3C/svg%3E\" alt=\"<ept id=&quot;6&quot;>&amp;lt;/dnt&amp;gt;</ept>\" id=\"tag-image-internal-tag close6\" title=\"<ept id=&quot;6&quot;>&amp;lt;/dnt&amp;gt;</ept>\" data-length=\"-1\" data-pixellength=\"0\" data-tag-number=\"6\"></span>");
        expect(right.outerHTML).toBe("<span class=\"t5spellcheck t5misspelling ownttip\" data-spellcheck-activematchindex=\"8\" data-qtip=\"<b>Press Ctrl+R</b> to select proposals or right-click this word. Cursor has to be inside error.\"> </span>");
    });

    test('position is out of bounds', () => {
        document.body.innerHTML = `<ins class="trackchanges ownttip" data-usertrackingid="67914" data-usercssnr="usernr1" data-workflowstep="firsttranslation1" data-timestamp="2025-09-01T11:38:45+02:00"><img class="internal-tag single" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22\\'Open%20Sans\\',%20\\'Helvetica%20Neue\\',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;3/&amp;gt;%3C/text%3E%3C/svg%3E" alt="…" id="tag-image-internal-tag single3" title="…" data-length="-1" data-pixellength="0" data-tag-number="3"><img class="internal-tag single" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2226%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22\\'Open%20Sans\\',%20\\'Helvetica%20Neue\\',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E-40%3C/text%3E%3C/svg%3E" alt="-40" id="tag-image-internal-tag single6" title="&lt;6/&gt; CP: default simple" data-length="3" data-pixellength="0" data-tag-number="6"></ins>`;
        const div = document.querySelector('ins');
        const [left, right] = splitNode(div, 100);
        expect(left.outerHTML).toBe("<ins class=\"trackchanges ownttip\" data-usertrackingid=\"67914\" data-usercssnr=\"usernr1\" data-workflowstep=\"firsttranslation1\" data-timestamp=\"2025-09-01T11:38:45+02:00\"><img class=\"internal-tag single\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22\\'Open%20Sans\\',%20\\'Helvetica%20Neue\\',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;3/&amp;gt;%3C/text%3E%3C/svg%3E\" alt=\"…\" id=\"tag-image-internal-tag single3\" title=\"…\" data-length=\"-1\" data-pixellength=\"0\" data-tag-number=\"3\"><img class=\"internal-tag single\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2226%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22\\'Open%20Sans\\',%20\\'Helvetica%20Neue\\',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E-40%3C/text%3E%3C/svg%3E\" alt=\"-40\" id=\"tag-image-internal-tag single6\" title=\"<6/> CP: default simple\" data-length=\"3\" data-pixellength=\"0\" data-tag-number=\"6\"></ins>");
        expect(right).toBe(null);
    });

    test('insertion with mixed content - text and tags', () => {
        document.body.innerHTML = `<ins class="trackchanges ownttip" data-usertrackingid="3786" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2025-08-27T12:56:05+03:00">` +
            `<img class="internal-tag single whitespace newline" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2210%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E%E2%86%B5%3C/text%3E%3C/svg%3E" alt="↵" id="tag-image-whitespace2" title="&lt;2/&gt;: Newline" data-length="1" data-pixellength="0" data-tag-number="2">` +
            `Wi` +
            `<span class="t5spellcheck t5misspelling ownttip" data-spellcheck-activematchindex="1" data-qtip="&lt;b&gt;Press Ctrl+R&lt;/b&gt; to select proposals or right-click this word. Cursor has to be inside error.">e S</span>` +
            `ie ` +
            `<img class="internal-tag single whitespace" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%22208%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%3C/text%3E%3C/svg%3E" alt="→→→→→→→→→→→→" id="tag-image-whitespace3" title="&lt;3/&gt;: 12 tab characters" data-length="12" data-pixellength="0" data-tag-number="3">` +
            `<img class="internal-tag open" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2230%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;1&amp;gt;%3C/text%3E%3C/svg%3E" id="tag-image-internal-tag open1" data-length="-1" data-pixellength="0" data-tag-number="1">` +
            `dies` +
            `<span class="t5spellcheck t5misspelling ownttip" data-spellcheck-activematchindex="3" data-qtip="&lt;b&gt;Press Ctrl+R&lt;/b&gt; to select proposals or right-click this word. Cursor has to be inside error.">e Einstellung</span>` +
            `e<span class="t5spellcheck t5misspelling ownttip" data-spellcheck-activematchindex="4" data-qtip="&lt;b&gt;Press Ctrl+R&lt;/b&gt; to select proposals or right-click this word. Cursor has to be inside error.">n f</span>` +
            `ür de<span class="t5spellcheck t5misspelling ownttip" data-spellcheck-activematchindex="5" data-qtip="&lt;b&gt;Press Ctrl+R&lt;/b&gt; to select proposals or right-click this word. Cursor has to be inside error.">n Leseberei</span>` +
            `c<span class="t5spellcheck t5misspelling ownttip" data-spellcheck-activematchindex="6" data-qtip="&lt;b&gt;Press Ctrl+R&lt;/b&gt; to select proposals or right-click this word. Cursor has to be inside error.">h vornehm</span>` +
            `en<span class="t5spellcheck t5misspelling ownttip" data-spellcheck-activematchindex="7" data-qtip="&lt;b&gt;Press Ctrl+R&lt;/b&gt; to select proposals or right-click this word. Cursor has to be inside error.">, erfahr</span>` +
            `e<span class="t5spellcheck t5misspelling ownttip" data-spellcheck-activematchindex="8" data-qtip="&lt;b&gt;Press Ctrl+R&lt;/b&gt; to select proposals or right-click this word. Cursor has to be inside error.">n S</span>` +
            `ie i<span class="t5spellcheck t5misspelling ownttip" data-spellcheck-activematchindex="9" data-qtip="&lt;b&gt;Press Ctrl+R&lt;/b&gt; to select proposals or right-click this word. Cursor has to be inside error.">n dies</span>` +
            `er Lerneinheit.<img class="internal-tag close" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;/1&amp;gt;%3C/text%3E%3C/svg%3E" alt="&lt;/g&gt;" id="tag-image-internal-tag close1" title="&lt;/g&gt;" data-length="-1" data-pixellength="0" data-tag-number="1">` +
            `<img class="internal-tag single whitespace newline" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2210%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E%E2%86%B5%3C/text%3E%3C/svg%3E" alt="↵" id="tag-image-whitespace4" title="&lt;4/&gt;: Newline" data-length="1" data-pixellength="0" data-tag-number="4">` +
            `<img class="internal-tag single whitespace" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%22191%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%3C/text%3E%3C/svg%3E" alt="→→→→→→→→→→→" id="tag-image-whitespace5" title="&lt;5/&gt;: 11 tab characters" data-length="11" data-pixellength="0" data-tag-number="5">` +
            `</ins>`;
        const div = document.querySelector('ins');
        const [left, right] = splitNode(div, 10);
        expect(left.outerHTML).toBe(
            `<ins class="trackchanges ownttip" data-usertrackingid="3786" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2025-08-27T12:56:05+03:00">` +
            `<img class="internal-tag single whitespace newline" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2210%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E%E2%86%B5%3C/text%3E%3C/svg%3E" alt="↵" id="tag-image-whitespace2" title="<2/>: Newline" data-length="1" data-pixellength="0" data-tag-number="2">` +
            `Wi` +
            `<span class="t5spellcheck t5misspelling ownttip" data-spellcheck-activematchindex="1" data-qtip="<b>Press Ctrl+R</b> to select proposals or right-click this word. Cursor has to be inside error.">e S</span>` +
            `ie ` +
            `<img class="internal-tag single whitespace" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%22208%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%3C/text%3E%3C/svg%3E" alt="→→→→→→→→→→→→" id="tag-image-whitespace3" title="<3/>: 12 tab characters" data-length="12" data-pixellength="0" data-tag-number="3">` +
            `</ins>`
        );

        const expectedRight = document.createElement('div');
        expectedRight.innerHTML = `<ins class="trackchanges ownttip" data-usertrackingid="3786" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2025-08-27T12:56:05+03:00">` +
            `<img class="internal-tag open" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2230%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;1&amp;gt;%3C/text%3E%3C/svg%3E" id="tag-image-internal-tag open1" data-length="-1" data-pixellength="0" data-tag-number="1">` +
            `dies` +
            `<span class="t5spellcheck t5misspelling ownttip" data-spellcheck-activematchindex="3" data-qtip="<b>Press Ctrl+R</b> to select proposals or right-click this word. Cursor has to be inside error.">e Einstellung</span>` +
            `e<span class="t5spellcheck t5misspelling ownttip" data-spellcheck-activematchindex="4" data-qtip="<b>Press Ctrl+R</b> to select proposals or right-click this word. Cursor has to be inside error.">n f</span>` +
            `ür de<span class="t5spellcheck t5misspelling ownttip" data-spellcheck-activematchindex="5" data-qtip="<b>Press Ctrl+R</b> to select proposals or right-click this word. Cursor has to be inside error.">n Leseberei</span>` +
            `c<span class="t5spellcheck t5misspelling ownttip" data-spellcheck-activematchindex="6" data-qtip="<b>Press Ctrl+R</b> to select proposals or right-click this word. Cursor has to be inside error.">h vornehm</span>` +
            `en<span class="t5spellcheck t5misspelling ownttip" data-spellcheck-activematchindex="7" data-qtip="<b>Press Ctrl+R</b> to select proposals or right-click this word. Cursor has to be inside error.">, erfahr</span>` +
            `e<span class="t5spellcheck t5misspelling ownttip" data-spellcheck-activematchindex="8" data-qtip="<b>Press Ctrl+R</b> to select proposals or right-click this word. Cursor has to be inside error.">n S</span>` +
            `ie i<span class="t5spellcheck t5misspelling ownttip" data-spellcheck-activematchindex="9" data-qtip="<b>Press Ctrl+R</b> to select proposals or right-click this word. Cursor has to be inside error.">n dies</span>` +
            `er Lerneinheit.<img class="internal-tag close" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2236%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E&amp;lt;/1&amp;gt;%3C/text%3E%3C/svg%3E" alt="</g>" id="tag-image-internal-tag close1" title="</g>" data-length="-1" data-pixellength="0" data-tag-number="1">` +
            `<img class="internal-tag single whitespace newline" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%2210%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E%E2%86%B5%3C/text%3E%3C/svg%3E" alt="↵" id="tag-image-whitespace4" title="<4/>: Newline" data-length="1" data-pixellength="0" data-tag-number="4">` +
            `<img class="internal-tag single whitespace" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20height=%2223%22%20width=%22191%22%3E%3Crect%20width=%22100%25%22%20height=%22100%25%22%20fill=%22rgb(207,207,207)%22%20rx=%223%22%20ry=%223%22/%3E%3Ctext%20x=%221%22%20y=%2218%22%20font-size=%2217.3333px%22%20font-weight=%22300%22%20font-family=%22'Open%20Sans',%20'Helvetica%20Neue',%20helvetica,%20arial,%20verdana,%20sans-serif%22%3E%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%E2%86%92%3C/text%3E%3C/svg%3E" alt="→→→→→→→→→→→" id="tag-image-whitespace5" title="<5/>: 11 tab characters" data-length="11" data-pixellength="0" data-tag-number="5">` +
            `</ins>`;
        expect(right).toStrictEqual(
            expectedRight.firstChild
        );
    });
});

describe('splitNodeByChild', () => {
    test('split ins node with nested del in the middle', () => {
        // Setup: <ins>before<del>nested</del>after</ins>
        document.body.innerHTML = `
            <ins class="trackchanges ownttip" data-usertrackingid="123">
                before text
                <del class="trackchanges ownttip deleted" data-usertrackingid="456">nested deletion</del>
                after text
            </ins>
        `;

        const parentNode = document.querySelector('ins');
        const childNode = document.querySelector('del');

        const [before, after] = splitNodeByChild(parentNode, childNode);

        // Should return before and after nodes
        expect(before).not.toBeNull();
        expect(after).not.toBeNull();

        // Before node should contain only "before text"
        expect(before.tagName).toBe('INS');
        expect(before.textContent.trim()).toBe('before text');
        expect(before.className).toBe('trackchanges ownttip');

        // After node should contain only "after text"
        expect(after.tagName).toBe('INS');
        expect(after.textContent.trim()).toBe('after text');
        expect(after.className).toBe('trackchanges ownttip');
    });

    test('split ins node with nested del at the beginning', () => {
        // Setup: <ins><del>nested</del>after</ins>
        document.body.innerHTML =
            `<ins class="trackchanges ownttip" data-usertrackingid="123">`
                + `<del class="trackchanges ownttip deleted" data-usertrackingid="456">nested deletion</del>`
                + `after text`
            +`</ins>`;

        const parentNode = document.querySelector('ins');
        const childNode = document.querySelector('del');

        const [before, after] = splitNodeByChild(parentNode, childNode);

        // Before should be null (no content before child)
        expect(before).toBeNull();

        // After node should contain "after text"
        expect(after).not.toBeNull();
        expect(after.tagName).toBe('INS');
        expect(after.textContent.trim()).toBe('after text');
    });

    test('split ins node with nested del at the end', () => {
        // Setup: <ins>before<del>nested</del></ins>
        document.body.innerHTML =
            `<ins class="trackchanges ownttip" data-usertrackingid="123">`
                + `before text`
                + `<del class="trackchanges ownttip deleted" data-usertrackingid="456">nested deletion</del>`
            + `</ins>`;

        const parentNode = document.querySelector('ins');
        const childNode = document.querySelector('del');

        const [before, after] = splitNodeByChild(parentNode, childNode);

        // Before node should contain "before text"
        expect(before).not.toBeNull();
        expect(before.tagName).toBe('INS');
        expect(before.textContent.trim()).toBe('before text');

        // After should be null (no content after child)
        expect(after).toBeNull();
    });

    test('split ins node with only nested del', () => {
        // Setup: <ins><del>only content</del></ins>
        document.body.innerHTML =
            `<ins class="trackchanges ownttip" data-usertrackingid="123">`
                + `<del class="trackchanges ownttip deleted" data-usertrackingid="456">only content</del>`
            + `</ins>`;

        const parentNode = document.querySelector('ins');
        const childNode = document.querySelector('del');

        const [before, after] = splitNodeByChild(parentNode, childNode);

        // Both should be null (no content except the child)
        expect(before).toBeNull();
        expect(after).toBeNull();
    });

    test('split ins with nested del and multiple text nodes', () => {
        // Setup with multiple text nodes and elements
        document.body.innerHTML = `
            <ins class="trackchanges ownttip" data-usertrackingid="123">
                text1
                <span class="term">span text</span>
                text2
                <del class="trackchanges ownttip deleted" data-usertrackingid="456">deletion</del>
                text3
                <span class="term">another span text</span>
                text4
            </ins>
        `;

        const parentNode = document.querySelector('ins');
        const childNode = document.querySelector('del');

        const [before, after] = splitNodeByChild(parentNode, childNode);

        // Before should contain text1, span, text2
        expect(before).not.toBeNull();
        expect(before.querySelector('span')).not.toBeNull();
        expect(before.textContent).toContain('text1');
        expect(before.textContent).toContain('span text');
        expect(before.textContent).toContain('text2');
        expect(before.textContent).not.toContain('deletion');

        // After should contain text3, strong, text4
        expect(after).not.toBeNull();
        expect(after.querySelector('.term')).not.toBeNull();
        expect(after.textContent).toContain('text3');
        expect(after.textContent).toContain('another span text');
        expect(after.textContent).toContain('text4');
        expect(after.textContent).not.toContain('deletion');
    });

    test('split preserves parent attributes', () => {
        document.body.innerHTML = `
            <ins class="trackchanges ownttip"
                 data-usertrackingid="123"
                 data-timestamp="2025-12-09T10:00:00"
                 data-usercssnr="usernr1">
                before
                <del class="trackchanges ownttip deleted">nested</del>
                after
            </ins>
        `;

        const parentNode = document.querySelector('ins');
        const childNode = document.querySelector('del');

        const [before, after] = splitNodeByChild(parentNode, childNode);

        // Both nodes should preserve parent attributes
        expect(before.getAttribute('data-usertrackingid')).toBe('123');
        expect(before.getAttribute('data-timestamp')).toBe('2025-12-09T10:00:00');
        expect(before.getAttribute('data-usercssnr')).toBe('usernr1');

        expect(after.getAttribute('data-usertrackingid')).toBe('123');
        expect(after.getAttribute('data-timestamp')).toBe('2025-12-09T10:00:00');
        expect(after.getAttribute('data-usercssnr')).toBe('usernr1');
    });

    test('split with internal tags (img elements)', () => {
        document.body.innerHTML = `
            <ins class="trackchanges ownttip">
                <img class="internal-tag open" src="tag1.svg" data-tag-number="1"/>
                text before
                <del class="trackchanges ownttip deleted">deleted content</del>
                text after
                <img class="internal-tag close" src="tag2.svg" data-tag-number="2"/>
            </ins>
        `;

        const parentNode = document.querySelector('ins');
        const childNode = document.querySelector('del');

        const [before, after] = splitNodeByChild(parentNode, childNode);

        // Before should contain first img and text
        expect(before).not.toBeNull();
        expect(before.querySelectorAll('img').length).toBe(1);
        expect(before.querySelector('img').getAttribute('data-tag-number')).toBe('1');
        expect(before.textContent).toContain('text before');

        // After should contain last img and text
        expect(after).not.toBeNull();
        expect(after.querySelectorAll('img').length).toBe(1);
        expect(after.querySelector('img').getAttribute('data-tag-number')).toBe('2');
        expect(after.textContent).toContain('text after');
    });

    test('returns undefined when child not found', () => {
        document.body.innerHTML = `
            <ins class="trackchanges ownttip">
                some content
            </ins>
            <del class="trackchanges ownttip deleted">separate deletion</del>
        `;

        const parentNode = document.querySelector('ins');
        const childNode = document.querySelector('del'); // This del is not a child of ins

        const [before, after] = splitNodeByChild(parentNode, childNode);

        // Should return undefined when child is not found in parent
        expect(before).toBeNull();
        expect(after).toBeNull();
    });

    test('split with whitespace preservation', () => {
        document.body.innerHTML = `
            <ins class="trackchanges ownttip">
                    text with spaces
                <del class="trackchanges ownttip deleted">   nested   </del>
                    more spaces
            </ins>
        `;

        const parentNode = document.querySelector('ins');
        const childNode = document.querySelector('del');

        const [before, after] = splitNodeByChild(parentNode, childNode);

        // Whitespace should be preserved
        expect(before).not.toBeNull();
        expect(before.textContent).toContain('text with spaces');

        expect(after).not.toBeNull();
        expect(after.textContent).toContain('more spaces');
    });

    test('split with identical sibling dels - finds correct one', () => {
        document.body.innerHTML = `
            <ins class="trackchanges ownttip">
                before
                <del class="trackchanges ownttip deleted" data-timestamp="2025-12-09T10:00:00">first deletion</del>
                middle
                <del class="trackchanges ownttip deleted" data-timestamp="2025-12-09T11:00:00">second deletion</del>
                after
            </ins>
        `;

        const parentNode = document.querySelector('ins');
        const allDels = document.querySelectorAll('del');
        const secondDel = allDels[1]; // Target the second del

        const [before, after] = splitNodeByChild(parentNode, secondDel);

        // Before should contain "before", first del, and "middle"
        expect(before).not.toBeNull();
        expect(before.textContent).toContain('before');
        expect(before.textContent).toContain('first deletion');
        expect(before.textContent).toContain('middle');
        expect(before.textContent).not.toContain('second deletion');

        // After should contain "after"
        expect(after).not.toBeNull();
        expect(after.textContent.trim()).toBe('after');
        expect(after.textContent).not.toContain('second deletion');
    });

    test('split with empty text nodes', () => {
        const parentNode = document.createElement('ins');
        parentNode.className = 'trackchanges ownttip';

        parentNode.appendChild(document.createTextNode(''));
        parentNode.appendChild(document.createTextNode('before'));

        const delNode = document.createElement('del');
        delNode.textContent = 'deletion';
        delNode.className = 'trackchanges ownttip deleted';
        parentNode.appendChild(delNode);

        parentNode.appendChild(document.createTextNode(''));
        parentNode.appendChild(document.createTextNode('after'));

        document.body.innerHTML = '';
        document.body.appendChild(parentNode);

        const [before, after] = splitNodeByChild(parentNode, delNode);

        expect(before).not.toBeNull();
        expect(before.textContent.trim()).toBe('before');

        expect(after).not.toBeNull();
        expect(after.textContent.trim()).toBe('after');
    });
});
