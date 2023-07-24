<?php

declare(strict_types=1);

namespace Neusta\SentryTracing\DataProcessing;

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

final class SentryTraceProcessor implements DataProcessorInterface
{
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $attribute = $cObj->getRequest()->getAttribute('sentry');
        if ($attribute !== null) {
            $processedData['sentry'] = $attribute;
        }
        return $processedData;
    }
}
