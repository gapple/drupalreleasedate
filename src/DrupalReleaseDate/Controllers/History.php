<?php
namespace DrupalReleaseDate\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class History
{

    public function info(Application $app, Request $request)
    {
        $fetcher = new \DrupalReleaseDate\DrupalIssueInfo();

        $issues = $app['db']->query("SELECT * FROM history WHERE severity =''");

        while ($issue = $issues->fetchObject()) {
            sleep(5);
            try {
                $info = $fetcher->getInfo($issue->nid);
            }
            catch(\Guzzle\Http\Exception\BadResponseException $e) {
                continue;
            }

            $app['db']->update('history',
                array(
                    'severity' => $info['Priority'],
                    'type' => $info['Category'],
                ),
                array('nid' => $issue->nid)
            );
        }

        return '';
    }

    public function count(Application $app, Request $request)
    {
        $startDate = \DateTime::createFromFormat('Y-m-d H:i:s', '2003-09-20 00:00:00');
        $endDate = \DateTime::createFromFormat('Y-m-d H:i:s', '2013-05-28 00:00:00');
        $dateStep = \DateInterval::createFromDateString('1 day');

        $countStatement = $app['db']->prepare("SELECT `type`, `severity`, count(*) as `count` from `history` WHERE `created` <= :date AND (`fixed` IS NULL OR `fixed` > :date) AND `severity` IN ('critical', 'major') AND `type` IN ('bug', 'task') GROUP BY `severity`, `type`");

        $insertStatment = $app['db']->prepare("INSERT INTO `samples` SET `version` = 8, `when` = :date, `critical_bugs` = :critical_bugs, `critical_tasks` = :critical_tasks, `major_bugs` = :major_bugs, `major_tasks` = :major_tasks, `notes` = 'history'");

        $currentDate = $startDate;
        while (!$currentDate->diff($endDate)->invert) {

            $params = array(
                'date' => $currentDate->format('Y-m-d'),
                'critical_bugs' => 0,
                'critical_tasks' => 0,
                'major_bugs' => 0,
                'major_tasks' => 0,
            );

            if ($countStatement->execute(array('date' => $currentDate->format('Y-m-d')))){
                while($row = $countStatement->fetch()) {
                    $countKey = $row['severity'] . '_' . $row['type'] . 's';
                    $params[$countKey] = $row['count'];
                }

                $insertStatment->execute($params);
            }

            $currentDate->add($dateStep);
        }

        return 'done!';
    }
}
