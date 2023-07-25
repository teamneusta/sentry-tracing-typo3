# Sentry Tracing for TYPO3

Provides performance monitoring for TYPO3 using Sentry tracing.
It provides the same performance information as available in the admin
panel TS tracking view.

It additionally provides a DataProcessor to add the tracing request information
to the frontend to allow the Sentry frontend JavaScript to add the frontend performance
information, tracing both the frontend and backend.

## Prerequisites

The extension provides additional functionality on top of networkteam/sentry-client.
It requires the networkteam/sentry-client extension to be installed and configured as
it uses the same basic Sentry configuration.

## Installation

Install the extension using composer:

```
composer require neusta/sentry-tracing-typo3
```

## Configuration

Go to the extension configuration and enable the tracing functionality.
You can configure the sampling rate (where 0.2 means 20% of requests are traced).
For testing purposes, you can set the sampling rate to 1.0 to trace all requests.

## Frontend tracing

This extension does not provide frontend tracing out of the box. You need to add
the Sentry frontend JavaScript to your frontend. See the Sentry documentation for
details (https://docs.sentry.io/platforms/javascript/ - heavily depends on your
frontend setup).

You can use the provided DataProcessor to add the tracing information to the frontend to be able to connect backend and frontend traces.
Add the DataProcessor to your page rendering configuration:

For example:

```typo3_typoscript
page.10.dataProcessing.100 = Neusta\SentryTracing\DataProcessing\SentryTraceProcessor
```

You can then access the tracing information in Fluid. 

Be aware that the tracing information may be cached, depending on how and where you include the meta tags.

If you have a meta tag viewhelper
in your project, you can add the tracing information to the corresponding meta tags:

```html
    <nsd:metaTag content="{sentry.baggage}" name="baggage"/>
    <nsd:metaTag content="{sentry.trace}" name="sentry-trace"/>
```

An example meta tag view helper could look like this (inspired by the `news` extension):

```php
<?php
declare(strict_types=1);
namespace Neusta\Theme\ViewHelpers;

use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

final class MetaTagViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Arguments initialization
     */
    public function initializeArguments()
    {
        $this->registerArgument('property', 'string', 'Property of meta tag', false, '', false);
        $this->registerArgument('name', 'string', 'Content of meta tag using the name attribute', false, '', false);
        $this->registerArgument('content', 'string', 'Content of meta tag', true, null, false);
        $this->registerArgument('useCurrentDomain', 'boolean', 'Use current domain', false, false);
        $this->registerArgument('forceAbsoluteUrl', 'boolean', 'Force absolut domain', false, false);
        $this->registerArgument('replace', 'boolean', 'Replace potential existing tag', false, false);
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $useCurrentDomain = $arguments['useCurrentDomain'];
        $forceAbsoluteUrl = $arguments['forceAbsoluteUrl'];
        $content = (string)$arguments['content'];

        // set current domain
        if ($useCurrentDomain) {
            $content = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
        }

        // prepend current domain
        if ($forceAbsoluteUrl) {
            $parsedPath = parse_url($content);
            if (is_array($parsedPath) && !isset($parsedPath['host'])) {
                $content =
                    rtrim(GeneralUtility::getIndpEnv('TYPO3_SITE_URL'), '/')
                    . '/'
                    . ltrim($content, '/');
            }
        }

        if ($content !== '') {
            $registry = GeneralUtility::makeInstance(MetaTagManagerRegistry::class);
            if ($arguments['property']) {
                $manager = $registry->getManagerForProperty($arguments['property']);
                $manager->addProperty($arguments['property'], $content, [], $arguments['replace'], 'property');
            } elseif ($arguments['name']) {
                $manager = $registry->getManagerForProperty($arguments['name']);
                $manager->addProperty($arguments['name'], $content, [], $arguments['replace'], 'name');
            }
        }
    }
}
```
