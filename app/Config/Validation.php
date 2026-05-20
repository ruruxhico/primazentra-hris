<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------
    // --------------------------------------------------------------------
    // MANPOWER Rules
    // --------------------------------------------------------------------
    public array $manpower = [
        'first_name' => [
            'label' => 'First Name',
            'rules' => "required|regex_match[/^[a-zA-Z\s'\-]*$/]",
            'errors' => [
                'required' => 'First Name is required.',
                'regex_match' => "First Name can only contain alphabetic characters, spaces, apostrophes (') and dashes (-)."
            ]
        ],

        'middle_name' => [
            'label' => 'Middle Name',
            'rules' => "permit_empty|regex_match[/^[a-zA-Z\s'\-]*$/]",
            'errors' => [
                'regex_match' => "Middle Name can only contain alphabetic characters, spaces, apostrophes (') and dashes (-)."
            ]
        ],

        'last_name' => [
            'label' => 'Last Name',
            'rules' => "required|regex_match[/^[a-zA-Z\s'\-]*$/]",
            'errors' => [
                'required' => 'Last Name is required.',
                'regex_match' => "Last Name can only contain alphabetic characters, spaces, apostrophes (') and dashes (-)."
            ]
        ],

        'manpower_role' => [
            'label' => 'Role',
            'rules' => 'required',
            'errors' => [
                'required' => 'Role is required.'
            ]
        ],

        'specialization' => [
            'label' => 'Specialization',
            'rules' => 'required',
            'errors' => [
                'required' => 'Specialization is required.'
            ]
        ],

        'contact_number' => [
            'label' => 'Contact Number',
            'rules' => 'permit_empty|max_length[30]',
            'errors' => [
                'max_length' => 'Contact number cannot exceed 30 characters.'
            ]
        ],
    ];

    // --------------------------------------------------------------------
    // PROJECT Rules
    // --------------------------------------------------------------------
    public array $projects = [
        'project_name' => [
            'label' => 'Project Name',
            'rules' => "required|regex_match[/^[a-zA-Z0-9\s'\-\.#,&]*$/]|max_length[150]",
            'errors' => [
                'required' => 'Project Name is required.',
                'regex_match' => "Project Name contains invalid characters.",
                'max_length' => 'Project Name cannot exceed 150 characters.'
            ]
        ],

        'project_type' => [
            'label' => 'Project Type',
            'rules' => 'required',
            'errors' => [
                'required' => 'Project Type is required.'
            ]
        ],

        'start_date' => [
            'label' => 'Start Date',
            'rules' => 'permit_empty',
            'errors' => []
        ],

        'end_date' => [
            'label' => 'End Date',
            'rules' => 'permit_empty',
            'errors' => []
        ],

        'budget' => [
            'label' => 'Budget',
            'rules' => 'permit_empty|numeric',
            'errors' => [
                'numeric' => 'Budget must be a number.'
            ]
        ],

        'project_manager_id' => [
            'label' => 'Project Manager',
            'rules' => 'permit_empty',
            'errors' => []
        ],

        'site_engineer_id' => [
            'label' => 'Site Engineer',
            'rules' => 'permit_empty',
            'errors' => []
        ],
    ];

    // --------------------------------------------------------------------
    // STAFF Rules
    // --------------------------------------------------------------------
    public array $staff = [
        'first_name' => [
            'label' => 'First Name',
            'rules' => "required|regex_match[/^[a-zA-Z\s'\-]*$/]|max_length[100]",
            'errors' => [
                'required' => 'First Name is required.',
                'regex_match' => "First Name can only contain alphabetic characters, spaces, apostrophes (') and dashes (-).",
                'max_length' => 'First Name cannot exceed 100 characters.'
            ]
        ],

        'middle_name' => [
            'label' => 'Middle Name',
            'rules' => "permit_empty|regex_match[/^[a-zA-Z\s'\-]*$/]|max_length[100]",
            'errors' => [
                'regex_match' => "Middle Name can only contain alphabetic characters, spaces, apostrophes (') and dashes (-).",
                'max_length' => 'Middle Name cannot exceed 100 characters.'
            ]
        ],

        'last_name' => [
            'label' => 'Last Name',
            'rules' => "required|regex_match[/^[a-zA-Z\s'\-]*$/]|max_length[100]",
            'errors' => [
                'required' => 'Last Name is required.',
                'regex_match' => "Last Name can only contain alphabetic characters, spaces, apostrophes (') and dashes (-).",
                'max_length' => 'Last Name cannot exceed 100 characters.'
            ]
        ],

        'email' => [
            'label' => 'Email Address',
            'rules' => 'required|valid_email',
            'errors' => [
                'required' => 'Email is required.',
                'valid_email' => 'Email entered is invalid.'
            ]
        ],

        'contact_no' => [
            'label' => 'Contact Number',
            'rules' => 'permit_empty|max_length[30]',
            'errors' => [
                'max_length' => 'Contact number cannot exceed 30 characters.'
            ]
        ],

        'role' => [
            'label' => 'Role',
            'rules' => 'required',
            'errors' => [
                'required' => 'Role is required.'
            ]
        ],
    ];
}
