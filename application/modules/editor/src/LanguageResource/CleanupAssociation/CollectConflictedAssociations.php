<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2026 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

declare(strict_types=1);

namespace MittagQI\Translate5\LanguageResource\CleanupAssociation;

class CollectConflictedAssociations
{
    /**
     * @var array<string, CollectConflictedAssociationsInterface>
     */
    private array $instances = [];

    /**
     * @param class-string<CollectConflictedAssociationsInterface>[] $collectors
     */
    public function __construct(
        private array $collectors,
    ) {
    }

    public static function create(): self
    {
        return new self([]);
    }

    /**
     * @param class-string<CollectConflictedAssociationsInterface> $collector
     */
    public function addService(string $collector): void
    {
        if (! is_subclass_of($collector, CollectConflictedAssociationsInterface::class)) {
            throw new \InvalidArgumentException(sprintf(
                'Service %s must implement %s',
                $collector,
                CollectConflictedAssociationsInterface::class
            ));
        }

        $this->collectors[] = $collector;
    }

    public function collect(): array
    {
        $classes = [];

        foreach ($this->collectors as $collector) {
            $instance = $this->getService($collector);
            $classes[] = $instance->getEntityClass();
        }

        return $classes;
    }

    /**
     * @param class-string<CollectConflictedAssociationsInterface> $collector
     */
    private function getService(string $collector): CollectConflictedAssociationsInterface
    {
        if (! isset($this->instances[$collector])) {
            $this->instances[$collector] = $collector::create();
        }

        return $this->instances[$collector];
    }
}
