<?php

/**
 * This class will detect internal tags in the target which are references to trans unit and link them to newly created
 * trans-unit which is clone of the one which they were pointing before. The only difference is, the cloned tu has new
 * unique transunit id. The reason for this is,
 */
class editor_Models_Export_FileParser_Sdlxliff_RepairLockedReferences
{

    public function __construct(
        private readonly editor_Models_Import_FileParser_XmlParser $xmlparser,
        private readonly string $exportFile
    ) {}

    public function repair(): string {

        [$lockedReferences,$lockedTransUnits] = $this->collect();


        if(empty($lockedReferences)) {
            return $this->exportFile;
        }

        return $this->replace($lockedReferences,$lockedTransUnits);
    }

    private function collect(): array
    {
        $xmlparser = $this->xmlparser;

        $lockedReferences = [];

        // collect all source tags which(grouped by tag id which actually is the target transunit id) are pointing to locked transunit
        $xmlparser->registerElement('trans-unit seg-source x[xid^=lockTU_]',
            function ($tag, $attr, $key) use(&$lockedReferences)
            {
                if(!isset($lockedReferences[$attr['xid']])){
                    $reference = new editor_Models_Export_FileParser_Sdlxliff_LockedReferencesDTO();
                    $lockedReferences[$attr['xid']] = $reference;
                }else{
                    $reference = $lockedReferences[$attr['xid']];
                }
                $reference->sourceId = $attr['xid'];
            }
        );


        // collect all target tags which(grouped by tag id which actually is the target transunit id) are pointing to locked transunit
        $xmlparser->registerElement('trans-unit target x[xid^=lockTU_]',
            function ($targetTag, $targetAttr, $targetKey) use(&$lockedReferences)
            {
                if(!isset($lockedReferences[$targetAttr['xid']])){
                    $reference = new editor_Models_Export_FileParser_Sdlxliff_LockedReferencesDTO();
                    $lockedReferences[$targetAttr['xid']] = $reference;
                }else{
                    $reference = $lockedReferences[$targetAttr['xid']];
                }

                $reference->targetId = $targetAttr['xid'];
                $reference->targetChunkId = $targetKey;
            }
        );

        // collect all locked transunit ids which are referenced by the locked segments above
        $xmlparser->registerElement('trans-unit[id^=lockTU_]',
            function ($tag, $attr, $key) use ($xmlparser,&$lockedTransUnits)
            {
                $id = $xmlparser->getAttribute($attr, 'id');
                $lockedTransUnits[$id]['start'] = $key;
            },
            function($tag, $key, $opener) use ($xmlparser,&$lockedTransUnits)
            {
                $id = $xmlparser->getAttribute($opener['attributes'], 'id');
                $lockedTransUnits[$id]['end'] = $key;
            }
        );

        // filter out the tag pairs where the target tu ids are not equal
        $xmlparser->registerElement('body',closer: function($tag, $key, $opener) use ($xmlparser,&$lockedReferences) {
            foreach ($lockedReferences as $key => $lockedTuId) {
                if (!$lockedTuId->match()) {
                    unset($lockedReferences[$key]);
                }
            }
        });

        $xmlparser->parse($this->exportFile);

        return [$lockedReferences,$lockedTransUnits];
    }

    /**
     * Replace the target tag referenced transunit id with cloned version with new unique transunit id
     * @param array $lockedReferences
     * @param array $lockedTransUnits
     * @return string
     */
    private function replace(array $lockedReferences, array $lockedTransUnits): string
    {
        foreach ($lockedReferences as $reference) {
            /* @var editor_Models_Export_FileParser_Sdlxliff_LockedReferencesDTO $reference */

            $matchedTag = $this->xmlparser->getChunk($reference->targetChunkId);

            $this->xmlparser->replaceChunk($reference->targetChunkId,
                function ($chunk) use($reference,$matchedTag)
                {
                    return str_replace(
                        $reference->targetId,
                        $reference->newTargetId,
                        $matchedTag
                    );
                }
            );


            $lockedTargetTuId = $lockedTransUnits[$reference->targetId] ?? null;

            if(isset($lockedTargetTuId)) {

                $newTu = '';
                for (
                    $i = $lockedTargetTuId['start'];
                    $i <= $lockedTargetTuId['end'];
                    $i++
                )
                {

                    if(str_contains($this->xmlparser->getChunk($i), $reference->targetId))
                    {
                        $newTu .= str_replace(
                            $reference->targetId,
                            $reference->newTargetId,
                            $this->xmlparser->getChunk($i)
                        );

                    }else{
                        $newTu .= $this->xmlparser->getChunk($i);
                    }
                }

                $this->xmlparser->replaceChunk($lockedTargetTuId['end'],
                    function ($chunk) use($newTu)
                    {
                        return '</trans-unit>'.$newTu;
                    }
                );
            }
        }

        return $this->xmlparser->__toString();
    }
}