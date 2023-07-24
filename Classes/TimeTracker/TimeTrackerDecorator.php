<?php

declare(strict_types=1);

namespace Neusta\SentryTracing\TimeTracker;

use Sentry\SentrySdk;
use Sentry\Tracing\Transaction;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;

final class TimeTrackerDecorator extends TimeTracker
{
    private array $spanStack = [];

    public function push(string $tslabel, string $value = ''): void
    {
        parent::push($tslabel, $value);
        $parent = SentrySdk::getCurrentHub()->getSpan();

        if ($parent !== null) {
            $this->spanStack[] = $parent;
            $context = new \Sentry\Tracing\SpanContext();
            $context->setOp($tslabel);
            $context->setDescription($value);
            $span = $parent->startChild($context);

            SentrySdk::getCurrentHub()->setSpan($span);
        }
    }

    public function pull(string $content = ''): void
    {
        parent::pull($content);

        $span = SentrySdk::getCurrentHub()->getSpan();
        if ($span !== null) {
            if (!($span instanceof Transaction)) {
                $span->setData(['content' => $content]);
                $span->finish();
            }
            $parent = array_pop($this->spanStack);
            SentrySdk::getCurrentHub()->setSpan($parent);
        }
    }
}
