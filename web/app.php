<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use DC\FlotLib\FlotTimeLine;

use DC\BankingAnalytics\Importer\Nordea;

require_once __DIR__.'/../app/bootstrap.php';

$app = require_once __DIR__.'/../app/app.php';

// global stuff
$app->before(function () use ($app) {

    $app['balance'] = $app['db']->fetchColumn("SELECT SUM( amount ) AS total FROM transaction");

});

$app->get('/', function() use ($app) {

    // #################################################################################################################
    // Overall stuff
    // get balance
    $at_balance = $app['balance'];

    // get total transactions
    $at_totalTransactions = $app['db']->fetchColumn("SELECT COUNT(*) AS total FROM transaction");

    // total days
    $at_totalDays = $app['db']->fetchColumn("
        SELECT DATEDIFF( CURDATE() , (

        SELECT transaction_date
        FROM transaction
        ORDER BY transaction_date ASC
        LIMIT 1
        ) )
    ");

    // total untagged
    $untagged = $app['db']->fetchColumn("
        SELECT COUNT(*) as total FROM place WHERE category_id = 0
    ");

    // #################################################################################################################
    // This months' stuff

    // figure out the month
    $ym = date('Y-m') . '-01';
    $ym = '2012-02';

    // how much have we spent this month?
    $monthExpenditure = $app['db']->fetchColumn("
        SELECT ABS(SUM(amount)) FROM transaction WHERE transaction_date LIKE ? AND amount < 0
    ", array($ym . '-%'));

    // how much have we recieved this month
    $monthIncome = $app['db']->fetchColumn("
        SELECT SUM(amount) FROM transaction WHERE transaction_date LIKE ? AND amount > 0
    ", array($ym . '-%'));
    if(empty($monthIncome)) { $monthIncome = 0; }

    // total transactions this month
    $monthtotalTransactions = $app['db']->fetchColumn("SELECT COUNT(*) AS total FROM transaction WHERE transaction_date LIKE ?", array($ym . '-%'));

    // most recent transactions
    $recent = $app['db']->fetchAll("
        SELECT
            t.*
            ,p.place
            ,c.title as category
        FROM
            transaction as t
        LEFT JOIN
            place as p ON t.place_id = p.place_id
        LEFT JOIN
            category as c ON c.category_id = p.category_id
        ORDER BY t.transaction_date DESC
        LIMIT 10
    ");

    return $app['twig']->render('index.html.twig', array(
        'at_balance' => $at_balance
        ,'at_total_transactions' => $at_totalTransactions
        ,'at_total_days' => $at_totalDays
        ,'at_untagged' => $untagged
        ,'month_out' => $monthExpenditure
        ,'month_in' => $monthIncome
        ,'month_total_transactions' => $monthtotalTransactions
        ,'month' => date('F')
        ,'recent' => $recent
    ));
});

$app->match('/transactions', function(Request $request) use ($app) {

    $catdata = $app['db']->fetchAll("SELECT category_id, title FROM category ORDER BY title ASC");
    $options = array();
    foreach($catdata AS $c) {
        $options[$c['category_id']] = $c['title'];
    }

    $form = $app['form.factory']->createBuilder('form', array())
        ->add('amount', 'text', array('required' => false))
        ->add('beneficiary', 'text', array('required' => false))
        ->add('category', 'choice', array('required' => false, 'choices' => $options))
        ->getForm();

    $filters = 'WHERE 1=1';
    $params = array();
    if ('POST' == $request->getMethod()) {
        $form->bind($request);

        $data = $form->getData();

        // amount
        if($data['amount'] != null) {
            $filters .= ' AND (t.amount = ? OR t.amount = ?)';
            $params[] = $data['amount'];
            $params[] = '-'.$data['amount'];
        }

        // beneficiary
        if($data['beneficiary'] != null) {
            $filters .= ' AND p.place LIKE ?';
            $params[] = '%'.$data['beneficiary'].'%';
        }

        // category
        if($data['category']) {
            $filters .= ' AND p.category_id = ?';
            $params[] = $data['category'];
        }
    }

    // calculate total before
    $transactions = $app['db']->fetchAll("
        SELECT
            t.*
            ,c.title as category
            ,p.place
        FROM
            transaction as t
        LEFT JOIN place as p ON p.place_id = t.place_id
        LEFT JOIN category as c ON p.category_id = c.category_id
        $filters
        ORDER BY
            t.transaction_date DESC
    ", $params);

    return $app['twig']->render('transactions.html.twig', array(
       'transactions' => $transactions
        ,'form' => $form->createView()
    ));
})->method('GET|POST');

$app->get('/reports/{report}', function($report = '') use ($app) {

    $graph = '';

    switch($report) {

        case 'topspend':

            $title = 'You have spent most at:';

            $data = $app['db']->fetchAll("
                SELECT
                    SUM(IF(t.amount<0,(t.amount*-1),0)) as total
                    , t.place_id

                FROM
                    transaction as t
                WHERE
                    t.amount < 0
                GROUP BY
                    t.place_id
                ORDER BY
                    total DESC
                LIMIT 10
            ");
            var_dump($data);die;

        break;

        case 'categories':

            $title = 'Where you spend the most money';

            $data = $app['db']->fetchAll("
                SELECT
                    SUM(t.amount) as total
                    ,c.title as category
                FROM
                    transaction as t
                LEFT JOIN
                    place as p ON p.place_id = t.place_id
                LEFT JOIN
                    category AS c ON c.category_id = p.category_id
                WHERE t.amount < 0
                GROUP BY
                    c.category_id
                ORDER BY
                    total ASC
            ");

        break;

        case 'balance':

            $title = 'Your balance over time';

            $data = $app['db']->fetchAll("
              SELECT
			    SUM(amount) AS balance
			    , transaction_date as yearmonth
		      FROM
			    transaction
		      GROUP BY
			    yearmonth
		      ORDER BY
			    yearmonth ASC
            ");

            $flot = new DC\FlotLib\FlotTimeLine('100%');
            $flot->addSet('balance', 'Balance');
            $total = 0.00;
            foreach($data AS $d) {
                $total += $d['balance'];
                $flot->addData('balance', strtotime($d['yearmonth'])*1000, $total);
            }
            $graph = $flot->draw();

        break;

        case 'monthly':
        default:

            $title = 'Monthly Expenditure';

            $data = $app['db']->fetchAll("
           		SELECT
			      SUM(IF(amount<0,amount,0)) AS monthly_total
			      , DATE_FORMAT(transaction_date, '%Y-%m') as yearmonth
		        FROM
			      transaction
		        GROUP BY
			      yearmonth
		        ORDER BY
			      yearmonth ASC
            ");

            $flot = new DC\FlotLib\FlotTimeLine('100%');
            $flot->addSet('monthly_spend', 'Monthly expenditure');
            foreach($data AS $d) {
                $flot->addData('monthly_spend', strtotime($d['yearmonth'])*1000, $d['monthly_total']*-1);
            }
            $graph = $flot->draw();


        break;
    }

    return $app['twig']->render('reports.html.twig', array(
        'report' => $report
        ,'report_title' => $title
        ,'data' => $data
        ,'graph' => $graph
    ));
});

$app->get('/tags/untagged', function() use ($app) {

    $untagged = $app['db']->fetchAll("SELECT place_id, place FROM place WHERE category_id = 0 ORDER BY place_id DESC");

    // categories
    $categories = $app['db']->fetchAll("SELECT * FROM category ORDER BY title ASC");

    return $app['twig']->render('tags/untagged.html.twig', array('untagged' => $untagged, 'categories' => $categories));
});

$app->match('/import', function(Request $request) use ($app) {

    $form = $app['form.factory']->createBuilder('form', array())
        ->add('bank', 'choice', array('choices' => array('Nordea')))
        ->add('file', 'file')
        ->getForm();

    if ('POST' == $request->getMethod()) {
        $form->bind($request);

        $data = $form->getData();#

        switch($data['bank']) {
            case 'nordea':

                $path = '../tmp';
                $filename = 'import.txt';
                $file = $data['file']->move($path, $filename);

                $importer = new DC\BankingAnalytics\Importer\Nordea($app['db']);
                $importer->process($path . '/'. $filename);

            break;
        }
    }

    return $app['twig']->render('import.html.twig', array('form' => $form->createView()));
})->method('GET|POST');

$app->post('/tags/tag', function (Request $request) use ($app) {

    $statement = $app['db']->prepare("UPDATE place SET category_id = ? WHERE place_id = ?");
    $statement->bindValue(1, $request->get('category_id'));
    $statement->bindValue(2, $request->get('place_id'));
    $statement->execute();

    $response = array(
      'success' => 1
    );

    return $app->json($response);
});

$app->run();

