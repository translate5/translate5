<?php

namespace MittagQI\Translate5\Service;

interface HasLanguageDetector
{
    public function getDetector(): DetectLanguageInterface;
}
