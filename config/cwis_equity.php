<?php

return [
    'inputs' => [
        'total_subsidies_amount_nss' => ['EQ-3', 'numeric', 9999999999.99],
        'total_subsidies_amount_ss' => ['EQ-3', 'numeric', 9999999999.99],
        'women_employee_count' => ['EQ-4', 'integer', 2147483647],
        'total_employee_count' => ['EQ-4', 'integer', 2147483647],
        'women_leadership_count' => ['EQ-4a', 'integer', 2147483647],
        'total_leadership_count' => ['EQ-4a', 'integer', 2147483647],
        'average_salary_of_women_in_sanitation' => ['EQ-5', 'numeric', 9999999999999999.99],
        'average_salary_of_men_in_sanitation' => ['EQ-5', 'numeric', 9999999999999999.99],
    ],
    'policy_parents' => [
        'EQ-6.1' => '', 'EQ-6.1a' => 'EQ-6.1', 'EQ-6.1b' => 'EQ-6.1',
        'EQ-6.2' => '', 'EQ-6.3' => '', 'EQ-6.3a' => 'EQ-6.3',
        'EQ-6.3b' => 'EQ-6.3', 'EQ-6.4' => '', 'EQ-6.5' => '',
    ],
];
