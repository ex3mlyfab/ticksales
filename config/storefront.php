<?php

return [
    'customer_data_retention_days' => (int) env('STOREFRONT_CUSTOMER_DATA_RETENTION_DAYS', 365),
    'analytics_event_retention_days' => (int) env('STOREFRONT_ANALYTICS_EVENT_RETENTION_DAYS', 90),
];
