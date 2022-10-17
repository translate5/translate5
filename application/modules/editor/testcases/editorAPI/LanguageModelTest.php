<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Unit Tests for Language Class
 * Should be extended as needed
 */
class LanguageModelTest extends editor_Test_UnitTest {
    /**
     * Testcase for "TRANSLATE-2939: Fix language matching on term tagging" to test language fuzzy logic.
     */
    public function testFuzzyLanguageGetter() {
        $allGermanRFCs = ['de', 'de-AT', 'de-CH', 'de-DE', 'de-LI', 'de-LU'];
        $allGermanSubRFCs = ['de-AT', 'de-CH', 'de-DE', 'de-LI', 'de-LU'];

        $languageDE = new editor_Models_Languages();
        $languageDE->loadByRfc5646('de');
        $languageDESUB = new editor_Models_Languages();
        $languageDESUB->loadByRfc5646('de-DE');

        //clean fuzzy cache first
        $memcache = new ZfExtended_Cache_MySQLMemoryBackend();
        $cached = $memcache->getAllForPartOfId('getFuzzyLanguages');
        if($cached) {
            foreach ($cached as $cache) {
                $memcache->remove($cache['id']);
            }
        }

        //this is just to test the loading, the values may be adopted if they changes (but still represent german!)
        static::assertEquals('Deutsch', $languageDE->getLangName(), 'Language Name not as expected after loading');
        static::assertEquals('de', $languageDE->getRfc5646(), 'RFC5646 not as expected after loading');
        static::assertEquals('ger', $languageDE->getIso6393(), 'ISO6393 not as expected after loading');
        static::assertEquals('Deutsch (Deutschland)', $languageDESUB->getLangName(), 'Language Name not as expected after loading');
        static::assertEquals('de-DE', $languageDESUB->getRfc5646(), 'RFC5646 not as expected after loading');
        static::assertEquals('1031', $languageDESUB->getLcid(), 'LCID not as expected after loading');

        //load the rfc fuzzy values for the loaded german
        static::assertEquals($allGermanRFCs, $languageDE->getFuzzyLanguages($languageDE->getId(), 'rfc5646', true), 'Should return all german languages!');
        static::assertEquals($allGermanRFCs, $languageDE->getFuzzyLanguages($languageDE->getId(), 'rfc5646', false), 'Should return all german languages!');

        static::assertEquals(['de-DE', 'de'], $languageDESUB->getFuzzyLanguages($languageDESUB->getId(), 'rfc5646', true), 'Fuzzies for de-DE (including major) should be de and de-DE only');
        //ta
        static::assertEquals(['de-DE'], $languageDESUB->getFuzzyLanguages($languageDESUB->getId(), 'rfc5646', false), 'Fuzzies for de-DE (excluding major) should be de-DE only');
    }
}
