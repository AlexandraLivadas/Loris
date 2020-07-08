<?php
/**
 * Unit test for NDB_BVL_Instrument class
 *
 * PHP Version 7
 *
 * @category Tests
 * @package  Main
 * @author   Dave MacFarlane <david.macfarlane2@mcgill.ca>
 * @license  http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 * @link     https://www.github.com/aces/Loris/
 */
namespace Loris\Tests;
set_include_path(get_include_path().":" .  __DIR__  . "/../../php/libraries:");
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../php/libraries/NDB_BVL_Instrument.class.inc';
require_once 'Smarty_hook.class.inc';
require_once 'NDB_Config.class.inc';
/**
 * Unit test for NDB_BVL_Instrument class
 *
 * PHP Version 7
 *
 * @category Tests
 * @package  Main
 * @author   Dave MacFarlane <david.macfarlane2@mcgill.ca>
 * @license  http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 * @link     https://www.github.com/aces/Loris/
 */
class NDB_BVL_Instrument_Test extends TestCase
{
    private $_instrument;
    /**
     * Set up sets a fake $_SESSION object that we can use for
     * assertions
     *
     * @return void
     */
    function setUp()
    {
        global $_SESSION;
        if (!defined("UNIT_TESTING")) {
            define("UNIT_TESTING", true);
        }
        date_default_timezone_set("UTC");
        $this->session = $this->getMockBuilder(\stdClass::class)
            ->setMethods(
                array('getProperty', 'setProperty', 'getUsername', 'isLoggedIn')
            )
            ->getMock();
        $this->mockSinglePointLogin = $this->getMockBuilder('SinglePointLogin')
            ->getMock();
        $this->session->method("getProperty")
            ->willReturn($this->mockSinglePointLogin);

        $_SESSION = array(
            'State' => $this->session
        );

        $factory = \NDB_Factory::singleton();
        $factory->setTesting(true);

        $mockdb = $this->getMockBuilder("\Database")->getMock();
        $mockconfig = $this->getMockBuilder("\NDB_Config")->getMock();

        \NDB_Factory::$db = $mockdb;
        \NDB_Factory::$testdb = $mockdb;
        \NDB_Factory::$config = $mockconfig;

        $this->quickForm = new \LorisForm();

        $this->_instrument = $this->getMockBuilder(\NDB_BVL_Instrument::class)
            ->disableOriginalConstructor()
            ->setMethods(array("getFullName", "getSubtestList"))->getMock();
        $this->_instrument->method('getFullName')->willReturn("Test Instrument");
        $this->_instrument->method('getSubtestList')->willReturn(array());
        $this->_instrument->form = $this->quickForm;
        $this->_instrument->testName = "Test";
    }

    /**
     * Helper function to use for creating stubs that stub out everything except
     * the method being tested
     *
     * @param $methods The method being tested
     *
     * @return array
     */
    function _getAllMethodsExcept($methods)
    {
        $AllMethods = get_class_methods('NDB_BVL_Instrument');

        return array_diff($AllMethods, $methods);
    }

    /**
     * Test that the toJSON method returns the correct metadata
     *
     * @covers NDB_BVL_Instrument::toJSON
     * @return void
     */
    function testMetaData()
    {
        $json = $this->_instrument->toJSON();
        $outArray = json_decode($json, true);
        $ExpectedMeta = [
            'InstrumentVersion' => "1l",
            'InstrumentFormatVersion' => "v0.0.1a-dev",
            "ShortName" => "Test",
            "LongName" => "Test Instrument",
            "IncludeMetaDataFields" => "true",
        ];
        $this->assertEquals($ExpectedMeta, $outArray['Meta']);
    }

    /**
     * Test that addSelect and addElement add the correct information
     * and that toJSON returns this data
     *
     * @covers NDB_BVL_Instrument::toJSON
     * @return void
     */
    function testSelectElement()
    {
        $value = array('value' => "Option");
        $not_answered = array('value' => 'Option', 'not_answered' => 'Not Answered');
        $this->_instrument->addSelect("FieldName", "Field Description", $value);
        $this->_instrument->addSelect(
            "FieldName2", "Field Description 2", $not_answered
        );
        $this->_instrument->form->addElement(
            'select',
            "multiselect1",
            "Test Question",
            $value, array("multiple" => 'multiple')
        );
        $this->_instrument->form->addElement(
            'select',
            "multiselect2",
            "Test Question",
            $not_answered, array('multiple' => "multiple")
        );
        $json = $this->_instrument->toJSON();
        $outArray = json_decode($json, true);
        $selectElement = $outArray['Elements'][0];
        $selectElement2 = $outArray['Elements'][1];

        $multiselectElement = $outArray['Elements'][2];
        $multiselectElement2 = $outArray['Elements'][3];

        $this->assertEquals(
            $selectElement,
            [
                'Type' => "select",
                "Name" => "FieldName",
                "Description" => "Field Description",
                "Options" => [
                    "Values" => [
                        "value" => "Option"
                    ],
                    "RequireResponse" => false
                ],
            ]
        );

        $this->assertEquals(
            $selectElement2,
            [
                'Type' => "select",
                "Name" => "FieldName2",
                "Description" => "Field Description 2",
                "Options" => [
                    "Values" => [
                        "value" => "Option"
                    ],
                    "RequireResponse" => true
                ],
            ]
        );

        $this->assertEquals(
            $multiselectElement,
            [
                'Type' => "select",
                "Name" => "multiselect1",
                "Description" => "Test Question",
                "Options" => [
                    "Values" => [
                        "value" => "Option"
                    ],
                    "RequireResponse" => false,
                    "AllowMultiple" => true,
                ],
            ]
        );

        $this->assertEquals(
            $multiselectElement2,
            [
                'Type' => "select",
                "Name" => "multiselect2",
                "Description" => "Test Question",
                "Options" => [
                    "Values" => [
                        "value" => "Option"
                    ],
                    "RequireResponse" => true,
                    "AllowMultiple" => true,
                ],
            ]
        );
    }


    /**
     * Test that addTextElement and addTextAreaElement adds the correct data to
     * the instrument
     *
     * @covers NDB_BVL_Instrument::addTextElement
     * @covers NDB_BVL_Instrument::addTextAreaElement
     * @covers NDB_BVL_Instrument::toJSON
     * @return void
     */
    function testTextElement()
    {
        $this->_instrument->addTextElement(
            "FieldName", "Field Description for Text", array("value" => "Option")
        );
        $this->_instrument->addTextAreaElement(
            "FieldName2", "Field Description2 for Text", array("value" => "Option")
        );
        $json = $this->_instrument->toJSON();
        $outArray = json_decode($json, true);
        $textElement = $outArray['Elements'][0];
        $textareaElement = $outArray['Elements'][1];

        $this->assertEquals(
            $textElement,
            [
                'Type'        => "text",
                "Name"        => "FieldName",
                "Description" => "Field Description for Text",
                "Options"     => [
                    "Type"            => "small",
                    "RequireResponse" => true
                ]
            ]
        );

        $this->assertEquals(
            $textareaElement,
            [
                'Type'        => "text",
                "Name"        => "FieldName2",
                "Description" => "Field Description2 for Text",
                "Options"     => [
                    "Type"            => "large",
                    "RequireResponse" => true
                ]
            ]
        );

        $textRules = $this->_instrument->XINRules['FieldName'];
        $textAreaRules = $this->_instrument->XINRules['FieldName2'];
        $this->assertEquals(
            $textRules,
            [
                'message' => 'This field is required.',
                'group' => 'FieldName_group',
                'rules' => ['FieldName_status{@}=={@}', 'Option']
            ]
        );
        $this->assertEquals(
            $textAreaRules,
            [
                'message' => 'This field is required.',
                'group' => 'FieldName2_group',
                'rules' => ['FieldName2_status{@}=={@}', 'Option']
            ]
        );
    }

    /**
     * Test that addTextAreaElementRD adds a group element to the instrument
     *
     * @covers NDB_BVL_Instrument::addTextAreaElementRD
     * @return void
     */
    function testAddTextAreaElement()
    {
        $this->_instrument->addTextAreaElementRD(
            "FieldName1", "Field Description1", array("value" => "Option")
        );
        $json = $this->_instrument->toJSON();
        $outArray = json_decode($json, true);
        $this->assertEquals(
            $outArray['Elements'][0],
            ['Type' => "Group", 'Error' => "Unimplemented"]
        );
        $textRules = $this->_instrument->XINRules['FieldName1'];
        $this->assertEquals(
            $textRules,
            [
                'message' => 'You must specify or select from the drop-down',
                'group' => 'FieldName1_group',
                'rules' => ['FieldName1_status{@}=={@}', 'Option']
            ]
        );
    }

    /**
     * Test that addBasicDate (from NDB_Page) and addDateElement
     * adds the correct date to the instrument object
     *
     * @covers NDB_Page::addBasicDate
     * @covers NDB_Page::addDateElement
     * @covers NDB_Page::toJSON
     * @return void
     */
    function testDateElement()
    {
        $this->_instrument->addBasicDate(
            "FieldName",
            "Field Description",
            [
                'format'  => 'YMd',
                "minYear" => "1990",
                "maxYear" => "2000",
                "addEmptyOption" => false,
            ]
        );
        $this->_instrument->addBasicDate(
            "FieldName2",
            "Field Description",
            [
                'format'  => 'YMd',
                "minYear" => "1990",
                "maxYear" => "2000",
                "addEmptyOption" => true,
            ]
        );

        $this->_instrument->addDateElement(
            "FieldName3",
            "Field Description",
            [
                'format'  => 'YMd',
                "minYear" => "1990",
                "maxYear" => "2000",
                "addEmptyOption" => false,
            ]
        );
        $this->_instrument->addDateElement(
            "FieldName4",
            "Field Description",
            [
                'format'  => 'YMd',
                "minYear" => "1990",
                "maxYear" => "2000",
                "addEmptyOption" => true,
            ]
        );
        $json = $this->_instrument->toJSON();
        $outArray = json_decode($json, true);
        $dateElement = $outArray['Elements'][0];
        $dateElement2 = $outArray['Elements'][1];
        $dateElement3 = $outArray['Elements'][2];
        $dateElement4 = $outArray['Elements'][3];

        // They were added with addBasicDate, so response is
        // not required.
        $expectedResult = [
                'Type'        => "date",
                "Name"        => "FieldName",
                "Description" => "Field Description",
                "Options"     => [
                    "MinDate" => "1990-01-01",
                    "MaxDate" => "2000-12-31",
                    "RequireResponse" => false
                ]
            ];

        $this->assertEquals($dateElement, $expectedResult);

        $expectedResult['Name'] = 'FieldName2';
        $this->assertEquals($dateElement2, $expectedResult);

        unset($expectedResult['Options']['RequireResponse']);

        // The addDateElement wrappers add _date to the field name, the
        // addBasicDate wrappers do not.
        $expectedResult['Name'] = 'FieldName3_date';
        $this->assertEquals($dateElement3, $expectedResult);

        $expectedResult['Name'] = 'FieldName4_date';
        $this->assertEquals($dateElement4, $expectedResult);
    }

    /**
     * Test that addNumericElement adds the correct data to the instrument
     *
     * @covers NDB_BVL_Instrument::addNumericElement
     * @covers NDB_BVL_Instrument::toJSON
     * @return void
     */
    function testNumericElement()
    {
        $this->_instrument->addNumericElement("TestElement", "Test Description");
        $json = $this->_instrument->toJSON();
        $outArray = json_decode($json, true);
        $numericElement = $outArray['Elements'][0];

        $this->assertEquals(
            $numericElement,
            [
                "Type" => "numeric",
                "Name" => "TestElement",
                "Description" => "Test Description",
                "Options" => [
                    "NumberType" => "decimal"
                ]
            ]
        );

    }

    /**
     * Test that addScoreColumn (from NDB_Page) adds the data to the
     * instrument object
     *
     * @covers NDB_Page::addScoreColumn
     * @covers NDB_BVL_Instrument::toJSON
     * @return void
     */
    function testScoreElement()
    {
        $this->_instrument->addScoreColumn(
            "FieldName",
            "Field Description",
            "45"
        );
        $this->_instrument->addScoreColumn(
            "FieldName2",
            null
        );
        $json = $this->_instrument->toJSON();
        $outArray = json_decode($json, true);
        $scoreElement = $outArray['Elements'][0];
        $scoreElement2 = $outArray['Elements'][1];

        $this->assertEquals(
            $scoreElement,
            [
                'Type'        => "score",
                "Name"        => "FieldName",
                "Description" => "Field Description",
            ]
        );
        $this->assertEquals(
            $scoreElement2,
            [
                'Type'        => "score",
                "Name"        => "FieldName2",
            ]
        );

    }

    /**
     * Test that when a header element is added, the toJSON method returns the
     * element in the correct format
     *
     * @covers NDB_BVL_Instrument::toJSON
     * @return void
     */
    function testHeaderElement()
    {
        // Since QuickForm arbitrarily decides to split things into "sections"
        // when there's a header, the header test adds 2 headers to ensure that
        // the JSON serialization was done according to spec, and not according
        // to QuickForm's whims.
        // The first "section" has no elements, and the second one, to make sure
        // that the serialization won't die on a 0 element "section"
        $this->_instrument->form->addElement(
            "header", null, "I am your test header"
        );
        $this->_instrument->form->addElement(
            "header", null, "I am another test header"
        );
        $this->_instrument->addScoreColumn(
            "FieldName2",
            "Field Description",
            "45"
        );
        $json = $this->_instrument->toJSON();
        $outArray = json_decode($json, true);
        $headerElement = $outArray['Elements'][0];
        $headerElement2= $outArray['Elements'][1];

        $this->assertEquals(
            $headerElement,
            [
                'Type'        => "header",
                "Description" => "I am your test header",
                "Options"     => [
                    "Level" => 1
                ]
            ]
        );
        $this->assertEquals(
            $headerElement2,
            [
                'Type'        => "header",
                "Description" => "I am another test header",
                "Options"     => [
                    "Level" => 1
                ]
            ]
        );

        $this->assertEquals(count($outArray['Elements']), 3);
    }

    /**
     * Test that addLabel (from NDB_Page) adds the label element
     * to the instrument object
     *
     * @covers NDB_Page::addLabel
     * @covers NDB_BVL_Instrument::toJSON
     * @return void
     */
    function testLabelElement()
    {
        $this->_instrument->addLabel("I am a label");
        $json = $this->_instrument->toJSON();
        $outArray = json_decode($json, true);
        $labelElement = $outArray['Elements'][0];

        $this->assertEquals(
            $labelElement,
            [
                'Type'        => "label",
                "Description" => "I am a label"
            ]
        );
        $this->assertEquals(count($outArray['Elements']), 1);
    }

    /**
     * Test that toJSON gets the subtest list and full name elements of
     * the instrument
     *
     * @covers NDB_BVL_Instrument::toJSON
     * @return void
     */
    function testPageGroup()
    {
        $this->_instrument = $this->getMockBuilder(\NDB_BVL_Instrument::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array("getFullName", "getSubtestList", '_setupForm')
            )->getMock();
        $this->_instrument->method('getFullName')->willReturn("Test Instrument");
        $this->_instrument->method('getSubtestList')->willReturn(
            array(
                array('Name' => 'Page 1', 'Description' => 'The first page'),
                array('Name' => 'Page 2', 'Description' => 'The second page'),
            )
        );

        $this->_instrument->form = $this->quickForm;
        $this->_instrument->testName = "Test";

        $json = $this->_instrument->toJSON();
        $outArray = json_decode($json, true);
        $page1 = $outArray['Elements'][0];
        $page2 = $outArray['Elements'][1];
        $this->assertEquals(
            $page1,
            [
                'Type' => 'ElementGroup',
                'GroupType' => 'Page',
                'Elements' => [],
                'Description' => 'The first page'
            ]
        );
        $this->assertEquals(
            $page2,
            [
                'Type' => 'ElementGroup',
                'GroupType' => 'Page',
                'Elements' => [],
                'Description' => 'The second page'
            ]
        );
    }

    /**
     * Test that setup correctly sets the commentID and page values of the
     * instrument and that getCommentID returns the commentID value.
     *
     * @covers NDB_BVL_Instrument::setup
     * @covers NDB_BVL_Instrument::getCommentID
     * @return void
     */
    function testSetup()
    {
        $this->_instrument->setup("commentID", "page");
        $this->assertEquals("commentID", $this->_instrument->getCommentID());
        $this->assertEquals("page", $this->_instrument->page);

    }

    /**
     * Test that calculateAgeMonths returns the correct number of months
     * for the given age array.
     *
     * @covers NDB_BVL_Instrument::calculateAgeMonths
     * @return void
     */
    function testCalculateAgeMonths()
    {
        $age = array('year' => 3, 'mon' => 4, 'day' => 23);
        $months = $this->_instrument->calculateAgeMonths($age);
        $this->assertEquals(40.8, $months);
    }

    /**
     * Test that calculateAgeDays returns the correct number of days
     * for the given age array
     *
     * @covers NDB_BVL_Instrument::calculateAgeDays
     * @return void
     */
    function testCalculateAgeDays()
    {
        $age = array('year' => 3, 'mon' => 4, 'day' => 23);
        $days = $this->_instrument->calculateAgeDays($age);
        $this->assertEquals(1238, $days);
    }

    /**
     * Test that addYesNoElement adds an element to the instrument with
     * yes/no options
     *
     * @covers NDB_BVL_Instrument::addYesNoElement
     * @return void
     */
    function testAddYesNoElement()
    {
        $this->_instrument->addYesNoElement("field1", "label1");
        $json = $this->_instrument->toJSON();
        $outArray = json_decode($json, true);
        $this->assertEquals(
            $outArray['Elements'][0],
            array('Type' => 'select',
                  'Name' => 'field1',
                  'Description' => 'label1',
                  'Options' => array('Values' => array('' => '',
                                                       'yes' => 'Yes',
                                                       'no' => 'No'),
                                     'RequireResponse' => true)
            )
        );
    }

    /**
     * Test that addYesNoElement adds an element and registers a XIN rule
     * if specified.
     *
     * @covers NDB_BVL_Instrument::addYesNoElementWithRules
     * @return void
     */
    function testAddYesNoElementWithRules()
    {
        $this->_instrument->addYesNoElement(
            "field1", "label1", ["rule1"], "Rule message"
        );
        $json = $this->_instrument->toJSON();
        $outArray = json_decode($json, true);
        $this->assertEquals(
            $outArray['Elements'][0],
            array('Type' => 'select',
                'Name' => 'field1',
                'Description' => 'label1',
                'Options' => array('Values' => array('' => '',
                    'yes' => 'Yes',
                    'no' => 'No'),
                    'RequireResponse' => true)
            )
        );
        $rules = $this->_instrument->XINRules["field1"];
        $this->assertEquals(
            $rules,
            array('message' => 'Rule message',
                  'group' => '',
                  'rules' => ['rule1']
            )
        );
    }

}
?>
