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
                " . $app['db']->quoteIdentifier('major_tasks') . ",
                " . $app['db']->quoteIdentifier('normal_bugs') . ",
                " . $app['db']->quoteIdentifier('normal_tasks') . "
                FROM " . $app['db']->quoteIdentifier('samples') . "
                WHERE " . $app['db']->quoteIdentifier('version')  ." = 8
                ORDER BY " . $app['db']->quoteIdentifier('when') . " ASC
        ";
        $results = $app['db']->query($sql);

        $data = array();
        $dataKeys = array(
            'critical_bugs',
            'critical_tasks',
            'major_bugs',
            'major_tasks',
            'normal_bugs',
            'normal_tasks',
        );
        while ($resultRow = $results->fetch(\PDO::FETCH_ASSOC)) {
            $dataRow = array(
                'when' => $resultRow['when']
            );
            foreach ($dataKeys as $key) {
              $dataRow[$key] = isset($resultRow[$key])? ((int) $resultRow[$key]) : null;
            }
            $data[] = $dataRow;
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
                'estimate' => $row['estimate'],
            );
        }

        return $app->json($data);
    }

    public function distribution(Application $app, Request $request) {

        $date = $request->get('date');

        $dateCondition = '';
        if (!empty($date)) {
            $dateCondition = "AND " . $app['db']->quoteIdentifier('when') . "=" . $app['db']->quote($date);
        }

        $sql = "
            SELECT
                " . $app['db']->quoteIdentifier('when') . ",
                " . $app['db']->quoteIdentifier('estimate') . ",
                " . $app['db']->quoteIdentifier('data') . "
                FROM " . $app['db']->quoteIdentifier('estimates') . "
                WHERE " . $app['db']->quoteIdentifier('version')  ." = 8
                " . $dateCondition . "
                ORDER BY " . $app['db']->quoteIdentifier('when') . " DESC
                LIMIT 1
        ";
        $results = $app['db']->query($sql);

        if ($row = $results->fetch(\PDO::FETCH_ASSOC)) {
            $data = unserialize($row['data']);
            foreach ($data as $key => $count) {
                $data[$key] = array(
                    'when' => date('Y-m-d H:i:s', strtotime($row['when'] . " +" . $key . " seconds")),
                    'count' => $count,
                );
            }

            return $app->json($data);
        }

        // TODO return failure response.
    }
}
