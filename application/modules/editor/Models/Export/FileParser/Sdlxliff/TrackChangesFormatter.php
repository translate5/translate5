<?php

declare(strict_types=1);

class editor_Models_Export_FileParser_Sdlxliff_TrackChangesFormatter
{
    private editor_Models_Import_FileParser_XmlParser $xmlParser;

    private editor_Models_Segment_InternalTag $internalTag;

    private array $revisions = [];

    public function __construct(array $trackChangeIdToUserName)
    {
        $this->internalTag = new editor_Models_Segment_InternalTag();
        $this->xmlParser = new editor_Models_Import_FileParser_XmlParser();

        $this->xmlParser->registerElement(
            'ins, del',
            fn ($tag, $attr, $key) => $this->xmlParser->replaceChunk($key, ''),
            function (string $tag, int $key, array $opener) use ($trackChangeIdToUserName): void {
                $attrs = $opener['attributes'];
                $uuid = ZfExtended_Utils::uuid();
                $ins = 'ins' === $opener['tag'];

                $revTag = sprintf(
                    '<rev-def id="%s"%s author="%s" date="%s" />',
                    $uuid,
                    $ins ? '' : ' type="Delete"',
                    $trackChangeIdToUserName[$attrs['data-usertrackingid']],
                    DateTime::createFromFormat('Y-m-d\TH:i:sO', $attrs['data-timestamp'])->format('m/d/Y H:i:s')
                );

                $this->revisions[] = $revTag;

                $openMrk = sprintf(
                    '<mrk mtype="x-sdl-%s" sdl:revid="%s">',
                    $ins ? 'added' : 'deleted',
                    $uuid,
                );
                $this->xmlParser->replaceChunk($opener['openerKey'], $openMrk);
                $this->xmlParser->replaceChunk($key, '</mrk>');
            }
        );
    }

    public function toSdlxliffFormat(string $segment, array &$revisions = []): string
    {
        $this->revisions = [];
        $segment = $this->xmlParser->parse($segment);
        $revisions = array_merge($revisions, $this->revisions);

        if (strpos($segment, '<mrk') === false) {
            return $this->internalTag->restore($segment);
        }

        // Disable libxml errors as we use mrk tags in the segment
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadXML("<body>$segment</body>", LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $dom->encoding = 'utf-8';

        $tagNodeList = [];
        $closedTags = [];
        $nodesToRemove = [];
        /** @var array<string, true> $removedOpenerCreatedPhantoms */
        $removedOpenerCreatedPhantoms = [];
        /** @var string[] $nodesToPrependAtNextStep */
        $nodesToPrependAtNextStep = [];

        $body = $dom->getElementsByTagName('body')->item(0);

        /** @var \DOMText|\DOMElement $node */
        foreach ($body->childNodes as $node) {
            foreach ($nodesToPrependAtNextStep as $nodeToPrepend) {
                $body->insertBefore($dom->createTextNode($nodeToPrepend), $node);
            }
            $nodesToPrependAtNextStep = [];

            // text nodes out of interest for us
            if (! $node instanceof \DOMElement) {
                continue;
            }

            $content = $dom->saveHTML($node);

            $hasTags = preg_match(editor_Models_Segment_InternalTag::REGEX_INTERNAL_TAGS, $content, $matches);

            // no internal tags - nothing to do here
            if ($hasTags === 0) {
                continue;
            }

            $type = $matches[1];

            // if it is a single tag simply restore internal tag and replace the node
            if ('single' === $type) {
                $body->insertBefore($dom->createTextNode($this->internalTag->restore($content)), $node);

                $nodesToRemove[] = $node;

                continue;
            }

            $restored = $this->internalTag->restore($content);

            $tagNode = [
                'node' => $node,
                'tagName' => preg_match('/<\/?g/', $restored) ? 'g' : 'x',
            ];

            // if it is not a mark tag context
            if (strpos($content, '<mrk') === false) {
                $originalid = $matches[3];

                // closing tag outside of mark tag context can be simply restored and replaced
                if ('close' === $type) {
                    if (isset($tagNodeList[$originalid])) {
                        $body->insertBefore($dom->createTextNode($restored), $node);

                        $nodesToRemove[] = $node;

                        unset($tagNodeList[$originalid]);

                        continue;
                    }

                    continue;
                }

                // opening tag should be preserved for later processing
                $tagNode['originalid'] = $originalid;
                $tagNode['type'] = 'open';
                $tagNode['restored'] = $restored;

                $tagNodeList[$originalid] = $tagNode;

                continue;
            }

            // We in mark tag context
            $deletionMark = strpos($content, 'x-sdl-added') === false;

            $changedTagNodeList = [];

            foreach ($node->childNodes as $changedNode) {
                if (! $changedNode instanceof \DOMElement) {
                    continue;
                }

                $changedTag = $dom->saveHTML($changedNode);

                preg_match(editor_Models_Segment_InternalTag::REGEX_INTERNAL_TAGS, $changedTag, $tagInfo);

                $changedTagOriginalId = $tagInfo[3];

                $restoredTag = $this->internalTag->restore($changedTag);

                if ('single' === $tagInfo[1]) {
                    $node->insertBefore($dom->createTextNode($restoredTag), $changedNode);

                    $nodesToRemove[] = $changedNode;

                    continue;
                }

                $tagName = preg_match('/<\/?g/', $restoredTag) ? 'g' : 'x';

                $changedTagNode = [
                    'originalid' => $changedTagOriginalId,
                    'node' => $changedNode,
                    'tagName' => $tagName,
                    'restored' => $restoredTag,
                    'type' => $tagInfo[1], // single|open|close
                ];

                // if we have both opening and closing tag pair we can replace them
                if ('close' === $tagInfo[1] && isset($changedTagNodeList[$changedTagOriginalId])) {
                    $openTag = $changedTagNodeList[$changedTagOriginalId];
                    // replace opener
                    $node->insertBefore($dom->createTextNode($openTag['restored']), $openTag['node']);
                    // replace closer
                    $node->insertBefore($dom->createTextNode($restoredTag), $changedNode);

                    $nodesToRemove[] = $openTag['node'];
                    $nodesToRemove[] = $changedNode;

                    unset($changedTagNodeList[$changedTagOriginalId]);

                    continue;
                }

                if ('close' === $tagInfo[1]) {
                    $node->insertBefore(
                        $dom->createTextNode(
                            sprintf('<%s id="%s" sdl:start="false"/>', $tagName, $changedTagOriginalId)
                        ),
                        $changedNode
                    );

                    $nodesToRemove[] = $changedNode;

                    // if we got closing tag in deletion change mark context that has no opening tag in this mark
                    // we assume that before mrk exsits not closed opening tag that should be closed
                    if ($deletionMark && ! isset($closedTags[$changedTagOriginalId])) {
                        $closedTags[$changedTagOriginalId] = true;
                        $body->insertBefore($dom->createTextNode(sprintf('</%s>', $tagName)), $node);
                    }

                    continue;
                }

                $changedTagNodeList[$changedTagOriginalId] = $changedTagNode;
            }

            // if there are list of open tags that are not closed in this mark context
            foreach ($changedTagNodeList as $changedTagNode) {
                if (! str_contains($changedTagNode['restored'], 'sdl:end="false"')) {
                    $markedAsOpenTag = str_replace('>', ' sdl:end="false">', $changedTagNode['restored']);
                    $node->insertBefore($dom->createTextNode($markedAsOpenTag), $changedTagNode['node']);

                    $nodesToRemove[] = $changedTagNode['node'];
                }

                // if not self-closed tag
                if (! str_contains($changedTagNode['restored'], '/>')) {
                    $node->append($dom->createTextNode(sprintf('</%s>', $changedTagNode['tagName'])));
                }

                if (! isset($removedOpenerCreatedPhantoms[$changedTagNode['originalid']])) {
                    $removedOpenerCreatedPhantoms[$changedTagNode['originalid']] = true;
                    $nodesToPrependAtNextStep[] = sprintf(
                        '<%s id="%s" sdl:start="false">',
                        $changedTagNode['tagName'],
                        $changedTagNode['originalid']
                    );
                }
            }
        }

        // if there are list of open tags that are not closed in this mark context
        foreach ($tagNodeList as $tagNode) {
            $markedAsOpenTag = str_replace('>', ' sdl:end="false">', $tagNode['restored']);
            $body->replaceChild($dom->createTextNode($markedAsOpenTag), $tagNode['node']);

            // checking that opening tag was not already closed
            if (! isset($closedTags[$tagNode['originalid']])) {
                $body->append($dom->createTextNode(sprintf('</%s>', $tagNode['tagName'])));
            }
        }

        foreach ($nodesToRemove as $node) {
            $node->remove();
        }

        return str_replace(
            ['<body>', '</body>'],
            '',
            $this->internalTag->restore(html_entity_decode($dom->saveXML($body)))
        );
    }
}
