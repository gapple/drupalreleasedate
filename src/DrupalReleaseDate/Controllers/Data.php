<?php
namespace DrupalReleaseDate\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Data
{

    public function samples(Application $app, Request $request)
    {
        // Check against Last-Modified header.
        $lastSql = "
            SELECT " . $app['db']->quoteIdentifier('when') . "
                FROM " . $app['db']->quoteIdentifier('samples') . "
                WHERE " . $app['db']->quoteIdentifier('version')  ." = 8
                ORDER BY " . $app['db']->quoteIdentifier('when') . " DESC
                LIMIT 1
        ";
        $lastResults = $app['db']->query($lastSql);
        $lastDate = null;
        if ($lastResultRow = $lastResults->fetch(\PDO::FETCH_ASSOC))
        {
            $lastDate = new \DateTime($lastResultRow['when']);

            $response = new Response();
            $response->setLastModified($lastDate);
            $response->setPublic();

            if ($response->isNotModified($request))
            {
                // Return 304 Not Modified response.
                return $response;
            }
        }

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
        while ($resultRow = $results->fetch(\PDO::FETCH_ASSOC))
        {
            $dataRow = array(
                'when' => $resultRow['when']
            );
            foreach ($dataKeys as $key)
            {
                $dataRow[$key] = isset($resultRow[$key])? ((int) $resultRow[$key]) : null;
            }
            $data[] = $dataRow;
        }

        $response = $app->json(array(
            'modified' => $lastResultRow['when'],
            'data' => $data,
        ));

        if ($lastDate)
        {
            $response->setLastModified($lastDate);
        }

        return $response;
    }

    /**
     * Get changes in the number of critical issues over recent periods in time.
     */
    public function changes(Application $app, Request $request)
    {

        $critical = array(
            'day' => null,
            'week' => null,
            'month' => null,
            'quarter' => null,
            'half' => null,
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
        $nowDate = null;

        if ($nowResultRow = $nowResult->fetch(\PDO::FETCH_ASSOC))
        {
            $nowDate = new \DateTime($nowResultRow['when']);

            $response = new Response();
            $response->setLastModified($nowDate);
            $response->setPublic();

            if ($response->isNotModified($request))
            {
              // Return 304 Not Modified response.
                return $response;
            }

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
                $critical['day'] = $nowIssues - $dayIssues;
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
                $critical['week'] = $nowIssues - $weekIssues;
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
                $critical['month'] = $nowIssues - $monthIssues;
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
                $critical['quarter'] = $nowIssues - $quarterIssues;
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
                $critical['half'] = $nowIssues - $halfIssues;
            }
        }

        $response = $app->json(array(
            'modified' => $nowResultRow['when'],
            'data' => array(
                'critical' => $critical,
            ),
        ));

        if ($nowDate)
        {
            $response->setLastModified($nowDate);
        }

        return $response;
    }

    public function estimates(Application $app, Request $request)
    {
        // Check against Last-Modified header.
        $lastSql = "
            SELECT " . $app['db']->quoteIdentifier('when') . "
                FROM " . $app['db']->quoteIdentifier('estimates') . "
                WHERE " . $app['db']->quoteIdentifier('version')  ." = 8
                ORDER BY " . $app['db']->quoteIdentifier('when') . " DESC
                LIMIT 1
        ";
        $lastResults = $app['db']->query($lastSql);
        $lastDate = null;
        if ($lastResultRow = $lastResults->fetch(\PDO::FETCH_ASSOC))
        {
            $lastDate = new \DateTime($lastResultRow['when']);

            $response = new Response();
            $response->setLastModified($lastDate);
            $response->setPublic();

            if ($response->isNotModified($request))
            {
                // Return 304 Not Modified response.
                return $response;
            }
        }

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
        while ($resultRow = $results->fetch(\PDO::FETCH_ASSOC))
        {
            $data[] = array(
                'when' => $resultRow['when'],
                'estimate' => $resultRow['estimate'],
            );
        }

        $response = $app->json(array(
            'modified' => $lastResultRow['when'],
            'data' => $data,
        ));

        if ($lastDate)
        {
            $response->setLastModified($lastDate);
        }

        return $response;
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

            $response = $app->json(array(
                'modified' => $lastResultRow['when'],
                'data' => $data,
            ));
        }

        // TODO return failure response.
    }
}
