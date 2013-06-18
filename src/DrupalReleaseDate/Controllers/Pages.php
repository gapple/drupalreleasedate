<?php
namespace DrupalReleaseDate\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class Pages
{

    public function index(Application $app, Request $request)
    {
        $estimate = array(
            'value' => 'N/A',
            'note' => 'The latest estimate could not be retrieved',
        );

        $sql = "
        SELECT " . $app['db']->quoteIdentifier('estimate') . "
            FROM " . $app['db']->quoteIdentifier('estimates') . "
            WHERE " . $app['db']->quoteIdentifier('version')  ." = 8
            ORDER BY " . $app['db']->quoteIdentifier('when') . " DESC
        ";
        $result = $app['db']->fetchColumn($sql, array(), 0);

        if ($result == null || $result == '0000-00-00 00:00:00') {
            $estimate['note'] = 'An estimate could not be calculated with the current data';
        }
        else if ($result) {
            $estimate['value'] = date('F j, Y', strtotime($result . ' +6 weeks'));
            $estimate['note'] = '';
        }

        return $app['twig']->render('index.twig', array(
            'estimate' => $estimate,
        ));
    }

    public function about(Application $app, Request $request)
    {
        return $app['twig']->render('about.twig', array());
    }
}
