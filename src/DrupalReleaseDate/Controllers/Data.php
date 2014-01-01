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

        $response = $app->json($data);

        if ($lastDate)
        {
            $response->setLastModified($lastDate);
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

        $response = $app->json($data);

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

            return $app->json($data);
        }

        // TODO return failure response.
    }
}
