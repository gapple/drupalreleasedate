<?php
namespace DrupalReleaseDate\Controllers;

use DateTime;
use DateInterval;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Data
{

    public function samples(Application $app, Request $request)
    {
        $responseData = array();

        $from = null;
        if ($request->query->has('from')) {
            try {
                $from = new DateTime($request->query->get('from'));
                $responseData['from'] = $from->format(DateTime::ISO8601);
            }
            catch (\Exception $e) {
                $app->abort(400, 'Invalid "from" parameter');
            }
        }
        $to = null;
        if ($request->query->has('to')) {
            try {
                $to = new DateTime($request->query->get('to'));
                $responseData['to'] = $to->format(DateTime::ISO8601);
            }
            catch (\Exception $e) {
                $app->abort(400, 'Invalid "to" parameter');
            }
        }

        if ($from && $to && $from->diff($to)->invert) {
            $app->abort(400, 'Invalid "from" and "to" parameters');
        }

        // Check against Last-Modified header.
        $lastQuery = $app['db']->createQueryBuilder()
            ->select('s.when')
            ->from('samples', 's')
            ->where('version = 8')
            ->orderBy($app['db']->quoteIdentifier('when'), 'DESC')
            ->setMaxResults(1);
        if ($from) {
            $lastQuery
                ->andWhere('s.when >= :from')
                ->setParameter('from', $app['db']->convertToDatabaseValue($from, 'datetime'), \PDO::PARAM_STR);
        }
        if ($to) {
            $lastQuery
                ->andWhere('s.when <= :to')
                ->setParameter('to', $app['db']->convertToDatabaseValue($to, 'datetime'), \PDO::PARAM_STR);
        }

        $lastResults = $lastQuery->execute();
        $lastDate = null;
        if ($lastResultRow = $lastResults->fetch(\PDO::FETCH_ASSOC)) {
            $lastDate = new DateTime($lastResultRow['when']);
            $responseData['modified'] = $lastDate->format(DateTime::ISO8601);

            $response = new Response();
            $response->setLastModified($lastDate);
            $response->setPublic();

            if ($response->isNotModified($request)) {
                // Return 304 Not Modified response.
                return $response;
            }
        }

        $sampleValuesQuery = $app['db']->createQueryBuilder()
            ->select(
                'sv.when',
                'sv.key',
                'sv.value'
            )
            ->from('sample_values', 'sv')
            ->where('version = 8')
            ->orderBy($app['db']->quoteIdentifier('when'), 'ASC');
        if ($from) {
            $sampleValuesQuery
                ->andWhere('sv.when >= :from')
                ->setParameter('from', $app['db']->convertToDatabaseValue($from, 'datetime'), \PDO::PARAM_STR);
        }
        if ($to) {
            $sampleValuesQuery
                ->andWhere('sv.when <= :to')
                ->setParameter('to', $app['db']->convertToDatabaseValue($to, 'datetime'), \PDO::PARAM_STR);
        }
        $sampleValuesResult = $sampleValuesQuery->execute();

        $data = array();
        while ($sampleValueRow = $sampleValuesResult->fetch(\PDO::FETCH_ASSOC)) {
            $valueWhen = $app['db']->convertToPhpValue($sampleValueRow['when'], 'datetime');
            $valueWhenTimestamp = $valueWhen->getTimestamp();
            if (!isset($data[$valueWhenTimestamp])) {
                $data[$valueWhenTimestamp] = array(
                    'when' => $valueWhen->format(DateTime::ISO8601),
                );
            }
            $data[$valueWhenTimestamp][$sampleValueRow['key']] = $app['db']->convertToPhpValue($sampleValueRow['value'], 'smallint');
        }
        $responseData['data'] = array_values($data);

        $response = $app->json($responseData);

        if ($lastDate) {
            $response->setLastModified($lastDate);
        }

        // TODO if $to is in the past, this result can be cached indefinitely.

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
            $nowDate = new DateTime($currentSample['when']);

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
                'modified' => $nowDate->format(DateTime::ISO8601),
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

        $from = null;
        if ($request->query->has('from')) {
            try {
                $from = new DateTime($request->query->get('from'));
                $responseData['from'] = $from->format(DateTime::ISO8601);
            }
            catch (\Exception $e) {
                $app->abort(400, 'Invalid "from" parameter');
            }
        }
        $to = null;
        if ($request->query->has('to')) {
            try {
                $to = new DateTime($request->query->get('to'));
                $responseData['to'] = $to->format(DateTime::ISO8601);
            }
            catch (\Exception $e) {
                $app->abort(400, 'Invalid "to" parameter');
            }
        }

        if ($from && $to && $from->diff($to)->invert) {
            $app->abort(400, 'Invalid "from" and "to" parameters');
        }

        // Check against Last-Modified header.
        $lastQuery = $app['db']->createQueryBuilder()
            ->select('e.when', 'e.estimate')
            ->from('estimates', 'e')
            ->where('version = 8')
            ->andWhere('completed IS NOT NULL')
            ->orderBy($app['db']->quoteIdentifier('when'), 'DESC')
            ->setMaxResults(1);
        if ($from) {
            $lastQuery
                ->andWhere('e.when >= :from')
                ->setParameter('from', $app['db']->convertToDatabaseValue($from, 'datetime'), \PDO::PARAM_STR);
        }
        if ($to) {
            $lastQuery
                ->andWhere('e.when <= :to')
                ->setParameter('to', $app['db']->convertToDatabaseValue($to, 'datetime'), \PDO::PARAM_STR);
        }

        $lastResults = $lastQuery->execute();
        $lastDate = null;
        if ($lastResultRow = $lastResults->fetch(\PDO::FETCH_ASSOC)) {
            $lastDate = new DateTime($lastResultRow['when']);
            $responseData['modified'] = $lastDate->format(DateTime::ISO8601);

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
            ->andWhere('completed IS NOT NULL')
            ->orderBy($app['db']->quoteIdentifier('when'), 'ASC');
        if ($from) {
            $queryBuilder
                ->andWhere('e.when >= :from')
                ->setParameter('from', $app['db']->convertToDatabaseValue($from, 'datetime'), \PDO::PARAM_STR);
        }
        if ($to) {
            $queryBuilder
                ->andWhere('e.when <= :to')
                ->setParameter('to', $app['db']->convertToDatabaseValue($to, 'datetime'), \PDO::PARAM_STR);
        }

        if ($request->query->has('limit')) {
            $limit = $request->query->getInt('limit');
            if ($limit <= 0) {
                $app->abort(400, 'Invalid "limit" parameter');
            }
            $responseData['limit'] = $limit;
            $queryBuilder
                ->setMaxResults($limit)
                ->orderBy($app['db']->quoteIdentifier('when'), 'DESC');
        }

        $results = $queryBuilder->execute();

        $responseData['data'] = array();
        while ($resultRow = $results->fetch(\PDO::FETCH_ASSOC)) {
            $responseData['data'][] = array(
                'when' => $app['db']->convertToPhpValue($resultRow['when'], 'datetime')->format(DateTime::ISO8601),
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

        // TODO if $to is in the past, this result can be cached indefinitely.

        return $response;
    }

    public function distribution(Application $app, Request $request)
    {
        $responseData = array();

        $date = null;
        if ($request->query->has('date')) {
            try {
                $date = new DateTime($request->query->get('date'));
                $responseData['date'] = $date->format(DateTime::ISO8601);
            }
            catch (\Exception $e) {
                $app->abort(400, 'Invalid "date" parameter');
            }
        }

        $query = $app['db']->createQueryBuilder()
            ->select('e.when', 'e.estimate', 'e.data')
            ->from('estimates', 'e')
            ->where('version = 8')
            ->andWhere('completed IS NOT NULL')
            ->orderBy($app['db']->quoteIdentifier('when'), 'DESC')
            ->setMaxResults(1);

        if ($date) {
            $query
                ->andWhere('e.when = :when')
                ->setParameter('when', $app['db']->convertToDatabaseValue($date, 'datetime'), \PDO::PARAM_STR);
        }

        $results = $query->execute();

        if ($row = $results->fetch(\PDO::FETCH_ASSOC)) {

            $estimateDate = new DateTime($row['when']);
            $responseData['modified'] = $estimateDate->format(DateTime::ISO8601);

            $response = new Response();
            $response->setLastModified($estimateDate);
            $response->setPublic();

            if ($response->isNotModified($request)) {
                // Return 304 Not Modified response.
                return $response;
            }
            $data = array();

            if (!empty($row['data'])) {
                $estimateDistribution = unserialize($row['data']);

                foreach ($estimateDistribution as $key => $count) {
                    $dataDate = clone $estimateDate;
                    $dataDate->add(DateInterval::createFromDateString($key . ' seconds'));
                    $data[$key] = array(
                        'when' => $dataDate->format('Y-m-d'),
                        'count' => $count,
                    );
                }
            } else {
                $data = null;
            }
            $responseData['data'] = $data;

            $response = $app->json($responseData);

            if ($estimateDate) {
                $response->setLastModified($estimateDate);
            }

            return $response;
        }
        else if ($date) {
            // A specific date was requested, but no result was available.
            $app->abort(404, 'No data for requested date');
        }

        // TODO return failure response.
    }
}
