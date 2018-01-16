<?php
namespace DrupalReleaseDate\Tests\Controllers;

use DrupalReleaseDate\Controllers\Data;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Exporter\Exception;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @coversDefaultClass \DrupalReleaseDate\Controllers\Data
 */
class DataTest extends TestCase
{
    public static function setUpBeforeClass() {
        date_default_timezone_set('America/Vancouver');
    }

    /**
     * Test the version if no value is provided in the request.
     */
    public function testVersionParserDefault()
    {
        $request = new Request();
        $this->assertEquals('8.0', Data::parseVersionFromRequest($request));
    }

    /**
     * Test the version if only a single number (major version) is provided
     */
    public function testVersionParserShort()
    {
        $request = new Request();
        $request->query->set('version', '4');
        $this->assertEquals('4.0', Data::parseVersionFromRequest($request));

        $request = new Request();
        $request->query->set('version', '12');
        $this->assertEquals('12.0', Data::parseVersionFromRequest($request));
    }

    /**
     * Test the version if a major and minor version are provided
     */
    public function testVersionParserLong() {
        $request = new Request();
        $request->query->set('version', '9.1');
        $this->assertEquals('9.1', Data::parseVersionFromRequest($request));

        $request = new Request();
        $request->query->set('version', '12.34');
        $this->assertEquals('12.34', Data::parseVersionFromRequest($request));
    }

    /**
     * Test that an exception is raised if a patch version is provided.
     */
    public function testVersionParserInvalidPatch()
    {
        $request = new Request();
        $request->query->set('version', '1.2.3');
        $this->addToAssertionCount(1);
        try {
            Data::parseVersionFromRequest($request);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('Invalid version parameter did not throw exception.');
    }

    /**
     * Test that an exception is raised if a string is provided in the version.
     */
    public function testVersionParserInvalidString()
    {
        $request = new Request();
        $request->query->set('version', '1.B');
        $this->addToAssertionCount(1);
        try {
            Data::parseVersionFromRequest($request);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('Invalid version parameter did not throw exception.');
    }

    /**
     * Test that an exception is raised if an invalid version separator is used.
     */
    public function testVersionParserInvalidSeparator ()
    {
        $request = new Request();
        $request->query->set('version', '1-2');
        $this->addToAssertionCount(1);
        try {
            Data::parseVersionFromRequest($request);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('Invalid version parameter did not throw exception.');
    }

    /**
     * Test that a null value is returned if the requested parameter is not present.
     */
    public function testDateParserEmpty()
    {
        $request = new Request();
        $this->assertNull(Data::parseDateFromRequest($request, 'from'));
    }

    /**
     * Test that a date with no time is parsed correctly.
     */
    public function testDateParserDate()
    {
        $request = new Request();
        $request->query->set('from', '2014-06-01');

        $date = Data::parseDateFromRequest($request, 'from');
        $this->assertNotNull($date);
        $this->assertEquals('2014-06-01T00:00:00-07:00', $date->format(\DateTime::ATOM));
    }

    /**
     * Test that a date with a time value is parsed correctly.
     */
    public function testDateParserDateTime()
    {
        $request = new Request();
        $request->query->set('from', '2014-06-01 08:25:10');

        $date = Data::parseDateFromRequest($request, 'from');
        $this->assertNotNull($date);
        $this->assertEquals('2014-06-01T08:25:10-07:00', $date->format(\DateTime::ATOM));


        $request = new Request();
        $request->query->set('from', '2014-06-01T08:25:10');

        $date = Data::parseDateFromRequest($request, 'from');
        $this->assertNotNull($date);
        $this->assertEquals('2014-06-01T08:25:10-07:00', $date->format(\DateTime::ATOM));
    }

    /**
     * Test that an invalid date value raises an exception.
     */
    public function testDateParserInvalid()
    {
        $request = new Request();
        $request->query->set('from', 'foo');
        $this->addToAssertionCount(1);
        try {
            Data::parseDateFromRequest($request, 'from');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('Invalid date parameter did not throw exception.');
    }
}
