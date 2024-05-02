<?php

class editor_Models_Export_FileParser_Sdlxliff_LockedReferencesDTO
{
    public string $sourceId = '';

    public string $targetId = '';

    public int $targetChunkId = 0;

    public string $newTargetId = '';

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->newTargetId = 'lockTU_' . ZfExtended_Utils::guid();
    }

    public function match(): bool
    {
        return ! empty($this->sourceId) && ! empty($this->targetId)
            &&
            $this->sourceId === $this->targetId;
    }
}
