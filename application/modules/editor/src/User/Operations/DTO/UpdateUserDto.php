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

declare(strict_types=1);

namespace MittagQI\Translate5\User\Operations\DTO;

use MittagQI\Translate5\User\Exception\AttemptToChangeCoordinatorGroupForUserException;
use MittagQI\Translate5\User\Exception\AttemptToSetCoordinatorGroupForNonJobCoordinatorException;
use REST_Controller_Request_Http as Request;
use ZfExtended_Models_User;

class UpdateUserDto
{
    /**
     * @param string[] $roles
     * @param int[] $customers
     */
    public function __construct(
        public readonly ?string $login,
        public readonly ?string $email,
        public readonly ?string $firstName,
        public readonly ?string $surName,
        public readonly ?string $gender,
        public readonly ?array $roles = null,
        public readonly ?array $customers = null,
        public readonly ?PasswordDto $password = null,
        public readonly ?string $locale = null,
    ) {
    }

    /**
     * @throws AttemptToSetCoordinatorGroupForNonJobCoordinatorException
     */
    public static function fromRequest(Request $request): UpdateUserDto
    {
        $data = $request->getParam('data');
        $data = json_decode($data, true, flags: JSON_THROW_ON_ERROR);

        if (isset($data['coordinatorGroup'])) {
            throw new AttemptToChangeCoordinatorGroupForUserException((int) $request->getParam('id'));
        }

        $roles = explode(',', trim($data['roles'] ?? '', ' ,'));

        $customerIds = array_filter(
            array_map(
                'intval',
                explode(',', trim((string) ($data['customers'] ?? ''), ' ,'))
            )
        );

        $password = null;

        if (array_key_exists('passwd', $data)) {
            $passwd = null !== $data['passwd'] ? trim($data['passwd']) : null;
            $password = new PasswordDto($passwd);
        }

        return new UpdateUserDto(
            $data['login'] ?? null,
            $data['email'] ?? null,
            $data['firstName'] ?? null,
            $data['surName'] ?? null,
            $data['gender'] ?? ZfExtended_Models_User::GENDER_NONE,
            $roles,
            $customerIds,
            $password,
            $data['locale'] ?? null,
        );
    }
}
