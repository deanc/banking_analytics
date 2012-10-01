<?php

require_once('../vendor/autoload.php');
if(file_exists('../app/config.php'))
{
    require_once('../app/config.php');
}
else {
    die('Make sure you copy config.default.php to config.php and fill in the details');
}

// register the setup app first
$setup = new Silex\Application();
$setup['debug'] = true;
$setup->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));

// collect the errors and succcess
$errors = $messages = array();

// now register the db tester
$setup->register(new Silex\Provider\DoctrineServiceProvider(), $config['db']);

// set up the setup routes
$setup->get('/', function () use ($setup) {

    $errors = $messages = array();

    // test the db connection
    try {
        $setup['db']->fetchColumn("SELECT transaction_date FROM transaction LIMIT 1");
        $messages[] = 'A database connection has been successfully established';
    }
    catch(PDOException $e) {
        $errors[] = 'A database connection could not be established. Please fix it in app/config.php';
    }

    // check mysql tables
    $done = false;
    try {
        $tables = array('category','place','transaction');
        foreach($tables AS $table) {
            $setup['db']->fetchAll("DESCRIBE $table");
        }

        $messages[] = 'It appears you have already imported the SQL file. You should delete this script if there are no other errors';
    } catch(PDOException $e) {
        $errors[] = 'You don\'t appear to have import the SQL file provided in app/import.sql as some tables are missing';
    }

    if(sizeof($errors) == 0) {
        $done = true;
    }

    return $setup['twig']->render('setup.html.twig', array(
        'errors' => $errors
        ,'messages' => $messages
        ,'done' => $done
    ));
});

$setup->run();

?>