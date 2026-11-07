<?php

return [
    'indicators' => [
        'SF-2' => ['label' => 'Access to Safely Managed Sanitation for Low-Income Communities', 'unit' => '%'],
        'SF-3a' => ['label' => 'Safe Treatment and Disposal from Community Toilets', 'unit' => '%'],
        'SF-3d' => ['label' => 'Average Waiting Time at Community Toilets', 'unit' => 'minutes'],
        'SF-4' => ['label' => 'Access to Public Toilets in Public Spaces', 'unit' => '%'],
        'SF-4c' => ['label' => 'Average Waiting Time at Public Toilets', 'unit' => 'minutes'],
        'SF-6a' => ['label' => 'Safely Managed Sanitation in Government Institutions', 'unit' => '%'],
        'SF-6b' => ['label' => 'Safely Managed Sanitation in Social Institutions', 'unit' => '%'],
        'SF-6c' => ['label' => 'Safely Managed Sanitation in Financial Institutions', 'unit' => '%'],
        'SF-6d' => ['label' => 'Safely Managed Sanitation in Commercial and Industrial Buildings', 'unit' => '%'],
        'SF-6e' => ['label' => 'Safely Managed Sanitation in Cultural and Religious Buildings', 'unit' => '%'],
        'SF-6f' => ['label' => 'Safely Managed Sanitation in Residential Buildings', 'unit' => '%'],
        'SF-6g' => ['label' => 'Safely Managed Sanitation in Mixed-use Buildings', 'unit' => '%'],
        'SF-8' => ['label' => 'Desludging Vehicle Maintenance Compliance', 'unit' => '%'],
        'SF-10' => ['label' => 'Fecal-oral Disease Incidence', 'unit' => 'per 1,000 people'],
        'SF-11a' => ['label' => 'Sanitation Worker Health and Safety Standards and SOPs Exist', 'unit' => 'Yes/No'],
        'SF-11a1' => ['label' => 'Worker Health and Safety Compliance Is Monitored and Enforced', 'unit' => 'Yes/No'],
        'SF-11b' => ['label' => 'All Desludging Operators Are Registered and Licensed', 'unit' => 'Yes/No'],
        'SF-11b1' => ['label' => 'Licensing Requires Practices That Protect Workers', 'unit' => 'Yes/No'],
        'SF-11c' => ['label' => 'Regular Health Checkups Are Available to All Sanitation Workers', 'unit' => 'Yes/No'],
        'SF-12' => ['label' => 'Certification Mechanisms Exist for Treated Effluent and Biosolids', 'unit' => 'Yes/No'],
    ],
    'policies' => ['SF-11a', 'SF-11a1', 'SF-11b', 'SF-11b1', 'SF-11c', 'SF-12'],
    'parents' => ['SF-11a1' => 'SF-11a', 'SF-11b1' => 'SF-11b'],
    'manual' => ['SF-3d', 'SF-4', 'SF-4c', 'SF-11a', 'SF-11a1', 'SF-11b', 'SF-11b1', 'SF-11c', 'SF-12'],
    // These are recommendations in D2.2 Annex 1, not a claim of national endorsement.
    'nepal' => ['SF-1', 'SF-1a', 'SF-1b', 'SF-1c', 'SF-1e', 'SF-1f', 'SF-2', 'SF-2a', 'SF-2b', 'SF-2c', 'SF-3a', 'SF-3b', 'SF-5', 'SF-6', 'SF-7', 'SF-10', 'SF-11a'],
    'decisions' => [
        'safety_sf1_household_basis' => ['default' => 'private', 'label' => 'SF-1 Household Numerator', 'options' => ['pending' => 'Awaiting confirmation', 'private' => 'Private-toilet households', 'served' => 'All households in safe buildings']],
        'safety_sf1e_volume_basis' => ['default' => 'transaction', 'label' => 'SF-1e Sludge Collection Volume Unit', 'options' => ['pending' => 'Awaiting confirmation', 'transaction' => 'Transaction volume in cubic metres', 'daily_rate' => 'Average daily volume in cubic metres per day']],
        'safety_waiting_method' => ['default' => 'literal', 'label' => 'SF-3d and SF-4c Waiting-time Aggregation', 'options' => ['pending' => 'Awaiting confirmation', 'weighted' => 'User-weighted mean', 'literal' => 'Sum of waiting time divided by users']],
        'safety_sf6f_scope' => ['label' => 'SF-6f Residential Building Scope', 'options' => ['pending' => 'Awaiting confirmation', 'residential' => 'Residential only', 'residential_mixed' => 'Residential and mixed-use']],
    ],
];
