<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\ContentProtection\NumberProtection;

use InvalidArgumentException;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionRepository;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\DateProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\FloatProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\IntegerProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\IPAddressProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\KeepContentProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\MacAddressProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\NumberProtectorInterface;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\ReplaceContentProtector;

class NumberProtectorProvider
{
    /**
     * @var array<string, NumberProtectorInterface>
     */
    private array $protectors;

    /**
     * @param NumberProtectorInterface[] $protectors
     */
    public function __construct(
        array $protectors,
    ) {
        foreach ($protectors as $protector) {
            $this->protectors[$protector::getType()] = $protector;
        }
    }

    public static function create(): self
    {
        $contentProtectionRepository = ContentProtectionRepository::create();

        return new self(
            [
                new DateProtector($contentProtectionRepository),
                new FloatProtector($contentProtectionRepository),
                new IntegerProtector($contentProtectionRepository),
                new IPAddressProtector($contentProtectionRepository),
                new MacAddressProtector($contentProtectionRepository),
                new KeepContentProtector($contentProtectionRepository),
                new ReplaceContentProtector($contentProtectionRepository),
            ],
        );
    }

    public function getByType(string $type): NumberProtectorInterface
    {
        if (! isset($this->protectors[$type])) {
            throw new InvalidArgumentException(sprintf('No number protector found for type "%s".', $type));
        }

        return $this->protectors[$type];
    }

    public function types(): array
    {
        return array_keys($this->protectors);
    }
}
