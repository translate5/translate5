<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/

namespace MittagQI\Translate5\Tools;

use editor_Utils;

/**
 * Represents a URL of a Website
 * The URL is cleaned/standardized and any redirects are followed to retrieve the final URL
 * By default, Hashes are removed as they usually represent a visual state of the page/webapp
 */
final class PageUrl
{
    public const MAX_REDIRECTS = 3;

    private ?string $url;

    private string $original;

    private bool $removeFragment;

    private bool $valid = true;

    private bool $fragment = false;

    private bool $found = true;

    private bool $accessible = true;

    private int $statusCode;

    public function __construct(string $url, bool $removeFragment = true)
    {
        $this->url = $this->original = $url;
        $this->removeFragment = $removeFragment;
        $this->clean();
        $this->follow();
        if ($this->hasError()) {
            $this->url = null;
        }
    }

    public function get(): string
    {
        return $this->url;
    }

    public function getOriginal(): string
    {
        return $this->original;
    }

    public function hasFragment(): bool
    {
        return $this->fragment;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function isAccessible(): bool
    {
        return $this->accessible;
    }

    public function exists(): bool
    {
        return $this->found;
    }

    public function hasError(): bool
    {
        return ! $this->valid || ! $this->accessible || ! $this->found;
    }

    /**
     * Retrieves a textual error in case of one
     */
    public function getError(string $closing = '.'): ?string
    {
        if (! $this->valid) {
            return 'Invalid URL “' . $this->original . '”' . $closing;
        }
        if (! $this->found) {
            return 'The URL “' . $this->original . '” could not be found' . $closing;
        }
        if (! $this->accessible) {
            return 'The URL “' . $this->original . '” is not accessible - HTTP-status ' . $this->statusCode . $closing;
        }

        return null;
    }

    public function hasWarning(): bool
    {
        return $this->fragment;
    }

    /**
     * Retrieves a textual warning in case of one
     */
    public function getWarning(string $closing = '.'): ?string
    {
        if ($this->fragment) {
            return 'The URL “' . $this->original . '” presumably represents the state of a webapp' . $closing;
        }

        return null;
    }

    /**
     * Cleans & validates the URL
     */
    private function clean(): void
    {
        if (filter_var($this->url, FILTER_VALIDATE_URL) === false) {
            $this->valid = false;

            return;
        }

        $cleanUrl = editor_Utils::removeFragment($this->url);
        if ($cleanUrl != $this->url) {
            $this->fragment = true;
            if ($this->removeFragment) {
                $this->url = $cleanUrl;
            }
        }
    }

    /**
     * Follows redirects and evaluates the destination URL in case of redirects
     * Also checks the accessibility of the URL
     */
    private function follow(): void
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, self::MAX_REDIRECTS);

        // Fetch headers
        $response = curl_exec($ch);

        if ($response === false) {
            $this->found = false;
        } else {
            $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            /** @phpstan-ignore-next-line */
            $this->url = ($effectiveUrl === false) ? null : $effectiveUrl;
            /** @phpstan-ignore-next-line */
            if ($this->url === null) {
                $this->found = false;
            } else {
                $this->statusCode = intval(curl_getinfo($ch, CURLINFO_RESPONSE_CODE));
                $this->accessible = ($this->statusCode >= 200 && $this->statusCode < 300);
                if ($this->statusCode === 404 || $this->statusCode === 410) {
                    $this->found = false;
                }
            }
        }
        curl_close($ch);
    }
}
