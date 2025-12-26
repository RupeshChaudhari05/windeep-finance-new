<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes with
| underscores in the controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// Root redirect
$route['^$'] = 'admin/auth/index';

// Admin routes
$route['admin'] = 'admin/dashboard';
$route['admin/auth'] = 'admin/auth/index';
$route['admin/auth/login'] = 'admin/auth/login';
$route['admin/login'] = 'admin/auth/index';
$route['auth/login'] = 'admin/auth/index';
$route['login'] = 'admin/auth/index';
$route['admin/logout'] = 'admin/auth/logout';
$route['admin/dashboard'] = 'admin/dashboard/index';

// Member routes
$route['admin/members'] = 'admin/members/index';
$route['admin/members/create'] = 'admin/members/create';
$route['admin/members/view/(:num)'] = 'admin/members/view/$1';
$route['admin/members/edit/(:num)'] = 'admin/members/edit/$1';

// Savings routes
$route['admin/savings'] = 'admin/savings/index';
$route['admin/savings/create'] = 'admin/savings/create';
$route['admin/savings/view/(:num)'] = 'admin/savings/view/$1';
$route['admin/savings/collect/(:num)'] = 'admin/savings/collect/$1';

// Loans routes
$route['admin/loans'] = 'admin/loans/index';
$route['admin/loans/applications'] = 'admin/loans/applications';
$route['admin/loans/apply'] = 'admin/loans/apply';
$route['admin/loans/view/(:num)'] = 'admin/loans/view/$1';
$route['admin/loans/view-application/(:num)'] = 'admin/loans/view_application/$1';
$route['admin/loans/approve/(:num)'] = 'admin/loans/approve/$1';
$route['admin/loans/disburse/(:num)'] = 'admin/loans/disburse/$1';
$route['admin/loans/collect/(:num)'] = 'admin/loans/collect/$1';
$route['admin/loans/pending-approval'] = 'admin/loans/pending_approval';
$route['admin/loans/disbursement'] = 'admin/loans/disbursement';
$route['admin/loans/overdue'] = 'admin/loans/overdue';
$route['admin/loans/products'] = 'admin/loans/products';
$route['admin/loans/calculator'] = 'admin/loans/calculator';
$route['admin/loans/statement/(:num)'] = 'admin/loans/statement/$1';

// Fines routes
$route['admin/fines'] = 'admin/fines/index';
$route['admin/fines/create'] = 'admin/fines/create';
$route['admin/fines/view/(:num)'] = 'admin/fines/view/$1';
$route['admin/fines/collect/(:num)'] = 'admin/fines/collect/$1';
$route['admin/fines/rules'] = 'admin/fines/rules';
$route['admin/fines/waive/(:num)'] = 'admin/fines/waive/$1';
$route['admin/fines/cancel/(:num)'] = 'admin/fines/cancel/$1';

// Bank routes
$route['admin/bank/import'] = 'admin/bank/import';
$route['admin/bank/view_import/(:num)'] = 'admin/bank/view_import/$1';

// Reports routes
$route['admin/reports/collection'] = 'admin/reports/collection';
$route['admin/reports/outstanding'] = 'admin/reports/outstanding';
$route['admin/reports/ledger'] = 'admin/reports/ledger';

// Settings routes
$route['admin/settings'] = 'admin/settings/index';
$route['admin/settings/financial_years'] = 'admin/settings/financial_years';
$route['admin/settings/admin_users'] = 'admin/settings/admin_users';