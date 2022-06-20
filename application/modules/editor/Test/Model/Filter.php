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
 * Just a general data structure for creating a filter to filter arrays of model-arrays or trees of models
 * This is a whitelist-filter that will filter for objects with a certain property to have one or multiple values
 */
class editor_Test_Model_Filter
{
    /**
     * Creates a filter to filter items to have a identical property
     * @param string $propertyName
     * @param string|int|float|bool $value: make sure the value is of the right type!
     * @return editor_Test_Model_Filter
     */
    public static function createSingle(string $propertyName, $value){
        return new editor_Test_Model_Filter($propertyName, $value, NULL);
    }

    /**
     * @param string $propertyName
     * @param array $values
     * @return editor_Test_Model_Filter
     */
    public static function createMulti(string $propertyName, array $values){
        return new editor_Test_Model_Filter($propertyName, NULL, $values);
    }

    /**
     * @var string
     */
    private string $prop;
    /**
     * @var mixed|null
     */
    private $val;
    /**
     * @var array|null
     */
    private ?array $vals;

    private function __construct(string $propertyName, $value=NULL, array $values=NULL){
        $this->prop = $propertyName;
        $this->val = $value;
        $this->vals = $values;
    }

    /**
     * Checks if an item applies to a filter
     * @param mixed $item
     * @return bool
     */
    public function matches($item) : bool {
        if(property_exists($item, $this->prop)){
            $p = $this->prop;
            if($this->val === NULL){
                return in_array($item->$p, $this->vals);
            } else {
                return ($item->$p === $this->val);
            }
        }
        return false;
    }

    /**
     * Filters a list of items
     * @param array $items
     * @return array
     */
    public function apply(array $items) : array {
        $results = [];
        foreach($items as $item){
            if($this->matches($item)){
                $results[] = $item;
            }
        }
        return $results;
    }

    /**
     * Just for debugging
     * @return string
     */
    public function __toString(){
        return 'editor_Test_Model_Filter: property: '.$this->prop.', '.(($this->val === NULL) ? 'values: ['.implode(',', $this->vals).']' : ', value: '.$this->val);
    }
}