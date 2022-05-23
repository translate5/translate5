<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Implements an Iterator for a tasks segments.
 * - this iterator loads always one Segment instead storing all segments in Memory.
 * - The only filtering arguments are the optional file id, for more advanced filtering (like in the indexAction) @see
 */
class editor_Models_Segment_Iterator implements Iterator {

    /**
     * @var string
     */
    protected string $taskGuid;
    /**
     * @var ?editor_Models_Segment
     */
    protected ?editor_Models_Segment $segment;
    
    /**
     * @var boolean
     */
    protected bool $isEmpty = false;
    
    /**
     * If set, iterate only over the segments of the given file Id.
     * @var ?integer
     */
    protected ?int $fileId = null;

    /**
     * @param string $taskGuid
     * @param int|null $fileId
     */
    public function __construct(string $taskGuid, ?int $fileId = null) {
        $this->fileId = $fileId;
        $this->taskGuid = $taskGuid;
        $this->rewind();
    }

    /**
     * @return ?editor_Models_Segment
     * @see Iterator::current()
     */
    public function current() : ?editor_Models_Segment
    {
        return $this->segment;
    }

    /**
     * @see Iterator::next()
     */
    public function next(): void {
        $this->segment = $this->segment->loadNext($this->taskGuid, $this->key(), $this->fileId);
    }
    
    /**
     * @see Iterator::key()
     * @return integer the segment id
     */
    public function key() : mixed {
        return $this->segment->getId();
    }

    /**
     * Reloads the first segment of task, we assume we have always a first segment.
     * If not a notfound exceptions is thrown.
     * @see Iterator::rewind()
     */
    public function rewind() : void {
        $this->initSegment();
        try {
            $this->segment->loadFirst($this->taskGuid, $this->fileId);
            $this->isEmpty = false;
        } catch(ZfExtended_Models_Entity_NotFoundException) {
            $this->isEmpty = true;
        }
    }
    
    /**
     * @see Iterator::valid()
     */
    public function valid() : bool {
        return $this->segment && ($this->key() > 0);
    }

    public function isEmpty(): bool {
        return $this->isEmpty;
    }

    protected function initSegment(): void
    {
        $this->segment = ZfExtended_Factory::get('editor_Models_Segment');
    }
}