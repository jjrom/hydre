<?php

/**
 * mapshup Server Domain name
 * (Usually = localhost)
 */
define("MSP_DOMAIN","localhost");

/**
 * Log directory : should be writable by webserver user
 * but not accessible from the Webserver (for security reasons)
 * !! Trailing "/" is MANDATORY !!
 */
define("MSP_LOG_DIR", "/Users/jrom/data/_logs/hydre/");

/**
 * Upload directory : should be writable by webserver user
 * but not accessible from the Webserver (to allow user to get the data)
 * !! Trailing "/" is MANDATORY !!
 */
define("MSP_UPLOAD_DIR", MSP_LOG_DIR);

/**
 * Directory to the UTFGRIDS
 */
define("MSP_MBTILES_DIR", "/Users/jrom/Devel/hydre/utfgrids/");

/**
 * Default number of results per page
 */
define("MSP_RESULTS_PER_PAGE", 20);

/**
 * Set debug mode - If true, all requests/responses are logged
 * (Default = false)
 */
define("MSP_DEBUG",false);

/**
 * Valid admin email adress (for registering)
 */
define("MSP_ADMIN_EMAIL","jrom@localhost");


/**
 * Default TimeZone for date computation
 */
define("MSP_TIMEZONE","Europe/Paris");

/**
 * If your webserver is behind a proxy set MSP_USE_PROXY to true
 * The MSP_PROXY_* parameters are only used if MSP_USE_PROXY
 * is set to true
 */
define("MSP_USE_PROXY", false);
define("MSP_PROXY_URL", "");
define("MSP_PROXY_PORT", "");
define("MSP_PROXY_USER", "");
define("MSP_PROXY_PASSWORD", "");

?>