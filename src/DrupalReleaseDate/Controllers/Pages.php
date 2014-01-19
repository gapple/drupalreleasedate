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

        if ($result == null || $result == '0000-00-00 00:00:00')
        {
            $estimate['note'] = 'An estimate could not be calculated with the current data';
        }
        else if ($result)
        {
            $estimate['value'] = date('F j, Y', strtotime($result . ' +6 weeks'));
            $estimate['note'] = '';
        }

        // Get critical issue changes over recent periods in time.
        $changes = array(
            'day' => '?',
            'week' => '?',
            'month' => '?',
            'quarter' => '?',
            'half' => '?',
        );
        $nowSql = "
        SELECT " . $app['db']->quoteIdentifier('when') . ",
            " . $app['db']->quoteIdentifier('critical_bugs') . " + " . $app['db']->quoteIdentifier('critical_tasks') . " AS critical
            FROM " . $app['db']->quoteIdentifier('samples') . "
            WHERE " . $app['db']->quoteIdentifier('version') . " = 8
            ORDER BY " . $app['db']->quoteIdentifier('when') . " DESC
            LIMIT 1
        ";
        $nowResult = $app['db']->query($nowSql);

        if ($nowResultRow = $nowResult->fetch(\PDO::FETCH_ASSOC))
        {
            $nowIssues = $nowResultRow['critical'];

            $daySql = "
            SELECT " . $app['db']->quoteIdentifier('critical_bugs') . " + " . $app['db']->quoteIdentifier('critical_tasks') . " AS critical
                FROM " . $app['db']->quoteIdentifier('samples') . "
                WHERE " . $app['db']->quoteIdentifier('when') . " < DATE_SUB('" . $nowResultRow['when'] . "', INTERVAL 1 DAY)
                    AND " . $app['db']->quoteIdentifier('version') . " = 8
                ORDER BY " . $app['db']->quoteIdentifier('when') . " DESC
                LIMIT 1

            ";
            $dayIssues = $app['db']->fetchColumn($daySql, array(), 0);

            if ($dayIssues)
            {
                $changes['day'] = $nowIssues - $dayIssues;
            }


            $weekSql = "
            SELECT " . $app['db']->quoteIdentifier('critical_bugs') . " + " . $app['db']->quoteIdentifier('critical_tasks') . " AS critical
                FROM " . $app['db']->quoteIdentifier('samples') . "
                WHERE " . $app['db']->quoteIdentifier('when') . " < DATE_SUB('" . $nowResultRow['when'] . "', INTERVAL 1 WEEK)
                    AND " . $app['db']->quoteIdentifier('version') . " = 8
                ORDER BY " . $app['db']->quoteIdentifier('when') . " DESC
                LIMIT 1

            ";
            $weekIssues = $app['db']->fetchColumn($weekSql, array(), 0);

            if ($weekIssues)
            {
                $changes['week'] = $nowIssues - $weekIssues;
            }


            $monthSql = "
            SELECT " . $app['db']->quoteIdentifier('critical_bugs') . " + " . $app['db']->quoteIdentifier('critical_tasks') . " AS critical
                FROM " . $app['db']->quoteIdentifier('samples') . "
                WHERE " . $app['db']->quoteIdentifier('when') . " < DATE_SUB('" . $nowResultRow['when'] . "', INTERVAL 1 MONTH)
                    AND " . $app['db']->quoteIdentifier('version') . " = 8
                ORDER BY " . $app['db']->quoteIdentifier('when') . " DESC
                LIMIT 1
            ";
            $monthIssues = $app['db']->fetchColumn($monthSql, array(), 0);

            if ($monthIssues)
            {
                $changes['month'] = $nowIssues - $monthIssues;
            }


            $quarterSql = "
            SELECT " . $app['db']->quoteIdentifier('critical_bugs') . " + " . $app['db']->quoteIdentifier('critical_tasks') . " AS critical
                FROM " . $app['db']->quoteIdentifier('samples') . "
                WHERE " . $app['db']->quoteIdentifier('when') . " < DATE_SUB('" . $nowResultRow['when'] . "', INTERVAL 3 MONTH)
                    AND " . $app['db']->quoteIdentifier('version') . " = 8
                ORDER BY " . $app['db']->quoteIdentifier('when') . " DESC
                LIMIT 1
            ";
            $quarterIssues = $app['db']->fetchColumn($quarterSql, array(), 0);

            if ($quarterIssues)
            {
                $changes['quarter'] = $nowIssues - $quarterIssues;
            }


            $halfSql = "
            SELECT " . $app['db']->quoteIdentifier('critical_bugs') . " + " . $app['db']->quoteIdentifier('critical_tasks') . " AS critical
                FROM " . $app['db']->quoteIdentifier('samples') . "
                WHERE " . $app['db']->quoteIdentifier('when') . " < DATE_SUB('" . $nowResultRow['when'] . "', INTERVAL 6 MONTH)
                    AND " . $app['db']->quoteIdentifier('version') . " = 8
                ORDER BY " . $app['db']->quoteIdentifier('when') . " DESC
                LIMIT 1
            ";
            $halfIssues = $app['db']->fetchColumn($halfSql, array(), 0);

            if ($halfIssues)
            {
                $changes['half'] = $nowIssues - $halfIssues;
            }
        }
        return $app['twig']->render('index.twig', array(
            'estimate' => $estimate,
            'changes' => $changes,
        ));
    }

    public function about(Application $app, Request $request)
    {
        return $app['twig']->render('about.twig', array());
    }
}
