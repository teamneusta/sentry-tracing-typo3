<?php

return [
    'frontend' => [
        'neusta/sentry/tracing' => [
            'target' => \Neusta\SentryTracing\Middlewares\InitializerMiddleware::class,
            'before' => [
                'typo3/cms-frontend/timetracker',
            ],
        ],
    ],
];
