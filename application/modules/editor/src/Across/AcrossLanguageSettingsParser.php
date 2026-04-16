<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Across;

use DOMDocument;

final class AcrossLanguageSettingsParser
{
    /**
     * Must be filled when loading/validation fails
     */
    protected string $error;

    protected DOMDocument $dom;

    /**
     * @throws \ZfExtended_Exception
     */
    public function __construct(
        private string $path
    ) {
        $content = @file_get_contents($this->path);
        if (! $content) {
            $this->error = get_class($this) . ' can only be instantiated for an existing file (' .
                $this->path . ') with contents';
        } else {
            $this->dom = new \ZfExtended_Dom();
            if (! $this->dom->loadXML($content) || ! $this->dom->isValid()) {
                $this->error = get_class($this) . ' the loaded file (' .
                    $this->path . ') seems to contain invalid XML';
            }
        }
        if (! isset($this->error)) {
            if (trim($this->dom->getRootNode()->nodeName, '#') === 'document') {
                $languages = $this->dom->getElementsByTagName('language');
                if ($languages->count() === 0) {
                    $this->error = get_class($this) . ' the loaded file (' .
                        $this->path . ') does not contain language-nodes';
                } elseif ($languages->item(0)->nodeName !== 'language' ||
                    ! $languages->item(0)->hasAttributes() ||
                    $languages->item(0)->attributes->getNamedItem('lcid') === null
                ) {
                    $this->error = get_class($this) . ' the loaded file (' .
                        $this->path . ') seem not to contain valid language-nodes';
                } else {
                    $this->error = '';
                }
            } else {
                $this->error = get_class($this) . ' the loaded file (' .
                    $this->path . ') does not contain a document-node as root';
            }
        }
    }

    public function isValid(): bool
    {
        return $this->error === '';
    }

    public function getValidationError(): string
    {
        return $this->error;
    }

    /**
     * @return array<string, AcrossLanguageSetting>
     */
    public function getLanguages(): array
    {
        $languages = [];

        if ($this->isValid()) {
            $languageModel = new \editor_Models_Languages();
            /** @var \DOMNode $child */
            foreach ($this->dom->getElementsByTagName('language') as $child) {
                if ($child->nodeName === 'language' && $child->hasAttributes()) {
                    $lcid = $child->attributes->getNamedItem('lcid');
                    if ($lcid !== null) {
                        try {
                            $languageModel->loadByLcid($lcid->nodeValue);
                            $locale = $languageModel->getRfc5646();
                            $languages[$languageModel->getRfc5646()] = new AcrossLanguageSetting($child, $locale);
                        } catch (\Throwable $e) {
                            error_log(
                                'Invalid LCID in Across language-Settings File "' .
                                $this->path . '": ' . $e->getMessage()
                            );
                        }
                    }
                }
            }
        }

        return $languages;
    }
}
