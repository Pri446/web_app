<?php

return [
    'financial_sections' => [
        'SS-2' => [
            'title' => 'Operation and Maintenance Cost Recovery',
            'fields' => [
                'ct_pt_user_fee_revenue' => 'Annual CT/PT User Fee Revenue',
                'ct_pt_om_expenditure' => 'Annual CT/PT O&M Expenditure',
                'fsm_chain_revenue' => 'Annual FSM Chain Revenue',
                'sewerage_charges_revenue' => 'Annual Sewerage Charges / Sanitation Levy Revenue',
                'sanitation_chain_om_expenditure' => 'Annual Sanitation Chain O&M Expenditure',
            ],
        ],
        'SS-3' => [
            'title' => 'Capital Expenditure Coverage',
            'fields' => [
                'gov_funded_treatment_capex' => 'Government Funded Treatment CAPEX',
                'gov_funded_ct_pt_capex' => 'Government Funded CT/PT CAPEX',
                'gov_funded_desludging_capex' => 'Government Funded Desludging and Other Eligible CAPEX',
                'annualized_treatment_capex' => 'Annualized Treatment CAPEX',
                'annualized_ct_pt_capex' => 'Annualized CT/PT CAPEX',
                'annualized_desludging_capex' => 'Annualized Desludging and Other Eligible CAPEX',
            ],
        ],
    ],
    'campaigns' => [
        'SS-4.1' => 'Regular Safe Sanitation and Hygiene Campaigns Throughout the City',
        'SS-4.1a' => 'Campaigns Reaching Poor Communities',
        'SS-4.1b' => 'Campaigns Reaching Women, Girls and Socioeconomically Vulnerable Groups',
        'SS-4.1c' => 'Campaigns Reaching Institutions and Public Places',
        'SS-4.1d' => 'Campaigns Covering Water, Energy, Climate and Environmental Issues',
    ],
];
