<?php
declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
    $file = __DIR__ . $path;
    if ($path !== '/' && (is_file($file) || (is_dir($file) && is_file(rtrim($file, '/') . '/index.php')))) {
        return false;
    }
}

require_once __DIR__ . '/../includes/bootstrap.php';

// If the app isn't configured yet, send the user to the installer.
if (!is_file(BASE_PATH . '/config/installed.lock') && is_file(__DIR__ . '/install/index.php')) {
    header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/install/');
    exit;
}

$router = new Router();

// ---- public marketing landing -------------------------------------
$router->add('home', 'HomeController', 'index');

// ---- auth ----------------------------------------------------------
$router->add('login',    'AuthController', 'login');
$router->add('register', 'AuthController', 'register');
$router->add('logout',   'AuthController', 'logout');
$router->add('forgot',   'AuthController', 'forgot');
$router->add('reset',    'AuthController', 'reset');
$router->add('verify',   'AuthController', 'verify');

// ---- public legal pages -------------------------------------------
$router->add('legal/terms',          'LegalController', 'terms');
$router->add('legal/privacy',        'LegalController', 'privacy');
$router->add('legal/acceptable-use', 'LegalController', 'acceptableUse');

// ---- dashboard -----------------------------------------------------
$router->add('dashboard', 'DashboardController', 'index');
$router->add('search',    'SearchController', 'quick');

// ---- smtp ----------------------------------------------------------
$router->add('smtp',              'SmtpController', 'index');
$router->add('smtp/store',        'SmtpController', 'store');
$router->add('smtp/test',            'SmtpController', 'test');
$router->add('smtp/test-connection', 'SmtpController', 'testConnection');
$router->add('smtp/toggle',       'SmtpController', 'toggle');
$router->add('smtp/delete',       'SmtpController', 'delete');
$router->add('smtp/group/store',  'SmtpController', 'storeGroup');
$router->add('smtp/group/delete', 'SmtpController', 'deleteGroup');

// ---- contacts ------------------------------------------------------
$router->add('contacts',             'ContactController', 'index');
$router->add('contacts/store',       'ContactController', 'store');
$router->add('contacts/delete',      'ContactController', 'delete');
$router->add('contacts/bulk-delete', 'ContactController', 'bulkDelete');
$router->add('contacts/dedupe',      'ContactController', 'dedupe');
$router->add('contacts/clean-invalid','ContactController', 'cleanInvalid');
$router->add('contacts/verify',      'ContactController', 'verify');
$router->add('contacts/verify-reset','ContactController', 'verifyReset');
$router->add('contacts/verify-one',  'ContactController', 'verifyOne');
$router->add('contacts/import',      'ContactController', 'import');
$router->add('contacts/lists',       'ContactController', 'lists');
$router->add('contacts/list/store',  'ContactController', 'storeList');
$router->add('contacts/list/delete', 'ContactController', 'deleteList');
$router->add('contacts/tag/store',   'ContactController', 'storeTag');

// ---- templates -----------------------------------------------------
$router->add('templates',         'TemplateController', 'index');
$router->add('templates/edit',    'TemplateController', 'edit');
$router->add('templates/store',   'TemplateController', 'store');
$router->add('templates/delete',  'TemplateController', 'delete');
$router->add('templates/preview', 'TemplateController', 'preview');

// ---- email verifier ------------------------------------------------
$router->add('email-verifier',       'EmailVerifyController', 'index');
$router->add('email-verifier/check', 'EmailVerifyController', 'check');
$router->add('email-verifier/bulk',  'EmailVerifyController', 'bulk');

// ---- campaigns -----------------------------------------------------
$router->add('campaigns',          'CampaignController', 'index');
$router->add('campaigns/edit',     'CampaignController', 'edit');
$router->add('campaigns/store',    'CampaignController', 'store');
$router->add('campaigns/show',     'CampaignController', 'show');
$router->add('campaigns/sheet-preview', 'CampaignController', 'sheetPreview');
$router->add('campaigns/test',     'CampaignController', 'sendTest');
$router->add('campaigns/send',     'CampaignController', 'sendNow');
$router->add('campaigns/schedule', 'CampaignController', 'schedule');
$router->add('campaigns/unschedule', 'CampaignController', 'unschedule');
$router->add('campaigns/reopen',   'CampaignController', 'reopen');

$router->add('billing',            'BillingController', 'index');
$router->add('billing/request',    'BillingController', 'request');
$router->add('billing/checkout',   'BillingController', 'checkout');
$router->add('billing/callback',   'BillingController', 'callback');
$router->add('campaigns/pause',    'CampaignController', 'pause');
$router->add('campaigns/resume',   'CampaignController', 'resume');
$router->add('campaigns/delete',   'CampaignController', 'delete');

// ---- automations ---------------------------------------------------
$router->add('automations',          'AutomationController', 'index');
$router->add('automations/edit',     'AutomationController', 'edit');
$router->add('automations/store',    'AutomationController', 'store');
$router->add('automations/activate', 'AutomationController', 'activate');
$router->add('automations/pause',    'AutomationController', 'pause');
$router->add('automations/delete',   'AutomationController', 'delete');

// ---- reports & settings -------------------------------------------
$router->add('reports',           'ReportController',   'index');
$router->add('suppression',        'SuppressionController', 'index');
$router->add('suppression/add',    'SuppressionController', 'add');
$router->add('suppression/remove', 'SuppressionController', 'remove');
$router->add('emails',            'MailLogController',  'index');
$router->add('emails/view',       'MailLogController',  'view');
$router->add('settings',          'SettingsController', 'index');
$router->add('settings/profile',  'SettingsController', 'profile');
$router->add('settings/password', 'SettingsController', 'password');
$router->add('settings/theme',    'SettingsController', 'theme');
$router->add('settings/imap',     'SettingsController', 'imap');

// ---- admin ---------------------------------------------------------
$router->add('admin',              'AdminController', 'index');
$router->add('admin/users',        'AdminController', 'users');
$router->add('admin/users/toggle', 'AdminController', 'toggleUser');
$router->add('admin/users/plan',   'AdminController', 'setPlan');
$router->add('admin/plans',        'AdminController', 'plans');
$router->add('admin/plans/store',  'AdminController', 'storePlan');
$router->add('admin/plans/delete', 'AdminController', 'deletePlan');
$router->add('admin/requests',         'AdminController', 'requests');
$router->add('admin/requests/approve', 'AdminController', 'approveRequest');
$router->add('admin/requests/reject',  'AdminController', 'rejectRequest');
$router->add('admin/branding',         'AdminController', 'branding');
$router->add('admin/branding/store',   'AdminController', 'storeBranding');
$router->add('admin/site',             'AdminController', 'site');
$router->add('admin/site/store',       'AdminController', 'storeSite');
$router->add('admin/logs',         'AdminController', 'logs');

$route = (string) ($_GET['r'] ?? '');
if ($route === '') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    $route = trim(rawurldecode($path), '/');
}
$router->dispatch($route);
