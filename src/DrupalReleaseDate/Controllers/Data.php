<?php
namespace DrupalReleaseDate\Controllers;

use DateTime;
use DateInterval;

use Doctrine\DBAL\Connection;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Data
{

    /**
     * Parse a version string from the request's GET parameters.
     *
     * 'version' accepts either a string containing only a major version number,
     * or major and minor version numbers separated by a period.  If only a
     * major version is provided, the return value is normalized to include '0'
     * as the minor version.
     *
     * @param  Request $request
     * @return array
     *
     * @throws \Exception
     */
    public static function parseVersionFromRequest(Request $request)
    {
        if (($versionString = $request->query->get('version', false)) !== false) {
            if (!preg_match('/^([0-9]+)(\\.[0-9]+)?$/', $versionString)) {
                throw new \Exception("Invalid version parameter");
            }
            $segments = explode('.', $versionString);
            $major = $segments[0];
            $minor = empty($segments[1]) ? '0' : $segments[1];

            return array(
              'major' => $major,
              'minor' => $minor,
            );
        } elseif (($majorVersion = $request->query->get('version_major', false)) !== false) {
            $version = array(
              'major' => (int) $majorVersion,
            );

            if (($minorVersion = $request->query->get('version_minor', false)) !== false) {
                $version['minor'] = $minorVersion;
            }

            return $version;
        } elseif (($minorVersion = $request->query->get('version_minor', false)) !== false) {
            throw new \Exception("Major version must be specified when minor version is provided.");
        }

        return array(
          'major' => 8,
          'minor' => 0,
        );
    }

    /**
     * Parse a date from the request's GET parameter of the specified key.
     *
     * @param  Request $request
     * @param  string  $key
     * @return \DateTime
     */
    public static function parseDateFromRequest(Request $request, $key)
    {
        $value = null;
        if ($request->query->has($key)) {
            $value = new DateTime($request->query->get($key));
            $value->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }
        return $value;
    }

    public function samples(Application $app, Request $request)
    {
        /** @var Connection $db */
        $db = $app['db'];

        $responseData = array();
        try {
            $version = self::parseVersionFromRequest($request);
        } catch (\Exception $e) {
            $app->abort(400, 'Invalid version parameters');
        }
        $responseData['version_major'] = $version['major'];
        if (isset($version['minor'])) {
            $responseData['version_minor'] = $version['minor'];
        }


        try {
            if (($from = self::parseDateFromRequest($request, 'from'))) {
                $responseData['from'] = $from->format(DateTime::ATOM);
            }
        } catch (\Exception $e) {
            $app->abort(400, 'Invalid "from" parameter');
        }
        try {
            if (($to = self::parseDateFromRequest($request, 'to'))) {
                $responseData['to'] = $to->format(DateTime::ATOM);
            }
        } catch (\Exception $e) {
            $app->abort(400, 'Invalid "to" parameter');
        }

        if ($from && $to && $from->diff($to)->invert) {
            $app->abort(400, 'Invalid "from" and "to" parameters');
        }

        // Check against Last-Modified header.
        $lastQuery = $db->createQueryBuilder()
            ->select('s.when')
            ->from('samples', 's')
            ->where('s.version_major = :version_major')
            ->setParameter('version_major', $version['major'], \PDO::PARAM_INT)
            ->orderBy($db->quoteIdentifier('when'), 'DESC')
            ->setMaxResults(1);

        if (isset($version['minor'])) {
            $lastQuery
                ->andWhere('s.version_minor = :version_minor')
                ->setParameter('version_minor', $version['minor'], \PDO::PARAM_INT);
        }
        if ($from) {
            $lastQuery
                ->andWhere('s.when >= :from')
                ->setParameter('from', $db->convertToDatabaseValue($from, 'datetime'), \PDO::PARAM_STR);
        }
        if ($to) {
            $lastQuery
                ->andWhere('s.when <= :to')
                ->setParameter('to', $db->convertToDatabaseValue($to, 'datetime'), \PDO::PARAM_STR);
        }

        $lastResults = $lastQuery->execute();
        $lastDate = null;
        $cacheMaxAge = 3600;
        if (($lastResultRow = $lastResults->fetch(\PDO::FETCH_ASSOC))) {
            $lastDate = new DateTime($lastResultRow['when']);
            $responseData['modified'] = $lastDate->format(DateTime::ATOM);

            $nowDate = new DateTime();
            if ($to && $to < $nowDate) {
                // If the request limits to data in the past, we can set a very
                // long expiry since the results will never change.
                $cacheMaxAge = 31536000;
            } else {
                // Calculate cache max age based on the time to the next sample.
                $nextSampleDate = clone $lastDate;
                $nextSampleDate->add(new DateInterval('PT' . $app['config']['sample.interval'] . 'S'));
                $nextSampleInterval = $nextSampleDate->getTimestamp() - $nowDate->getTimestamp();
                $cacheMaxAge = max(900, $nextSampleInterval);
            }

            $response = new Response();
            $response->setLastModified($lastDate);
            $response->setPublic();

            if ($response->isNotModified($request)) {
                // Return 304 Not Modified response.
                $response->setMaxAge($cacheMaxAge);
                return $response;
            }
        }

        $sampleValuesQuery = $db->createQueryBuilder()
            ->select(
                'sv.version_major',
                'sv.version_minor',
                'sv.when',
                'sv.key',
                'sv.value'
            )
            ->from('sample_values', 'sv')
            ->where('sv.version_major = :version_major')
            ->setParameter('version_major', $version['major'], \PDO::PARAM_INT)
            ->orderBy($db->quoteIdentifier('when'), 'ASC');

        if (isset($version['minor'])) {
            $sampleValuesQuery
                ->andWhere('sv.version_minor = :version_minor')
                ->setParameter('version_minor', $version['minor'], \PDO::PARAM_INT);
        }
        if ($from) {
            $sampleValuesQuery
                ->andWhere('sv.when >= :from')
                ->setParameter('from', $db->convertToDatabaseValue($from, 'datetime'), \PDO::PARAM_STR);
        }
        if ($to) {
            $sampleValuesQuery
                ->andWhere('sv.when <= :to')
                ->setParameter('to', $db->convertToDatabaseValue($to, 'datetime'), \PDO::PARAM_STR);
        }

        if ($request->query->has('limit')) {
            $limit = $request->query->getInt('limit');
            if ($limit <= 0) {
                $app->abort(400, 'Invalid "limit" parameter');
            }
            $responseData['limit'] = $limit;

            $dateLimitQuery = $db->createQueryBuilder()
              ->select('s.when')
              ->from('samples', 's')
                ->where('s.version_major = :version_major')
                ->setParameter('version_major', $version['major'], \PDO::PARAM_INT)
              ->orderBy($db->quoteIdentifier('when'), 'DESC')
              ->setMaxResults($limit);

            if (isset($version['minor'])) {
                $dateLimitQuery
                    ->andWhere('s.version_minor = :version_minor')
                    ->setParameter('version_minor', $version['minor'], \PDO::PARAM_INT);
            }
            if ($from) {
                $dateLimitQuery
                  ->andWhere('s.when >= :from')
                  ->setParameter('from', $db->convertToDatabaseValue($from, 'datetime'), \PDO::PARAM_STR);
            }
            if ($to) {
                $dateLimitQuery
                  ->andWhere('s.when <= :to')
                  ->setParameter('to', $db->convertToDatabaseValue($to, 'datetime'), \PDO::PARAM_STR);
            }

            $dateLimitResults = $dateLimitQuery
              ->execute()
              ->fetchAll(\PDO::FETCH_ASSOC);

            $dateLimitResultMin = end($dateLimitResults);
            $dateLimitResultMin = $dateLimitResultMin['when'];
            $dateLimitResultMax = reset($dateLimitResults);
            $dateLimitResultMax = $dateLimitResultMax['when'];

            if (!$from) {
                $sampleValuesQuery
                  ->andWhere('sv.when >= :from');
            }
            if (!$to) {
                $sampleValuesQuery
                  ->andWhere('sv.when <= :to');
            }
            $sampleValuesQuery
                ->setParameter('from', $dateLimitResultMin, \PDO::PARAM_STR)
                ->setParameter('to', $dateLimitResultMax, \PDO::PARAM_STR);
        }

        $sampleValuesResult = $sampleValuesQuery->execute();

        $data = array();
        while (($sampleValueRow = $sampleValuesResult->fetch(\PDO::FETCH_ASSOC))) {
            $valueWhen = $db->convertToPhpValue($sampleValueRow['when'], 'datetime');
            $sampleKey = implode(array(
                $sampleValueRow['version_major'],
                $sampleValueRow['version_minor'],
                $valueWhen->getTimestamp(),
            ));
            if (!isset($data[$sampleKey])) {
                $data[$sampleKey] = array(
                    'version_major' => $sampleValueRow['version_major'],
                    'version_minor' => $sampleValueRow['version_minor'],
                    'when' => $valueWhen->format(DateTime::ATOM),
                );
            }
            $data[$sampleKey][$sampleValueRow['key']] = $db->convertToPhpValue($sampleValueRow['value'], 'smallint');
        }
        $responseData['data'] = array_values($data);

        $response = $app->json($responseData);

        if ($lastDate) {
            $response->setLastModified($lastDate);
        }

        $response->setMaxAge($cacheMaxAge);

        return $response;
    }

    /**
     * Get sample values at set periods back in time.
     */
    public function historical(Application $app, Request $request)
    {
        $responseData = array();

        try {
            $versionString = self::parseVersionFromRequest($request);
        }
        catch (\Exception $e) {
            $app->abort(400, 'Invalid "version" parameter');
        }
        $responseData['version'] = $versionString;


        $currentDate = null;
        $currentSample = $app['db']->createQueryBuilder()
            ->select('s.when')
            ->from('samples', 's')
            ->where('s.version = :version')
            ->setParameter('version', $app['db']->convertToDatabaseValue($versionString, 'string'), \PDO::PARAM_STR)
            ->orderBy($app['db']->quoteIdentifier('when'), 'DESC')
            ->setMaxResults(1)
            ->execute()
            ->fetch(\PDO::FETCH_ASSOC);

        $data = array();
        $cacheMaxAge = 3600;
        if ($currentSample) {
            $currentDate = new DateTime($currentSample['when']);
            $responseData['modified'] = $currentDate->format(DateTime::ATOM);

            // Calculate cache max age based on the time to the next sample.
            $nextSampleDate = clone $currentDate;
            $nextSampleDate->add(new DateInterval('PT' . $app['config']['sample.interval'] . 'S'));
            $nowDate = new DateTime();
            $nextSampleInterval = $nextSampleDate->getTimestamp() - $nowDate->getTimestamp();
            $cacheMaxAge = max(900, $nextSampleInterval);

            $response = new Response();
            $response->setLastModified($currentDate);
            $response->setPublic();

            if ($response->isNotModified($request)) {
                // Return 304 Not Modified response.
                $response->setMaxAge($cacheMaxAge);
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
                    ->where('s.version = :version')
                    ->setParameter('version', $app['db']->convertToDatabaseValue($versionString, 'string'), \PDO::PARAM_STR)
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
            $responseData['data'] = $data;
        }

        $response = $app->json($responseData);

        if ($currentDate) {
            $response->setLastModified($currentDate);
        }

        $response->setMaxAge($cacheMaxAge);

        return $response;
    }

    public function estimates(Application $app, Request $request)
    {
        $responseData = array();

        try {
            $versionString = self::parseVersionFromRequest($request);
        }
        catch (\Exception $e) {
            $app->abort(400, 'Invalid "version" parameter');
        }
        $responseData['version'] = $versionString;


        try {
            if ($from = self::parseDateFromRequest($request, 'from')) {
                $responseData['from'] = $from->format(DateTime::ATOM);
            }
        }
        catch (\Exception $e) {
            $app->abort(400, 'Invalid "from" parameter');
        }
        try {
            if($to = self::parseDateFromRequest($request, 'to')) {
                $responseData['to'] = $to->format(DateTime::ATOM);
            }
        }
        catch (\Exception $e) {
            $app->abort(400, 'Invalid "to" parameter');
        }

        if ($from && $to && $from->diff($to)->invert) {
            $app->abort(400, 'Invalid "from" and "to" parameters');
        }

        // Check against Last-Modified header.
        $lastQuery = $app['db']->createQueryBuilder()
            ->select('e.when', 'e.estimate')
            ->from('estimates', 'e')
            ->where('e.version = :version')
            ->setParameter('version', $app['db']->convertToDatabaseValue($versionString, 'string'), \PDO::PARAM_STR)
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
        $cacheMaxAge = 3600;
        if ($lastResultRow = $lastResults->fetch(\PDO::FETCH_ASSOC)) {
            $lastDate = new DateTime($lastResultRow['when']);
            $responseData['modified'] = $lastDate->format(DateTime::ATOM);

            $nowDate = new DateTime();
            if ($to && $to < $nowDate){
                // If the request limits to data in the past, we can set a very long expiry since the results will never change.
                $cacheMaxAge = 31536000;
            }
            else {
                // Calculate cache max age based on the time to the next estimate.
                $nextEstimateDate = clone $lastDate;
                $nextEstimateDate->add(new DateInterval('PT' . $app['config']['estimate.interval'] . 'S'));
                $nextEstimateInterval = $nextEstimateDate->getTimestamp() - $nowDate->getTimestamp();
                $cacheMaxAge = max(1800, $nextEstimateInterval);
            }

            $response = new Response();
            $response->setLastModified($lastDate);
            $response->setPublic();

            if ($response->isNotModified($request)) {
                // Return 304 Not Modified response.
                $response->setMaxAge($cacheMaxAge);
                return $response;
            }
        }

        $queryBuilder = $app['db']->createQueryBuilder()
            ->select('e.when', 'e.estimate', 'e.data')
            ->from('estimates', 'e')
            ->where('e.version = :version')
            ->setParameter('version', $app['db']->convertToDatabaseValue($versionString, 'string'), \PDO::PARAM_STR)
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
        while ($resultRow = $results->fetch(\PDO::FETCH_OBJ)) {
            /** @var \DateTime $whenDateTime */
            $whenDateTime = $app['db']->convertToPhpValue($resultRow->when, 'datetime');
            $dataRow = array(
                'when' => $whenDateTime->format(DateTime::ATOM),
                'estimate' => $resultRow->estimate,
            );
            /** @var \DrupalReleaseDate\EstimateDistribution $estimateDistribution */
            $estimateDistribution = unserialize($resultRow->data);
            if (!empty($resultRow->estimate) && !empty($estimateDistribution)) {
                $estimateDateTime = new DateTime($resultRow->estimate);
                $estimateDuration = $estimateDateTime->getTimestamp() - $whenDateTime->getTimestamp();
                $stdDev = $estimateDistribution->getGeometricStandardDeviation();

                $lowerDateInterval = DateInterval::createFromDateString(floor(exp(log($estimateDuration) - log($stdDev))) . ' seconds');
                $lowerDateTime = clone $whenDateTime;
                $lowerDateTime->add($lowerDateInterval);
                $upperDateInterval = DateInterval::createFromDateString(floor(exp(log($estimateDuration) + log($stdDev))) . ' seconds');
                $upperDateTime = clone $whenDateTime;
                $upperDateTime->add($upperDateInterval);

                $dataRow['geometricStandardDeviationBounds'] = array(
                    'lower' => $lowerDateTime->format(DateTime::ATOM),
                    'upper' => $upperDateTime->format(DateTime::ATOM),
                );

            }
            $responseData['data'][] = $dataRow;
        }

        if (isset($limit)) {
            $responseData['data'] = array_reverse($responseData['data']);
        }

        $response = $app->json($responseData);

        if ($lastDate) {
            $response->setLastModified($lastDate);
        }

        $response->setMaxAge($cacheMaxAge);

        return $response;
    }

    public function estimate(Application $app, Request $request)
    {
        $responseData = array();

        try {
            $versionString = self::parseVersionFromRequest($request);
        }
        catch (\Exception $e) {
            $app->abort(400, 'Invalid "version" parameter');
        }
        $responseData['version'] = $versionString;


        try {
            $date = self::parseDateFromRequest($request, 'date');
        }
        catch (\Exception $e) {
            $app->abort(400, 'Invalid "date" parameter');
        }

        $query = $app['db']->createQueryBuilder()
            ->select('e.*')
            ->from('estimates', 'e')
            ->where('e.version = :version')
            ->setParameter('version', $app['db']->convertToDatabaseValue($versionString, 'string'), \PDO::PARAM_STR)
            ->andWhere('completed IS NOT NULL')
            ->orderBy($app['db']->quoteIdentifier('when'), 'DESC')
            ->setMaxResults(1);

        if ($date) {
            $query
                ->andWhere('e.when = :when')
                ->setParameter('when', $app['db']->convertToDatabaseValue($date, 'datetime'), \PDO::PARAM_STR);
        }
        $results = $query->execute();

        if ($row = $results->fetch(\PDO::FETCH_OBJ)) {

            $estimateDate = new DateTime($row->when);
            $responseData['modified'] = $responseData['date'] = $estimateDate->format(DateTime::ATOM);


            $nowDate = new DateTime();
            $cacheMaxAge = 3600;
            if ($date){
                // If the request limits to data in the past, we can set a very long expiry since the results will never change.
                $cacheMaxAge = 31536000;
            }
            else {
                // Calculate cache max age based on the time to the next estimate.
                $nextEstimateDate = clone $estimateDate;
                $nextEstimateDate->add(new DateInterval('PT' . $app['config']['estimate.interval'] . 'S'));
                $nextEstimateInterval = $nextEstimateDate->getTimestamp() - $nowDate->getTimestamp();
                $cacheMaxAge = max(1800, $nextEstimateInterval);
            }

            $response = new Response();
            $response->setLastModified($estimateDate);
            $response->setPublic();

            if ($response->isNotModified($request)) {
                // Return 304 Not Modified response.
                $response->setMaxAge($cacheMaxAge);
                return $response;
            }

            $responseData['data']['when'] = $app['db']->convertToPhpValue($row->when, 'datetime')->format(DateTime::ATOM);
            $responseData['data']['estimate'] = $row->estimate;

            if (!empty($row->data)) {
                $distribution = array();
                $estimateDistribution = unserialize($row->data);

                foreach ($estimateDistribution as $key => $count) {
                    $dataDate = clone $estimateDate;
                    $dataDate->add(DateInterval::createFromDateString($key . ' seconds'));
                    $distribution[] = array(
                        'duration' => $key,
                        'when' => $dataDate->format('Y-m-d'),
                        'count' => $count,
                    );
                }
            } else {
                $distribution = null;
            }
            $responseData['data']['distribution'] = $distribution;

            $response = $app->json($responseData);

            if ($estimateDate) {
                $response->setLastModified($estimateDate);
            }

            $response->setMaxAge($cacheMaxAge);

            return $response;
        }
        else {
            // A specific date was requested, but no result was available.
            $app->abort(404, 'No data for requested parameters');
        }

        // TODO return failure response.
    }
}
