<?php
// Copy this file to config.php and fill in your details.
return [
    // ---- Database (cPanel → MySQL Databases) ----
    'db_dsn'  => 'mysql:host=localhost;dbname=CPANELUSER_gridtool;charset=utf8mb4',
    'db_user' => 'CPANELUSER_gridtool',
    'db_pass' => 'CHANGE_ME',

    // ---- DataForSEO API (app.dataforseo.com → API Access) ----
    'dfs_login'    => 'your-dataforseo-login@email.com',
    'dfs_password' => 'your-dataforseo-api-password',
    // true = fake rankings for testing without spending credits. Set to false when your API login is filled in.
    'dfs_mock'     => true,
    'language_code' => 'en',
    'map_zoom'      => 14,      // Google Maps zoom used for each grid point
    'search_depth'  => 20,      // positions checked per point; not found = "20+"

    // ---- Free plan limits ----
    'free_scans_per_month' => 5,
    'max_businesses'       => 3,
    'grid_sizes'           => [3, 5, 7, 9],
    'spacings_miles'       => [0.5, 1, 2, 3],

    // ---- Site ----
    'site_name'  => 'Zahir Islam · Local SEO Grid Tool',
    'main_site'  => '/',
    'whatsapp'   => '8801644842785',
    'admin_email' => 'islamzahirul7048@gmail.com', // gets a notification when someone signs up (uses PHP mail())
    'countries' => [ // DataForSEO / Google location codes
        2050 => 'Bangladesh', 2784 => 'United Arab Emirates', 2826 => 'United Kingdom',
        2840 => 'United States', 2792 => 'Turkey', 2356 => 'India',
    ],
];
