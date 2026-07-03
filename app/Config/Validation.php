<?php

namespace Config;

use App\Validation\AuthRules;
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
        AuthRules::class,
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

    ];

    // --------------------------------------------------------------------
    // PROJECT Rules
    // --------------------------------------------------------------------
    public array $projects = [
        'project_name' => [
            'label' => 'Project Name',
            'rules' => "required|regex_match[/^[a-zA-Z0-9\s'\-\.#,&]*$/]|max_length[150]",
            'errors' => [
                'required'    => 'Project Name is required.',
                'regex_match' => 'Project Name contains invalid characters.',
                'max_length'  => 'Project Name cannot exceed 150 characters.',
            ]
        ],

        'client_name' => [
            'label' => 'Client Name',
            'rules' => 'required|max_length[100]',
            'errors' => [
                'required'   => 'Client Name is required.',
                'max_length' => 'Client Name cannot exceed 100 characters.',
            ]
        ],

        'project_type' => [
            'label' => 'Tower Type',
            'rules' => 'required',
            'errors' => [
                'required' => 'Tower Type is required.',
            ]
        ],

        'location_address' => [
            'label' => 'Address',
            'rules' => 'required|max_length[300]',
            'errors' => [
                'required'   => 'Address is required.',
                'max_length' => 'Address cannot exceed 300 characters.',
            ]
        ],

        'project_manager_id' => [
            'label' => 'Project Manager',
            'rules' => 'required',
            'errors' => [
                'required' => 'Project Manager is required.',
            ]
        ],

        'site_engineer_id' => [
            'label' => 'Site Engineer',
            'rules' => 'required',
            'errors' => [
                'required' => 'Site Engineer is required.',
            ]
        ],

        'start_date' => [
            'label' => 'Start Date',
            'rules' => 'required',
            'errors' => [
                'required' => 'Start Date is required.',
            ]
        ],

        'end_date' => [
            'label' => 'End Date',
            'rules' => 'required',
            'errors' => [
                'required' => 'End Date is required.',
            ]
        ],

        'budget' => [
            'label' => 'Budget',
            'rules' => 'required|numeric',
            'errors' => [
                'required' => 'Budget is required.',
                'numeric'  => 'Budget must be a number.',
            ]
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

    // --------------------------------------------------------------------
    // AUTH Rules
    // --------------------------------------------------------------------
    public array $authRegister = [
        'email' => [
            'label' => 'Email Address',
            'rules' => 'required|valid_email|email_domain_accepts_mail',
            'errors' => [
                'required' => 'Email is required.',
                'valid_email' => 'Email entered is invalid.',
            ],
        ],
        'password' => [
            'label' => 'Password',
            'rules' => 'required|strong_password',
            'errors' => [
                'required' => 'Password is required.',
            ],
        ],
        'confirm_password' => [
            'label' => 'Confirm Password',
            'rules' => 'required|matches[password]',
            'errors' => [
                'required' => 'Confirm Password is required.',
                'matches' => 'Passwords do not match.',
            ],
        ],
    ];

    public array $authForgotPassword = [
        'email' => [
            'label' => 'Email Address',
            'rules' => 'required|valid_email|email_domain_accepts_mail',
            'errors' => [
                'required' => 'Email is required.',
                'valid_email' => 'Email entered is invalid.',
            ],
        ],
    ];

    public array $authResetPassword = [
        'oobCode' => [
            'label' => 'Reset Code',
            'rules' => 'required',
            'errors' => [
                'required' => 'Reset code is required.'
            ],
        ],
        'password' => [
            'label' => 'Password',
            'rules' => 'required|strong_password',
            'errors' => [
                'required' => 'Password is required.'
            ],
        ],
        'confirm_password' => [
            'label' => 'Confirm Password',
            'rules' => 'required|matches[password]',
            'errors' => [
                'required' => 'Confirm Password is required.',
                'matches' => 'Passwords do not match.'
            ],
        ],
    ];
}
