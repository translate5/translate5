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
 * Customer Entity Objekt
 *
 * @method int getId()
 * @method void setId(int $id)
 *
 * @method string getName()
 * @method void setName(string $name)
 *
 * @method string getNumber()
 * @method void setNumber(string $number)
 *
 * @method string getSearchCharacterLimit()
 * @method void setSearchCharacterLimit(int $searchCharacterLimit)
 *
 * @method string getDomain()
 * @method void setDomain(string $domain)
 *
 * @method string getOpenIdServer()
 * @method void setOpenIdServer(string $openIdServer)
 *
 * @method string getOpenIdIssuer()
 * @method void setOpenIdIssuer(string $openIdIssuer)
 *
 * @method string getOpenIdAuth2Url()
 * @method void setOpenIdAuth2Url(string $openIdAuth2Url)
 *
 * @method string getOpenIdServerRoles()
 * @method void setOpenIdServerRoles(string $openIdServerRoles)
 *
 * @method string getOpenIdDefaultServerRoles()
 * @method void setOpenIdDefaultServerRoles(string $openIdDefaultServerRoles)
 *
 * @method string getOpenIdClientId()
 * @method void setOpenIdClientId(string $openIdClientId)
 *
 * @method string getOpenIdClientSecret()
 * @method void setOpenIdClientSecret(string $openIdClientSecret)
 *
 * @method string getOpenIdRedirectLabel()
 * @method void setOpenIdRedirectLabel(string $openIdRedirectLabel)
 *
 * @method int getOpenIdRedirectCheckbox()
 * @method void setOpenIdRedirectCheckbox(integer $openIdRedirectCheckbox)
 *
 * @method int getOpenIdSyncUserData()
 * @method void setOpenIdSyncUserData(integer $openIdSyncUserData)
 */
class editor_Models_Customer_Customer extends ZfExtended_Models_Entity_Abstract
{
    protected $dbInstanceClass = 'editor_Models_Db_Customer';

    protected $validatorInstanceClass = 'editor_Models_Validator_Customer';

    /**
     * Customers must be filtered by role-driven restrictions
     */
    protected ?array $clientAccessRestriction = [
        'field' => 'id',
    ];

    public const DEFAULTCUSTOMER_NUMBER = 'default for legacy data';

    protected ?editor_Models_Customer_Meta $meta;

    public function delete()
    {
        $customerId = $this->getId();
        parent::delete();
        $logger = ZfExtended_Factory::get(editor_Models_LanguageResources_UsageLogger::class);
        //remove the log data for the deleted customer
        $logger->deleteByCustomer($customerId);
    }

    /**
     *  Get the customer specific config for current customer.
     *  If there is no customer overwritte for the config, the instance level value will be used.
     * @return Zend_Config
     */
    public function getConfig()
    {
        $customerConfig = ZfExtended_Factory::get(editor_Models_Customer_CustomerConfig::class);

        return $customerConfig->getCustomerConfig($this->getId());
    }

    /**
     * Loads customers by a given list of ids
     * @return array
     */
    public function loadByIds(array $ids)
    {
        $s = $this->db->select()
            ->where('id IN (?)', array_unique($ids))
            ->order('name ASC');

        return $this->loadFilterdCustom($s);
    }

    /***
     * Load customer by number
     * @param string $number
     */
    public function loadByNumber($number)
    {
        try {
            $s = $this->db->select()->where('`number` = ?', $number);
            $row = $this->db->fetchRow($s);
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        if (! $row) {
            $this->notFound(__CLASS__ . '#number', $number);
        }
        //load implies loading one Row, so use only the first row
        $this->row = $row;
    }

    /***
     * Search customers by given search string.
     * The search will provide any match on name field.
     *
     * @param string $searchString
     * @return array|array
     */
    public function search($searchString, $fields = [])
    {
        $s = $this->db->select();
        if (! empty($fields)) {
            $s->from($this->tableName, $fields);
        }
        $s->where('lower(name) LIKE lower(?)', '%' . $searchString . '%');

        return $this->db->fetchAll($s)->toArray();
    }

    /***
     * Get min characters from given customers
     * @param array $customers
     * @return array
     */
    public function getMinSearchCharacters(array $customers)
    {
        if (empty($customers)) {
            return [];
        }
        $s = $this->db->select()
            ->from($this->tableName, ['MIN(searchCharacterLimit) as searchCharacterLimit'])
            ->where('id IN(?)', $customers);

        return $this->db->fetchRow($s)->toArray();
    }

    /**
     * Return minimum search characters for user customers.
     * If no user model is provided, the session user customers will be used
     *
     * @return int|mixed
     */
    public function getMinCharactersByUser(ZfExtended_Models_User $user = null)
    {
        //no user, use the session user
        if (is_null($user)) {
            $user = ZfExtended_Authentication::getInstance()->getUser();
        }

        $ret = $this->getMinSearchCharacters($user->getCustomersArray());
        if (! empty($ret) && isset($ret['searchCharacterLimit'])) {
            return $ret['searchCharacterLimit'];
        }

        return 0;
    }

    /**
     * Load the default customer and return it
     */
    public function loadByDefaultCustomer(): editor_Models_Customer_Customer
    {
        $this->loadRow('number=?', self::DEFAULTCUSTOMER_NUMBER);

        return $this;
    }

    /***
     * Load customer entity by given openid domain
     * @param string $domain
     */
    public function loadByDomain($domain)
    {
        $s = $this->db->select();
        $s->where('domain=?', $domain);
        $row = $this->db->fetchRow($s);
        if (empty($row)) {
            return;
        }
        $this->row = $row;
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function loadByName(string $name): void
    {
        $s = $this->db->select();
        $s->where('name = ?', $name);
        $row = $this->db->fetchRow($s);

        if (empty($row)) {
            throw new ZfExtended_Models_Entity_NotFoundException();
        }

        $this->row = $row;
    }

    /**
     * convenient method to get the customer meta data
     * @param bool $reinit if true reinits the internal meta object completely (after adding a field for example)
     * @return editor_Models_customer_Meta
     */
    public function meta(bool $reinit = false)
    {
        $meta = $this->meta ?? $this->meta = ZfExtended_Factory::get(editor_Models_Customer_Meta::class);
        $customerId = $this->getId();
        if ($meta->getCustomerId() != $customerId || $reinit) {
            try {
                $meta->loadByCustomerId($customerId);
            } catch (ZfExtended_Models_Entity_NotFoundException) {
                $meta->init([
                    'customerId' => $customerId,
                ]);
            }
        }

        return $meta;
    }

    /***
     * Is the customer the default customer?
     */
    public function isDefaultCustomer(): bool
    {
        return $this->getNumber() == self::DEFAULTCUSTOMER_NUMBER;
    }

    /**
     * Get the customer domain with the configured server protocol. The customer domain is always
     * saved without protocol.
     * @throws Zend_Exception
     */
    public function getFullDomain(): string
    {
        if (empty($this->getDomain())) {
            return '';
        }
        $config = Zend_Registry::get('config');
        $protocol = $config->runtimeOptions->server->protocol ?? 'https://';

        return $protocol . rtrim($this->getDomain(), '/');
    }

    public function __toString()
    {
        return $this->getName() . ' (' . $this->getNumber() . '; id: ' . $this->getId() . ')';
    }
}
