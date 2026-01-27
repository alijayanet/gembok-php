<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
// The Auto Routing (Legacy) is very dangerous. It is easy to create vulnerable apps
// where controller filters or CSRF protection are bypassed.
// If you don't want to define all routes, please use the Auto Routing (Improved).
// Set `$autoRoutesImproved` to true in `app/Config/Feature.php` and set the following to true.
// $routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Home::index');

/*
 * --------------------------------------------------------------------
 * API Routes
 * --------------------------------------------------------------------
 */
$routes->get('api/onuLocations', 'Api::onuLocations');
$routes->get('api/onu/detail', 'Api::onuDetail');
$routes->post('api/onu/add', 'Api::addOnu');
$routes->post('api/onu/wifi', 'Api::updateWifi');
$routes->post('api/whatsapp/webhook', 'Api::whatsappWebhook');

// Realtime Data API
$routes->get('api/dashboard/stats', 'Api::dashboardStats');
$routes->get('api/analytics/summary', 'Api::analyticsSummary');
$routes->get('api/invoices/recent', 'Api::recentInvoices');

// Pagination API
$routes->get('api/customers/list', 'Api::customersList');
$routes->get('api/invoices/list', 'Api::invoicesList');
$routes->get('api/mikrotik/users', 'Api::mikrotikUsers');

/*
 * --------------------------------------------------------------------
 * Admin Routes
 * --------------------------------------------------------------------
 */
// Admin Authentication (NOT protected by AdminFilter)
$routes->get('admin/login', 'AdminAuth::login');
$routes->post('admin/login', 'AdminAuth::authenticate');
$routes->get('admin/logout', 'AdminAuth::logout');

// Protected Admin Routes (require login)
$routes->group('admin', ['filter' => 'admin'], function($routes) {
    $routes->get('/', 'Admin::index');
    $routes->get('dashboard', 'Admin::index');
    $routes->get('analytics', 'Admin::analytics');
    $routes->get('genieacs', 'Admin::genieacs');
    $routes->get('map', 'Admin::map');
    $routes->get('odp', 'Admin::odp');
    $routes->get('mikrotik', 'Admin::mikrotik');
    $routes->get('mikrotik/profiles', 'Admin::mikrotikProfiles');
    $routes->get('mikrotik/hotspot-profiles', 'Admin::hotspotProfiles');
    $routes->post('mikrotik/action', 'Admin::mikrotikAction');
    $routes->get('hotspot', 'Admin::hotspot');
    $routes->get('hotspot/voucher', 'Admin::voucher');
    
    // Trouble Ticket
    $routes->get('trouble', 'Admin::trouble');
    $routes->post('trouble/create', 'Admin::createTicket');
    $routes->post('trouble/update/(:num)', 'Admin::updateTicket/$1');
    $routes->post('trouble/assign/(:num)', 'Admin::assignTicket/$1');
    $routes->post('trouble/close/(:num)', 'Admin::closeTicket/$1');

    // Technician Management
    $routes->get('technicians', 'Technician::list');
    $routes->post('technicians/add', 'Technician::add');
    $routes->get('technician/dashboard', 'Technician::index');
    $routes->get('technician/genieacs', 'Technician::genieacs');
    $routes->get('technician/map', 'Technician::map');
    $routes->get('technician/getOnuData', 'Technician::getOnuData');
    $routes->post('technician/updateStatus', 'Technician::updateStatus');
    
    // Billing Routes

    $routes->group('billing', function($routes) {
        $routes->get('/', 'Billing::index');
        $routes->get('invoices', 'Billing::invoices');
        $routes->post('generate', 'Billing::generateInvoices'); 
        $routes->post('pay/(:num)', 'Billing::payInvoice/$1');
        $routes->post('unisolate_only/(:num)', 'Billing::unisolateOnly/$1'); // New Route
        $routes->get('print/(:num)', 'Billing::printInvoice/$1');
        $routes->get('cron/isolir', 'Billing::checkIsolation');
        
        $routes->get('packages', 'Billing::packages');
        $routes->post('packages/add', 'Billing::addPackage');
        $routes->post('packages/update/(:num)', 'Billing::updatePackage/$1');
        $routes->get('packages/delete/(:num)', 'Billing::deletePackage/$1');
        
        $routes->get('customers', 'Billing::customers');
        $routes->post('customers/add', 'Billing::addCustomer');
        $routes->post('customers/edit/(:num)', 'Billing::editCustomer/$1');
        $routes->get('customers/delete/(:num)', 'Billing::deleteCustomer/$1');
        $routes->get('customers/unisolate/(:num)', 'Billing::unisolateManual/$1');
        
        // Export/Import Customers
        $routes->get('customers/export', 'Billing::exportCustomers');
        $routes->get('customers/template', 'Billing::downloadTemplate');
        $routes->post('customers/import', 'Billing::importCustomers');
    });

    $routes->get('setting', 'AdminSettings::index');
    $routes->get('settings', 'AdminSettings::index');
    $routes->post('settings/save', 'AdminSettings::save');
    $routes->post('settings/profile', 'AdminSettings::updateProfile');
    $routes->post('settings/password', 'AdminSettings::changePassword');
    $routes->post('command', 'Admin::handleCommand');
    // Telegram webhook management
    $routes->post('settings/setTelegramWebhook', 'AdminSettings::setTelegramWebhook');
    $routes->post('settings/deleteTelegramWebhook', 'AdminSettings::deleteTelegramWebhook');
    
    // System Update
    $routes->get('update', 'Admin::update');
    $routes->post('update/run', 'Admin::runUpdate');
});

/*
 * --------------------------------------------------------------------
 * Cron Routes (Protected by Key in Controller)
 * --------------------------------------------------------------------
 */
$routes->get('cron/run/(:segment)', 'Billing::cronHandler/$1');

/*
 * --------------------------------------------------------------------
 * Customer Portal Routes
 * --------------------------------------------------------------------
 */
$routes->get('portal', 'Portal::index');
$routes->get('portal/dashboard', 'Portal::index'); // Alias for portal
$routes->get('portal/logout', 'Portal::logout'); // Add logout route
$routes->get('portal/tos', 'Portal::tos');
$routes->get('portal/invoices', 'Portal::invoices');
$routes->get('portal/payment/(:num)', 'Portal::payment/$1'); // Payment page
$routes->post('portal/processPayment', 'Portal::processPayment'); // Process payment
$routes->get('portal/wifi', 'Portal::editWifi');
$routes->post('portal/wifi', 'Portal::editWifi');

// AJAX endpoints for WiFi settings
$routes->post('portal/updateSsid', 'Portal::updateSsid');
$routes->post('portal/updatePassword', 'Portal::updatePassword');
$routes->post('portal/changePortalPassword', 'Portal::changePortalPassword'); // New Route
$routes->post('portal/reportTrouble', 'Portal::reportTrouble');

/*
 * --------------------------------------------------------------------
 * Settings Routes
 * --------------------------------------------------------------------
 */
$routes->get('admin/settings/integrations', 'Settings::integrations');

/*
 * --------------------------------------------------------------------
 * Debug Routes (⚠️ REMOVE IN PRODUCTION!)
 * --------------------------------------------------------------------
 * These routes are for development/debugging only.
 * IMPORTANT: Comment out or delete these lines before deploying to production!
 */
// $routes->get('debug/genieacs', 'Debug::genieacs');       // UNCOMMENT FOR DEBUG ONLY
// $routes->get('diagnostic/pppoeMatch', 'Diagnostic::pppoeMatch'); // UNCOMMENT FOR DEBUG ONLY

/*
 * --------------------------------------------------------------------
 * Webhook Routes
 * --------------------------------------------------------------------
 */
$routes->post('webhook/payment', 'Webhook::payment');
$routes->post('webhook/whatsapp', 'Webhook::whatsapp');
$routes->post('webhook/midtrans', 'Webhook::midtrans');
$routes->post('webhook/telegram', 'Webhook::telegram'); // Telegram Bot Webhook
$routes->get('webhook/telegram', 'Webhook::telegram');  // GET for testing


/*
 * --------------------------------------------------------------------
 * Authentication Routes
 * --------------------------------------------------------------------
 */
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::authenticate');
$routes->get('logout', 'Auth::logout');

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
