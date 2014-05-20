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
        if ($lastResultRow = $lastResults->fetch(\PDO::FETCH_ASSOC)) {
            $lastDate = new \DateTime($lastResultRow['when']);

            $response = new Response();
            $response->setLastModified($lastDate);
            $response->setPublic();

            if ($response->isNotModified($request)) {
                // Return 304 Not Modified response.
                return $response;
            }
        }

        $sampleValuesResult = $app['db']->createQueryBuilder()
            ->select(
                'sv.when',
                'sv.key',
                'sv.value'
            )
            ->from('sample_values', 'sv')
            ->where('version = 8')
            ->orderBy($app['db']->quoteIdentifier('when'), 'ASC')
            ->execute();

        $data = array();
        while ($sampleValueRow = $sampleValuesResult->fetch(\PDO::FETCH_ASSOC)) {
            if (!isset($data[$sampleValueRow['when']])) {
                $data[$sampleValueRow['when']] = array(
                    'when' => $sampleValueRow['when'],
                );
            }
            $data[$sampleValueRow['when']][$sampleValueRow['key']] = $app['db']->convertToPhpValue($sampleValueRow['value'], 'smallint');
        }
        $response = $app->json(
            array(
                'modified' => $lastResultRow['when'],
                'data' => array_values($data),
            )
        );

        if ($lastDate) {
            $response->setLastModified($lastDate);
        }

        return $response;
    }

    /**
     * Get sample values at set periods back in time.
     */
    public function historical(Application $app, Request $request)
    {
        $data = array();

        $nowDate = null;
        $currentSample = $app['db']->createQueryBuilder()
            ->select('s.when')
            ->from('samples', 's')
            ->where('version = 8')
            ->orderBy($app['db']->quoteIdentifier('when'), 'DESC')
            ->setMaxResults(1)
            ->execute()
            ->fetch(\PDO::FETCH_ASSOC);

        if ($currentSample) {
            $nowDate = new \DateTime($currentSample['when']);

            $response = new Response();
            $response->setLastModified($nowDate);
            $response->setPublic();

            if ($response->isNotModified($request)) {
                // Return 304 Not Modified response.
                return $response;
            }

            $periods = array(
                'current' => null,
                'day' => '1 DAY',
                'week' => '1 WEEK',
                'month' => '1 MONTH',
                'quarter' => '3 MONTH',
                'half' => '6 MONTH',
                'year' => '1 YEAR',
            );
            foreach ($periods as $periodKey => $periodInterval) {

                $pastSampleQuery = $app['db']->createQueryBuilder()
                    ->select('s.version', 's.when')
                    ->from('samples', 's')
                    ->where('version = 8')
                    ->orderBy($app['db']->quoteIdentifier('when'), 'DESC')
                    ->setMaxResults(1);
                if ($periodInterval) {
                    $pastSampleQuery
                        ->andWhere($app['db']->quoteIdentifier('when') . ' < DATE_SUB( :now , INTERVAL ' . $periodInterval . ')')
                        ->setParameter('now', $currentSample['when']);
                }
                $pastSample = $pastSampleQuery
                    ->execute()
                    ->fetch(\PDO::FETCH_ASSOC);

                if ($pastSample) {
                    $pastSampleValuesResult = $app['db']->createQueryBuilder()
                        ->select('sv.key', 'sv.value')
                        ->from('sample_values', 'sv')
                        ->where($app['db']->quoteIdentifier('version') . ' = :version')
                        ->andWhere($app['db']->quoteIdentifier('when') . ' = :when')
                        ->setParameter('version', $pastSample['version'])
                        ->setParameter('when', $pastSample['when'])
                        ->execute();

                    while($pastSampleValue = $pastSampleValuesResult->fetch(\PDO::FETCH_ASSOC)) {
                        $data[$periodKey][$pastSampleValue['key']] = $app['db']->convertToPhpValue($pastSampleValue['value'], 'smallint');
                    }
                }
            }
        }

        $response = $app->json(
            array(
                'modified' => $currentSample['when'],
                'data' => $data,
            )
        );

        if ($nowDate) {
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
        if ($lastResultRow = $lastResults->fetch(\PDO::FETCH_ASSOC)) {
            $lastDate = new \DateTime($lastResultRow['when']);
            $responseData['modified'] = $lastResultRow['when'];

            $response = new Response();
            $response->setLastModified($lastDate);
            $response->setPublic();

            if ($response->isNotModified($request)) {
                // Return 304 Not Modified response.
                return $response;
            }
        }

        $queryBuilder = $app['db']->createQueryBuilder()
            ->select('e.when', 'e.estimate')
            ->from('estimates', 'e')
            ->where('version = 8')
            ->orderBy($app['db']->quoteIdentifier('when'), 'ASC');

        if ($request->query->has('limit')) {
            $limit = $request->query->getInt('limit');
            $responseData['limit'] = $limit;
            $queryBuilder
                ->setMaxResults($limit)
                ->orderBy($app['db']->quoteIdentifier('when'), 'DESC');
        }

        $results = $queryBuilder->execute();

        $responseData['data'] = array();
        while ($resultRow = $results->fetch(\PDO::FETCH_ASSOC)) {
            $responseData['data'][] = array(
                'when' => $resultRow['when'],
                'estimate' => $resultRow['estimate'],
            );
        }

        if (isset($limit)) {
            $responseData['data'] = array_reverse($responseData['data']);
        }

        $response = $app->json($responseData);

        if ($lastDate) {
            $response->setLastModified($lastDate);
        }

        return $response;
    }

    public function distribution(Application $app, Request $request)
    {

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

            if ($response->isNotModified($request)) {
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
            } else {
                $data = null;
            }

            $response = $app->json(
                array(
                    'modified' => $row['when'],
                    'data' => $data,
                )
            );

            if ($estimateDate) {
                $response->setLastModified($estimateDate);
            }

            return $response;
        }

        // TODO return failure response.
    }
}
