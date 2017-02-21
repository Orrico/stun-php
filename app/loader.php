<?php

/**
 * Bootstrap to Estupendo/Stun application.
 *
 * This file loads and set up the environment.
 *
 * @package Estupendo/Stun
 */

/** Load configuration file if exists. */
if (file_exists(STUN_PATH . '/app/config.php')) {
    $config = include STUN_PATH . '/app/config.php';
}
else {
    $config = include STUN_PATH . '/app/config-sample.php';
}

/** Load application core settings */
require_once STUN_PATH . '/app/settings.php';

/** Default timezone used by all date/time functions. */
date_default_timezone_set($config['timezone']);

define('STUN_DEBUG', $config['debug']);

/**
* Slim Framework
*
*/
$config['displayErrorDetails'] = STUN_DEBUG;
$app = new \Slim\App(['settings' => $config]);

/** Slim Containers */
$container = $app->getContainer();

/** Mustache Views Containers */
$container['views'] = new \Mustache_Engine(array(
    'template_class_prefix' => '__stunThemes_',
    // 'cache' => STUN_THEME . '/tmp/cache/mustache',
    // 'cache_file_mode' => 0755, // Please, configure your umask instead of doing this :)
    // 'cache_lambda_templates' => false,
    'strict_callables' => false,
    'loader' => new \Mustache_Loader_FilesystemLoader(STUN_THEME, array('extension' => '.php')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(STUN_THEME . '/inc', array('extension' => '.php')),
    'pragmas' => [\Mustache_Engine::PRAGMA_FILTERS],
    'logger' => new \Mustache_Logger_StreamLogger('php://stderr'),
    'charset' => 'UTF-8',
    'escape' => function($value) {
        return htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
    },
    'helpers' => array('i18n' => function($text) {
        // do something translatey here...
    })
));

$container['adminViews'] = new \Mustache_Engine(array(
    'template_class_prefix' => '__stunAdminTheme_',
    // 'cache' => STUN_THEME . '/tmp/cache/mustache',
    // 'cache_file_mode' => 0755, // Please, configure your umask instead of doing this :)
    // 'cache_lambda_templates' => false,
    'strict_callables' => false,
    'loader' => new \Mustache_Loader_FilesystemLoader(STUN_ADMIN_TEMPLATE, array('extension' => '.php')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(STUN_ADMIN_TEMPLATE . '/inc', array('extension' => '.php')),
    'pragmas' => [\Mustache_Engine::PRAGMA_FILTERS],
    'logger' => new \Mustache_Logger_StreamLogger('php://stderr'),
    'charset' => 'UTF-8',
    'escape' => function($value) {
        return htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
    },
    'helpers' => array('i18n' => function($text) {
        // do something translatey here...
    })
));

/** Override the default Not Found Handler */
$container['notFoundHandler'] = function ($c) {
    return function ($request, $response) use ($c) {
        return $c['response']
            ->withStatus(404)
            ->withHeader('Content-Type', 'text/html')
            ->write('Page not found');
    };
};

// $container['slim-views'] = new \Slim\Views\PhpRenderer(STUN_THEME);

/** Logger Container */
$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler(STUN_PATH . '/app/app.log');
    $logger->pushHandler($file_handler);
    return $logger;
};

/** Database Container */
$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO('mysql:host=' . $db['dbhost'] . ';port=' . $db['dbport'] . ';dbname=' . $db['dbname'] . ';charset=' . $db['charset'] . ';', $db['dbuser'], $db['dbpass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    /** RedBeanPHP4 connection */
    \R::setup('mysql:host=' . $db['dbhost'] . ';port=' . $db['dbport'] . ';dbname=' . $db['dbname'] . ';charset=' . $db['charset'] . ';', $db['dbuser'], $db['dbpass']);

    \R::fancyDebug(STUN_DEBUG);

    /** Return PDO connection in alternative to RedBeanPHP4 */
    return $pdo;
};

/** Load application routes */
require_once STUN_PATH . '/app/routes.php';

/** Run Slim Framework */
$app->run();
