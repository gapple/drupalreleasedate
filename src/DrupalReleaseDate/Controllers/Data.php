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
        $lastQuery = $app['db']->createQueryBuilder()
            ->select('s.when')
            ->from('samples', 's')
            ->where('version = 8')
            ->orderBy($app['db']->quoteIdentifier('when'), 'DESC')
            ->setMaxResults(1);
        $lastResults = $lastQuery->execute();
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

        $query = $app['db']->createQueryBuilder()
            ->select(
                's.when',
                's.critical_bugs', 's.critical_tasks',
                's.major_bugs', 's.major_tasks',
                's.normal_bugs', 's.normal_tasks'
            )
            ->from('samples', 's')
            ->where('version = 8')
            ->orderBy($app['db']->quoteIdentifier('when'), 'ASC');

        $results = $query->execute();

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
            'current' => null,
            'day' => null,
            'week' => null,
            'month' => null,
            'quarter' => null,
            'half' => null,
        );

        $nowQuery = $app['db']->createQueryBuilder()
            ->select('s.when', 's.critical_bugs', 's.critical_tasks')
            ->from('samples', 's')
            ->where('version = 8')
            ->orderBy($app['db']->quoteIdentifier('when'), 'DESC')
            ->setMaxResults(1);

        $nowResult = $nowQuery->execute();
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

            $nowIssues = $nowResultRow['critical_bugs'] + $nowResultRow['critical_tasks'];
            $critical['current'] = $nowIssues;

            $dayQuery = $app['db']->createQueryBuilder()
                ->select('s.critical_bugs', 's.critical_tasks')
                ->from('samples', 's')
                ->where('version = 8')
                ->andWhere($app['db']->quoteIdentifier('when') . ' < DATE_SUB( :now , INTERVAL 1 DAY)')
                ->orderBy($app['db']->quoteIdentifier('when'), 'DESC')
                ->setMaxResults(1)
                ->setParameter('now', $nowResultRow['when']);
            $dayResult = $dayQuery->execute();

            if ($dayResultRow = $dayResult->fetch(\PDO::FETCH_ASSOC))
            {
                $critical['day'] = $nowIssues - ($dayResultRow['critical_bugs'] + $dayResultRow['critical_tasks']);
            }

            $weekQuery = $app['db']->createQueryBuilder()
                ->select('s.critical_bugs', 's.critical_tasks')
                ->from('samples', 's')
                ->where('version = 8')
                ->andWhere($app['db']->quoteIdentifier('when') . ' < DATE_SUB( :now , INTERVAL 1 WEEK)')
                ->orderBy($app['db']->quoteIdentifier('when'), 'DESC')
                ->setMaxResults(1)
                ->setParameter('now', $nowResultRow['when']);
            $weekResult = $weekQuery->execute();

            if ($weekResultRow = $weekResult->fetch(\PDO::FETCH_ASSOC))
            {
                $critical['week'] = $nowIssues - ($weekResultRow['critical_bugs'] + $weekResultRow['critical_tasks']);
            }

            $monthQuery = $app['db']->createQueryBuilder()
                ->select('s.critical_bugs', 's.critical_tasks')
                ->from('samples', 's')
                ->where('version = 8')
                ->andWhere($app['db']->quoteIdentifier('when') . ' < DATE_SUB( :now , INTERVAL 1 MONTH)')
                ->orderBy($app['db']->quoteIdentifier('when'), 'DESC')
                ->setMaxResults(1)
                ->setParameter('now', $nowResultRow['when']);
            $monthResult = $monthQuery->execute();

            if ($monthResultRow = $monthResult->fetch(\PDO::FETCH_ASSOC))
            {
                $critical['month'] = $nowIssues - ($monthResultRow['critical_bugs'] + $monthResultRow['critical_tasks']);
            }

            $quarterQuery = $app['db']->createQueryBuilder()
                ->select('s.critical_bugs', 's.critical_tasks')
                ->from('samples', 's')
                ->where('version = 8')
                ->andWhere($app['db']->quoteIdentifier('when') . ' < DATE_SUB( :now , INTERVAL 3 MONTH)')
                ->orderBy($app['db']->quoteIdentifier('when'), 'DESC')
                ->setMaxResults(1)
                ->setParameter('now', $nowResultRow['when']);
            $quarterResult = $quarterQuery->execute();

            if ($quarterResultRow = $quarterResult->fetch(\PDO::FETCH_ASSOC))
            {
                $critical['quarter'] = $nowIssues - ($quarterResultRow['critical_bugs'] + $quarterResultRow['critical_tasks']);
            }

            $halfQuery = $app['db']->createQueryBuilder()
                ->select('s.critical_bugs', 's.critical_tasks')
                ->from('samples', 's')
                ->where('version = 8')
                ->andWhere($app['db']->quoteIdentifier('when') . ' < DATE_SUB( :now , INTERVAL 6 MONTH)')
                ->orderBy($app['db']->quoteIdentifier('when'), 'DESC')
                ->setMaxResults(1)
                ->setParameter('now', $nowResultRow['when']);
            $halfResult = $halfQuery->execute();

            if ($halfResultRow = $halfResult->fetch(\PDO::FETCH_ASSOC))
            {
                $critical['half'] = $nowIssues - ($halfResultRow['critical_bugs'] + $halfResultRow['critical_tasks']);
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
        $responseData = array();

        // Check against Last-Modified header.
        $lastQuery = $app['db']->createQueryBuilder()
            ->select('e.when', 'e.estimate')
            ->from('estimates', 'e')
            ->where('version = 8')
            ->orderBy($app['db']->quoteIdentifier('when'), 'DESC')
            ->setMaxResults(1);
        $lastResults = $lastQuery->execute();
        $lastDate = null;
        if ($lastResultRow = $lastResults->fetch(\PDO::FETCH_ASSOC))
        {
            $lastDate = new \DateTime($lastResultRow['when']);
            $responseData['modified'] = $lastResultRow['when'];

            $response = new Response();
            $response->setLastModified($lastDate);
            $response->setPublic();

            if ($response->isNotModified($request))
            {
                // Return 304 Not Modified response.
                return $response;
            }
        }

        $queryBuilder = $app['db']->createQueryBuilder()
            ->select('e.when', 'e.estimate')
            ->from('estimates', 'e')
            ->where('version = 8')
            ->orderBy($app['db']->quoteIdentifier('when'), 'ASC');

        if ($request->query->has('limit'))
        {
            $limit = $request->query->getInt('limit');
            $responseData['limit'] = $limit;
            $queryBuilder
                ->setMaxResults($limit)
                ->orderBy($app['db']->quoteIdentifier('when'), 'DESC');
        }

        $results = $queryBuilder->execute();

        $responseData['data'] = array();
        while ($resultRow = $results->fetch(\PDO::FETCH_ASSOC))
        {
            $responseData['data'][] = array(
                'when' => $resultRow['when'],
                'estimate' => $resultRow['estimate'],
            );
        }

        if (isset($limit)) {
          $responseData['data'] = array_reverse($responseData['data']);
        }

        $response = $app->json($responseData);

        if ($lastDate)
        {
            $response->setLastModified($lastDate);
        }

        return $response;
    }

    public function distribution(Application $app, Request $request) {

        $query = $app['db']->createQueryBuilder()
            ->select('e.when', 'e.estimate', 'e.data')
            ->from('estimates', 'e')
            ->where('version = 8')
            ->orderBy($app['db']->quoteIdentifier('when'), 'DESC')
            ->setMaxResults(1);

        if ($request->query->has('date')) {
          $query
            ->andWhere($app['db']->quoteIdentifier('when') . ' = :when')
            ->setParameter('when', $request->query->get('date'), \PDO::PARAM_STR);
        }

        $results = $query->execute();

        if ($row = $results->fetch(\PDO::FETCH_ASSOC)) {

            $estimateDate = new \DateTime($row['when']);

            $response = new Response();
            $response->setLastModified($estimateDate);
            $response->setPublic();

            if ($response->isNotModified($request))
            {
                // Return 304 Not Modified response.
                return $response;
            }

            if (!empty($row['data'])) {
                $data = unserialize($row['data']);
                foreach ($data as $key => $count) {
                    $data[$key] = array(
                        'when' => date('Y-m-d H:i:s', strtotime($row['when'] . " +" . $key . " seconds")),
                        'count' => $count,
                    );
                }
            }
            else {
                $data = null;
            }

            $response = $app->json(array(
                'modified' => $row['when'],
                'data' => $data,
            ));

            if ($estimateDate)
            {
              $response->setLastModified($estimateDate);
            }

            return $response;
        }

        // TODO return failure response.
    }
}
