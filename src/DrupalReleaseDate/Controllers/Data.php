<?php
namespace DrupalReleaseDate\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class Data
{

    public function samples(Application $app, Request $request)
    {
        $sql = "
            SELECT
                " . $app['db']->quoteIdentifier('when') . ",
                " . $app['db']->quoteIdentifier('critical_bugs') . ",
                " . $app['db']->quoteIdentifier('critical_tasks') . ",
                " . $app['db']->quoteIdentifier('major_bugs') . ",
                " . $app['db']->quoteIdentifier('major_tasks') . "
                FROM " . $app['db']->quoteIdentifier('samples') . "
                WHERE " . $app['db']->quoteIdentifier('version')  ." = 8
                ORDER BY " . $app['db']->quoteIdentifier('when') . " ASC
        ";
        $results = $app['db']->query($sql);

        $data = array();
        while ($row = $results->fetch(\PDO::FETCH_ASSOC)) {
            $data[] = array(
                'when' => $row['when'],
                'timestamp' => strtotime($row['when']),
                'critical_bugs' => (int) $row['critical_bugs'],
                'critical_tasks' => (int) $row['critical_tasks'],
                'major_bugs' => (int) $row['major_bugs'],
                'major_tasks' => (int) $row['major_tasks'],
            );
        }

        return $app->json($data);
    }

    public function estimates(Application $app, Request $request)
    {
        $sql = "
            SELECT
                " . $app['db']->quoteIdentifier('when') . ",
                " . $app['db']->quoteIdentifier('estimate') . "
                FROM " . $app['db']->quoteIdentifier('estimates') . "
                WHERE " . $app['db']->quoteIdentifier('version')  ." = 8
                ORDER BY " . $app['db']->quoteIdentifier('when') . " ASC
        ";
        $results = $app['db']->query($sql);

        $data = array();
        while ($row = $results->fetch(\PDO::FETCH_ASSOC)) {
            $data[] = array(
                'when' => $row['when'],
                'timestamp' => strtotime($row['when']),
                'estimate' => $row['estimate'],
                'estimate_timestamp' => strtotime($row['estimate']),
            );
        }

        return $app->json($data);
    }
}
