<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Auth::login');
$routes->get('login', 'Auth::login');
$routes->get('auth/login', 'Auth::login');
$routes->post('auth/processLogin', 'Auth::processLogin');
$routes->get('forgot-password', 'Auth::forgotPassword');
$routes->get('auth/forgot-password', 'Auth::forgotPassword');
$routes->post('auth/processForgotPassword', 'Auth::processForgotPassword');
// Password reset (link from email)
$routes->get('reset-password', 'Auth::resetPassword');
$routes->post('auth/processResetPassword', 'Auth::processResetPassword');
$routes->get('register', 'Auth::register');
$routes->post('auth/processRegister', 'Auth::processRegister');
$routes->post('auth/generateInvite', 'Auth::generateInvite');
$routes->post('auth/logout', 'Auth::logout');
$routes->get('change-password', 'Auth::changePassword');
$routes->post('auth/processChangePassword', 'Auth::processChangePassword');

$routes->get('auth/firebase-token', 'Auth::firebaseToken');

$routes->get('dashboard', 'Dashboard::index');
$routes->get('dashboard/live', 'Dashboard::live');

// Google Maps API proxy (server-side; keeps API key out of the browser)
$routes->get('maps/geocode',  'Maps::geocode');
$routes->get('maps/reverse',  'Maps::reverse');
$routes->get('maps/distance', 'Maps::distance');
$routes->get('summary', 'Dashboard::index');
$routes->get('monitor', 'Dashboard::index');

// Projects Routes
$routes->get('projects/deleted',  'Projects::deleted');
$routes->get('projects/archived', 'Projects::archived');
$routes->get('projects', 'Projects::index');
$routes->get('projects/create', 'Projects::create');
$routes->post('projects/create', 'Projects::create');
$routes->get('projects/update/(:segment)', 'Projects::update/$1');
$routes->post('projects/update/(:segment)', 'Projects::update/$1');
$routes->get('projects/show/(:segment)', 'Projects::show/$1');
$routes->post('projects/delete', 'Projects::delete');
// Backwards-compatible GET delete
$routes->get('projects/delete/(:segment)', 'Projects::delete/$1');

// Project Progress Summary JSON (for modal Weekly / Overall buttons)
$routes->get('projects/(:segment)/progress-summary', 'Projects::progressSummaryJson/$1');

// Project Schedule / Milestone Management
$routes->get('projects/(:segment)/schedule',  'Projects::schedule/$1');
$routes->post('projects/(:segment)/schedule', 'Projects::schedule/$1');

// Project Resource Assignment
$routes->get('projects/(:segment)/resources',                      'Projects::resources/$1');
$routes->post('projects/(:segment)/resources/assign',              'Projects::assignResource/$1');
$routes->post('projects/(:segment)/resources/unassign',            'Projects::unassignResource/$1');
$routes->get('projects/(:segment)/resources/units/(:segment)',     'Projects::resourceUnits/$1/$2');
$routes->post('projects/(:segment)/resources/bulk-assign-manpower', 'Projects::bulkAssignManpower/$1');
$routes->post('projects/(:segment)/resources/bulk-assign-equipment','Projects::bulkAssignEquipment/$1');

// Project Reports (Emergency + Daily Progress)
$routes->get('projects/(:segment)/emergency-reports/json',                    'Reports::emergencyList/$1');
$routes->get('projects/(:segment)/emergency-reports/(:segment)',              'Reports::emergencyShow/$1/$2');
$routes->post('projects/(:segment)/emergency-reports',                         'Reports::emergencyCreate/$1');
$routes->post('projects/(:segment)/emergency-reports/(:segment)/resolve',      'Reports::emergencyResolve/$1/$2');
$routes->get('projects/(:segment)/daily-reports/json',             'Reports::dailyList/$1');
$routes->get('projects/(:segment)/daily-reports/(:segment)',       'Reports::dailyShow/$1/$2');
$routes->post('projects/(:segment)/daily-reports',                 'Reports::dailyCreate/$1');

// Profile
$routes->get('profile',                   'Profile::index');
$routes->post('profile/update',           'Profile::update');
$routes->post('profile/upload-photo',     'Profile::uploadPhoto');
$routes->get('profile/change-password',   'Profile::changePassword');
$routes->post('profile/change-password',  'Profile::processChangePassword');

// Resource Requests & Transfers
$routes->get('requests/deleted',           'ResourceRequests::deleted');
$routes->get('requests/archived',          'ResourceRequests::archived');
$routes->post('requests/archive',          'ResourceRequests::archive');
$routes->post('requests/delete',           'ResourceRequests::delete');
$routes->get('requests',                          'ResourceRequests::index');
$routes->get('requests/pending-json',             'ResourceRequests::pendingJson');
$routes->get('requests/(:segment)',               'ResourceRequests::show/$1');
$routes->post('requests/store',                   'ResourceRequests::store');
$routes->post('requests/recommend-source',        'ResourceRequests::recommendSource');
$routes->post('requests/approve/(:segment)',       'ResourceRequests::approve/$1');
$routes->post('requests/approve-secondary/(:segment)', 'ResourceRequests::approveSecondary/$1');
$routes->post('requests/reject/(:segment)',          'ResourceRequests::reject/$1');
$routes->post('requests/cancel/(:segment)',          'ResourceRequests::cancel/$1');
$routes->post('requests/accept-delivery/(:segment)', 'ResourceRequests::acceptDelivery/$1');

// Resources Routes
$routes->get('resources/deleted',  'Resources::deleted');
$routes->get('resources/archived', 'Resources::archived');
$routes->get('resources', 'Resources::index');
$routes->get('resources/requests', 'Resources::index');
$routes->get('resources/create', 'Resources::create');
$routes->post('resources/store', 'Resources::store');
$routes->get('resources/show/(:segment)', 'Resources::show/$1');
$routes->get('resources/edit/(:segment)', 'Resources::edit/$1');
$routes->post('resources/update/(:segment)', 'Resources::update/$1');
$routes->post('resources/delete', 'Resources::delete');
$routes->get('resources/delete/(:segment)', 'Resources::delete/$1');
$routes->get('resources/viewItems/(:segment)',          'Resources::viewItems/$1');
$routes->get('resources/viewItems/(:segment)/deleted',  'Resources::itemsDeleted/$1');
$routes->get('resources/viewItems/(:segment)/archived', 'Resources::itemsArchived/$1');
$routes->post('resources/addItem/(:segment)', 'Resources::addItem/$1');
$routes->post('resources/updateItem/(:segment)/(:segment)', 'Resources::updateItem/$1/$2');
$routes->post('resources/deleteItem/(:segment)/(:segment)', 'Resources::deleteItem/$1/$2');
$routes->post('resources/archiveItem/(:segment)/(:segment)', 'Resources::archiveItem/$1/$2');
$routes->post('resources/bulkDeleteItems/(:segment)', 'Resources::bulkDeleteItems/$1');
$routes->post('resources/bulkArchiveItems/(:segment)', 'Resources::bulkArchiveItems/$1');
$routes->post('resources/warehouse-default', 'Resources::saveWarehouseDefault');
$routes->post('resources/warehouse-saved-location', 'Resources::saveWarehouseLocation');
$routes->post('resources/warehouse-saved-location-delete', 'Resources::deleteWarehouseLocation');
// Equipment CRUD
$routes->post('resources/equipment/create', 'Resources::createEquipment');
$routes->post('resources/equipment/update/(:segment)', 'Resources::updateEquipment/$1');
$routes->get('resources/equipment/delete/(:segment)', 'Resources::deleteEquipment/$1');
// Manpower CRUD (moved to dedicated controller)
$routes->post('resources/manpower/create', 'Manpower::store');
$routes->post('resources/manpower/update/(:segment)', 'Manpower::update/$1');
$routes->get('resources/manpower/delete/(:segment)', 'Manpower::delete/$1');

// Dedicated Manpower routes
$routes->get('manpower/create', 'Manpower::create');
$routes->post('manpower/store', 'Manpower::store');
$routes->get('manpower/edit/(:segment)', 'Manpower::edit/$1');
$routes->post('manpower/update/(:segment)', 'Manpower::update/$1');
$routes->post('manpower/delete', 'Manpower::delete');
$routes->get('manpower/delete/(:segment)', 'Manpower::delete/$1');

// Staff Routes
$routes->get('staff/deleted',  'Staff::deleted');
$routes->get('staff/archived', 'Staff::archived');
$routes->get('staff', 'Staff::index');
$routes->get('staff/create', 'Staff::create');
$routes->post('staff/create', 'Staff::create');
$routes->get('staff/update/(:segment)', 'Staff::update/$1');
$routes->post('staff/update/(:segment)', 'Staff::update/$1');
$routes->get('staff/show/(:segment)', 'Staff::show/$1');
$routes->post('staff/resend-credentials/(:segment)', 'Staff::resendCredentials/$1');
$routes->post('staff/delete', 'Staff::delete');
// Backwards-compatible GET delete (optional) - keeps previous behavior when called with segment
$routes->get('staff/delete/(:segment)', 'Staff::delete/$1');

// Account verification (no auth middleware - user isn't logged in yet)
$routes->get('verify-account/(:segment)',  'VerifyAccount::showForm/$1');
$routes->post('verify-account/(:segment)', 'VerifyAccount::setPassword/$1');
$routes->get('verification-success',       'VerifyAccount::verificationSuccess');

// Trash (soft-delete restore/permanent-delete)
$routes->post('trash/restore', 'Trash::restore');
$routes->post('trash/permanent-delete', 'Trash::permanentDelete');

// Archive
$routes->post('archive/restore',          'Archive::restore');
$routes->post('archive/permanent-delete', 'Archive::permanentDelete');
$routes->post('staff/archive',            'Staff::archive');
$routes->post('projects/archive',         'Projects::archive');
$routes->post('resources/archive',        'Resources::archive');

// Notifications
$routes->get('notifications',               'Notifications::index');
$routes->get('notifications/json',          'Notifications::json');
$routes->post('notifications/mark-read',    'Notifications::markRead');
$routes->post('notifications/mark-all-read','Notifications::markAllRead');

// Messages
$routes->get('messages',                          'Messages::index');
$routes->get('messages/json',                     'Messages::chatsJson');
$routes->get('messages/unread-count',             'Messages::unreadCount');
$routes->get('messages/project/(:segment)',       'Messages::projectChat/$1');
$routes->get('messages/(:segment)',               'Messages::show/$1');
$routes->get('messages/(:segment)/json',          'Messages::messagesJson/$1');
$routes->post('messages/(:segment)/send',         'Messages::sendMessage/$1');

// Export (XLSX, PDF, Print)
$routes->get('export', 'Export::index');

// Import (template download + file processing)
$routes->get('import/template', 'Import::template');
$routes->post('import/process', 'Import::process');
