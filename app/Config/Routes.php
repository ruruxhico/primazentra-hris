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
$routes->get('auth/register', 'Auth::register');
$routes->post('auth/processRegister', 'Auth::processRegister');
$routes->post('auth/logout', 'Auth::logout');

$routes->get('dashboard', 'Dashboard::index');

// Projects Routes
$routes->get('projects', 'Projects::index');
$routes->get('projects/create', 'Projects::create');
$routes->post('projects/create', 'Projects::create');
$routes->get('projects/update/(:segment)', 'Projects::update/$1');
$routes->post('projects/update/(:segment)', 'Projects::update/$1');
$routes->get('projects/show/(:segment)', 'Projects::show/$1');
$routes->post('projects/delete', 'Projects::delete');
// Backwards-compatible GET delete
$routes->get('projects/delete/(:segment)', 'Projects::delete/$1');

// Resources Routes
$routes->get('resources', 'Resources::index');
$routes->get('resources/requests', 'Resources::requests');
$routes->get('resources/create', 'Resources::create');
$routes->post('resources/store', 'Resources::store');
$routes->get('resources/show/(:segment)', 'Resources::show/$1');
$routes->get('resources/edit/(:segment)', 'Resources::edit/$1');
$routes->post('resources/update/(:segment)', 'Resources::update/$1');
$routes->post('resources/delete', 'Resources::delete');
$routes->get('resources/delete/(:segment)', 'Resources::delete/$1');
$routes->get('resources/viewItems/(:segment)', 'Resources::viewItems/$1');
$routes->post('resources/addItem/(:segment)', 'Resources::addItem/$1');
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
$routes->get('staff', 'Staff::index');
$routes->get('staff/create', 'Staff::create');
$routes->post('staff/create', 'Staff::create');
$routes->get('staff/update/(:segment)', 'Staff::update/$1');
$routes->post('staff/update/(:segment)', 'Staff::update/$1');
$routes->get('staff/show/(:segment)', 'Staff::show/$1');
$routes->post('staff/delete', 'Staff::delete');
// Backwards-compatible GET delete (optional) - keeps previous behavior when called with segment
$routes->get('staff/delete/(:segment)', 'Staff::delete/$1');
