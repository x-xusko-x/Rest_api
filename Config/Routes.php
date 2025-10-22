<?php

namespace Config;

$routes = Services::routes();

$rest_api = ['namespace' => 'Rest_api\Controllers'];

// Admin Routes - Settings
$routes->get('rest_api_settings', 'Rest_api_settings::index', $rest_api);
$routes->post('rest_api_settings/(:any)', 'Rest_api_settings::$1', $rest_api);
$routes->get('rest_api_settings/(:any)', 'Rest_api_settings::$1', $rest_api);

// Admin Routes - API Keys
$routes->get('api_keys', 'Api_keys::index', $rest_api);
$routes->post('api_keys/(:any)', 'Api_keys::$1', $rest_api);
$routes->get('api_keys/(:any)', 'Api_keys::$1', $rest_api);

// Admin Routes - Permission Groups
$routes->get('api_permission_groups', 'Api_permission_groups::index', $rest_api);
$routes->post('api_permission_groups/(:any)', 'Api_permission_groups::$1', $rest_api);
$routes->get('api_permission_groups/(:any)', 'Api_permission_groups::$1', $rest_api);

// Admin Routes - API Logs
$routes->get('api_logs', 'Api_logs::index', $rest_api);
$routes->post('api_logs/(:any)', 'Api_logs::$1', $rest_api);
$routes->get('api_logs/(:any)', 'Api_logs::$1', $rest_api);

// Admin Routes - Swagger Documentation
$routes->get('swagger/spec', 'Swagger::spec', $rest_api);
$routes->get('swagger/ui', 'Swagger::ui', $rest_api);
$routes->get('swagger/debug', 'Swagger::debug', $rest_api);
$routes->get('swagger/clear_cache', 'Swagger::clear_cache', $rest_api);

// ====================================
// REST API Endpoints (v1)
// ====================================

// Users API
$routes->get('api/v1/users', 'Api\Users::index', ['namespace' => 'Rest_api\Controllers']);
$routes->get('api/v1/users/(:num)', 'Api\Users::show/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->post('api/v1/users', 'Api\Users::create', ['namespace' => 'Rest_api\Controllers']);
$routes->put('api/v1/users/(:num)', 'Api\Users::update/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->delete('api/v1/users/(:num)', 'Api\Users::delete/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->options('api/v1/users', 'Api\Users::index', ['namespace' => 'Rest_api\Controllers']); // CORS preflight
$routes->options('api/v1/users/(:any)', 'Api\Users::show/$1', ['namespace' => 'Rest_api\Controllers']); // CORS preflight

// Projects API
$routes->get('api/v1/projects', 'Api\Projects::index', ['namespace' => 'Rest_api\Controllers']);
$routes->get('api/v1/projects/(:num)', 'Api\Projects::show/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->post('api/v1/projects', 'Api\Projects::create', ['namespace' => 'Rest_api\Controllers']);
$routes->put('api/v1/projects/(:num)', 'Api\Projects::update/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->delete('api/v1/projects/(:num)', 'Api\Projects::delete/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->options('api/v1/projects', 'Api\Projects::index', ['namespace' => 'Rest_api\Controllers']); // CORS preflight
$routes->options('api/v1/projects/(:any)', 'Api\Projects::show/$1', ['namespace' => 'Rest_api\Controllers']); // CORS preflight

// Tasks API
$routes->get('api/v1/tasks', 'Api\Tasks::index', ['namespace' => 'Rest_api\Controllers']);
$routes->get('api/v1/tasks/(:num)', 'Api\Tasks::show/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->post('api/v1/tasks', 'Api\Tasks::create', ['namespace' => 'Rest_api\Controllers']);
$routes->put('api/v1/tasks/(:num)', 'Api\Tasks::update/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->delete('api/v1/tasks/(:num)', 'Api\Tasks::delete/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->options('api/v1/tasks', 'Api\Tasks::index', ['namespace' => 'Rest_api\Controllers']); // CORS preflight
$routes->options('api/v1/tasks/(:any)', 'Api\Tasks::show/$1', ['namespace' => 'Rest_api\Controllers']); // CORS preflight

// Clients API (handles both clients and leads via is_lead flag)
$routes->get('api/v1/clients', 'Api\Clients::index', ['namespace' => 'Rest_api\Controllers']);
$routes->get('api/v1/clients/(:num)', 'Api\Clients::show/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->post('api/v1/clients', 'Api\Clients::create', ['namespace' => 'Rest_api\Controllers']);
$routes->put('api/v1/clients/(:num)', 'Api\Clients::update/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->delete('api/v1/clients/(:num)', 'Api\Clients::delete/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->post('api/v1/clients/(:num)/convert', 'Api\Clients::convert/$1', ['namespace' => 'Rest_api\Controllers']); // Convert lead to client
$routes->options('api/v1/clients', 'Api\Clients::index', ['namespace' => 'Rest_api\Controllers']); // CORS preflight
$routes->options('api/v1/clients/(:any)', 'Api\Clients::show/$1', ['namespace' => 'Rest_api\Controllers']); // CORS preflight

// Invoices API
$routes->get('api/v1/invoices', 'Api\Invoices::index', ['namespace' => 'Rest_api\Controllers']);
$routes->get('api/v1/invoices/(:num)', 'Api\Invoices::show/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->post('api/v1/invoices', 'Api\Invoices::create', ['namespace' => 'Rest_api\Controllers']);
$routes->put('api/v1/invoices/(:num)', 'Api\Invoices::update/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->delete('api/v1/invoices/(:num)', 'Api\Invoices::delete/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->options('api/v1/invoices', 'Api\Invoices::index', ['namespace' => 'Rest_api\Controllers']); // CORS preflight
$routes->options('api/v1/invoices/(:any)', 'Api\Invoices::show/$1', ['namespace' => 'Rest_api\Controllers']); // CORS preflight

// Estimates API
$routes->get('api/v1/estimates', 'Api\Estimates::index', ['namespace' => 'Rest_api\Controllers']);
$routes->get('api/v1/estimates/(:num)', 'Api\Estimates::show/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->post('api/v1/estimates', 'Api\Estimates::create', ['namespace' => 'Rest_api\Controllers']);
$routes->put('api/v1/estimates/(:num)', 'Api\Estimates::update/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->delete('api/v1/estimates/(:num)', 'Api\Estimates::delete/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->options('api/v1/estimates', 'Api\Estimates::index', ['namespace' => 'Rest_api\Controllers']); // CORS preflight
$routes->options('api/v1/estimates/(:any)', 'Api\Estimates::show/$1', ['namespace' => 'Rest_api\Controllers']); // CORS preflight

// Proposals API
$routes->get('api/v1/proposals', 'Api\Proposals::index', ['namespace' => 'Rest_api\Controllers']);
$routes->get('api/v1/proposals/(:num)', 'Api\Proposals::show/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->post('api/v1/proposals', 'Api\Proposals::create', ['namespace' => 'Rest_api\Controllers']);
$routes->put('api/v1/proposals/(:num)', 'Api\Proposals::update/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->delete('api/v1/proposals/(:num)', 'Api\Proposals::delete/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->options('api/v1/proposals', 'Api\Proposals::index', ['namespace' => 'Rest_api\Controllers']); // CORS preflight
$routes->options('api/v1/proposals/(:any)', 'Api\Proposals::show/$1', ['namespace' => 'Rest_api\Controllers']); // CORS preflight

// Contracts API
$routes->get('api/v1/contracts', 'Api\Contracts::index', ['namespace' => 'Rest_api\Controllers']);
$routes->get('api/v1/contracts/(:num)', 'Api\Contracts::show/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->post('api/v1/contracts', 'Api\Contracts::create', ['namespace' => 'Rest_api\Controllers']);
$routes->put('api/v1/contracts/(:num)', 'Api\Contracts::update/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->delete('api/v1/contracts/(:num)', 'Api\Contracts::delete/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->options('api/v1/contracts', 'Api\Contracts::index', ['namespace' => 'Rest_api\Controllers']); // CORS preflight
$routes->options('api/v1/contracts/(:any)', 'Api\Contracts::show/$1', ['namespace' => 'Rest_api\Controllers']); // CORS preflight

// Expenses API
$routes->get('api/v1/expenses', 'Api\Expenses::index', ['namespace' => 'Rest_api\Controllers']);
$routes->get('api/v1/expenses/(:num)', 'Api\Expenses::show/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->post('api/v1/expenses', 'Api\Expenses::create', ['namespace' => 'Rest_api\Controllers']);
$routes->put('api/v1/expenses/(:num)', 'Api\Expenses::update/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->delete('api/v1/expenses/(:num)', 'Api\Expenses::delete/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->options('api/v1/expenses', 'Api\Expenses::index', ['namespace' => 'Rest_api\Controllers']); // CORS preflight
$routes->options('api/v1/expenses/(:any)', 'Api\Expenses::show/$1', ['namespace' => 'Rest_api\Controllers']); // CORS preflight

// Tickets API
$routes->get('api/v1/tickets', 'Api\Tickets::index', ['namespace' => 'Rest_api\Controllers']);
$routes->get('api/v1/tickets/(:num)', 'Api\Tickets::show/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->post('api/v1/tickets', 'Api\Tickets::create', ['namespace' => 'Rest_api\Controllers']);
$routes->put('api/v1/tickets/(:num)', 'Api\Tickets::update/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->delete('api/v1/tickets/(:num)', 'Api\Tickets::delete/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->options('api/v1/tickets', 'Api\Tickets::index', ['namespace' => 'Rest_api\Controllers']); // CORS preflight
$routes->options('api/v1/tickets/(:any)', 'Api\Tickets::show/$1', ['namespace' => 'Rest_api\Controllers']); // CORS preflight

// Timesheets API
$routes->get('api/v1/timesheets', 'Api\Timesheets::index', ['namespace' => 'Rest_api\Controllers']);
$routes->get('api/v1/timesheets/(:num)', 'Api\Timesheets::show/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->post('api/v1/timesheets', 'Api\Timesheets::create', ['namespace' => 'Rest_api\Controllers']);
$routes->put('api/v1/timesheets/(:num)', 'Api\Timesheets::update/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->delete('api/v1/timesheets/(:num)', 'Api\Timesheets::delete/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->options('api/v1/timesheets', 'Api\Timesheets::index', ['namespace' => 'Rest_api\Controllers']); // CORS preflight
$routes->options('api/v1/timesheets/(:any)', 'Api\Timesheets::show/$1', ['namespace' => 'Rest_api\Controllers']); // CORS preflight

// Events API
$routes->get('api/v1/events', 'Api\Events::index', ['namespace' => 'Rest_api\Controllers']);
$routes->get('api/v1/events/(:num)', 'Api\Events::show/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->post('api/v1/events', 'Api\Events::create', ['namespace' => 'Rest_api\Controllers']);
$routes->put('api/v1/events/(:num)', 'Api\Events::update/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->delete('api/v1/events/(:num)', 'Api\Events::delete/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->options('api/v1/events', 'Api\Events::index', ['namespace' => 'Rest_api\Controllers']); // CORS preflight
$routes->options('api/v1/events/(:any)', 'Api\Events::show/$1', ['namespace' => 'Rest_api\Controllers']); // CORS preflight

// Notes API
$routes->get('api/v1/notes', 'Api\Notes::index', ['namespace' => 'Rest_api\Controllers']);
$routes->get('api/v1/notes/(:num)', 'Api\Notes::show/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->post('api/v1/notes', 'Api\Notes::create', ['namespace' => 'Rest_api\Controllers']);
$routes->put('api/v1/notes/(:num)', 'Api\Notes::update/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->delete('api/v1/notes/(:num)', 'Api\Notes::delete/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->options('api/v1/notes', 'Api\Notes::index', ['namespace' => 'Rest_api\Controllers']); // CORS preflight
$routes->options('api/v1/notes/(:any)', 'Api\Notes::show/$1', ['namespace' => 'Rest_api\Controllers']); // CORS preflight

// Messages API
$routes->get('api/v1/messages', 'Api\Messages::index', ['namespace' => 'Rest_api\Controllers']);
$routes->get('api/v1/messages/(:num)', 'Api\Messages::show/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->post('api/v1/messages', 'Api\Messages::create', ['namespace' => 'Rest_api\Controllers']);
$routes->delete('api/v1/messages/(:num)', 'Api\Messages::delete/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->options('api/v1/messages', 'Api\Messages::index', ['namespace' => 'Rest_api\Controllers']); // CORS preflight
$routes->options('api/v1/messages/(:any)', 'Api\Messages::show/$1', ['namespace' => 'Rest_api\Controllers']); // CORS preflight

// Notifications API (Read-only with mark_read action)
$routes->get('api/v1/notifications', 'Api\Notifications::index', ['namespace' => 'Rest_api\Controllers']);
$routes->get('api/v1/notifications/(:num)', 'Api\Notifications::show/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->post('api/v1/notifications/(:num)/mark_read', 'Api\Notifications::mark_read/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->options('api/v1/notifications', 'Api\Notifications::index', ['namespace' => 'Rest_api\Controllers']); // CORS preflight
$routes->options('api/v1/notifications/(:any)', 'Api\Notifications::show/$1', ['namespace' => 'Rest_api\Controllers']); // CORS preflight

// Announcements API
$routes->get('api/v1/announcements', 'Api\Announcements::index', ['namespace' => 'Rest_api\Controllers']);
$routes->get('api/v1/announcements/(:num)', 'Api\Announcements::show/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->post('api/v1/announcements', 'Api\Announcements::create', ['namespace' => 'Rest_api\Controllers']);
$routes->put('api/v1/announcements/(:num)', 'Api\Announcements::update/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->delete('api/v1/announcements/(:num)', 'Api\Announcements::delete/$1', ['namespace' => 'Rest_api\Controllers']);
$routes->options('api/v1/announcements', 'Api\Announcements::index', ['namespace' => 'Rest_api\Controllers']); // CORS preflight
$routes->options('api/v1/announcements/(:any)', 'Api\Announcements::show/$1', ['namespace' => 'Rest_api\Controllers']); // CORS preflight
