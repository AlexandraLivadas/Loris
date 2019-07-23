<?php
/**
 * Unit tests for User class
 *
 * PHP Version 5
 *
 * @category Tests
 * @package  Main
 * @author   Alexandra Livadas <alexandra.livadas@mcin.ca>
 * @license  http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 * @link     https://www.github.com/aces/Loris/
 */
require_once __DIR__ . '/../../vendor/autoload.php';
use PHPUnit\Framework\TestCase;
/**
 * Unit test for User class
 *
 * @category Tests
 * @package  Main
 * @author   Alexandra Livadas <alexandra.livadas@mcin.ca>
 * @license  http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 * @link     https://www.github.com/aces/Loris/
 */
class UserTest extends TestCase
{
    /**
     * The username for the private user object
     *
     * @var string
     */
    private $_username;
    /**
     * Stores user information
     *
     * @var array
     */
    private $_userInfo
        = array('ID'                     => 1,
                'UserID'                 => '968775',
                'Password'               => 'pass123',
                'Real_name'              => 'John Doe',
                'First_name'             => 'John',
                'Last_name'              => 'Doe',
                'Degree'                 => 'Eng.D',
                'Position_title'         => 'Doctor',
                'Institution'            => 'MCIN',
                'Department'             => 'Neuroscience',
                'Address'                => '123 Main St',
                'City'                   => 'Montreal',
                'State'                  => 'Quebec',
                'Zip_code'               => '123',
                'Country'                => 'Canada',
                'Phone'                  => '123-456-7890',
                'Fax'                    => null,
                'Email'                  => 'john.doe@mcgill.ca',
                'Privilege'              => 1,
                'PSCPI'                  => 'Y',
                'DBAccess'               => '123',
                'Active'                 => 'Y',
                'Password_hash'          => null,
                'Password_expiry'        => '2020-07-16',
                'Pending_approval'       => 'Y',
                'Doc_Repo_Notifications' => 'Y',
                'language_preference'    => 2,
                'active_from'            => '2017-07-16',
                'active_to'              => '2020-07-16'
          );
    /**
     * The userInfo table that should result from calling the factory function
     * TODO There should be a way to write this that takes up less space!
     *
     * @var array
     */
    private $_userInfoComplete
        = array('ID'                     => '1',
                'UserID'                 => '968775',
                'Password'               => 'pass123',
                'Real_name'              => 'John Doe',
                'First_name'             => 'John',
                'Last_name'              => 'Doe',
                'Degree'                 => 'Eng.D',
                'Position_title'         => 'Doctor',
                'Institution'            => 'MCIN',
                'Department'             => 'Neuroscience',
                'Address'                => '123 Main St',
                'City'                   => 'Montreal',
                'State'                  => 'Quebec',
                'Zip_code'               => '123',
                'Country'                => 'Canada',
                'Phone'                  => '123-456-7890',
                'Fax'                    => null,
                'Email'                  => 'john.doe@mcgill.ca',
                'Privilege'              => '1',
                'PSCPI'                  => 'Y',
                'DBAccess'               => '123',
                'Active'                 => 'Y',
                'Password_hash'          => null,
                'Password_expiry'        => '2020-07-16',
                'Pending_approval'       => 'Y',
                'Doc_Repo_Notifications' => 'Y',
                'language_preference'    => 2,
                'active_from'            => '2017-07-16',
                'active_to'              => '2020-07-16',
                'Sites'                  => 'psc_test;psc_test2',
                'examiner'               => array('pending' => 'N',
                                                  '1'       => array('Y',
                                                                     1
                                                               ),
                                                  '4'       => array('Y',
                                                                     1
                                                               )
                                            ),
                'CenterIDs'              => array('1', '4')
          );
    /**
     * Psc table information
     *
     * @var array
     */
    private $_pscInfo = array(0 => array('CenterID' => '1',
                                         'Name' => 'psc_test'),
                              1 => array('CenterID' => '4',
                                         'Name' => 'psc_test2')
                        );
    /**
     * Examiners table information
     *
     * @var array
     */
    private $_examinerInfo = array(0 => array('full_name' => 'John Doe',
                                              'examinerID' => 1,
                                              'radiologist' => 1)
                             );
    /**
     * User_psc_rel table information
     *
     * @var array
     */
    private $_uprInfo = array(0 => array('UserID' => '1',
                                         'CenterID' => '1'),
                              1 => array('UserID' => '1',
                                         'CenterID' => '4')
                        );
    /**
     * Examiners_psc_rel table information
     *
     * @var array
     */
    private $_eprInfo = array(0 => array('centerID' => '1',
                                         'examinerID' => 1,
                                         'active' => 'Y',
                                         'pending_approval' => 'N'),
                              1 => array('centerID' => '4',
                                         'examinerID' => 1,
                                         'active' => 'Y',
                                         'pending_approval' => 'N')
                        );
    /**
     * User object used for testing
     *
     * @var User object
     */
    private $_user;
    /**
     * NDB_Factory used in tests.
     * Test doubles are injected to the factory object.
     *
     * @var NDB_Factory
     */
    private $_factory;
    /**
     * Test double for NDB_Config object
     *
     * @var \NDB_Config | PHPUnit_Framework_MockObject_MockObject
     */
    private $_configMock;
    /**
     * Test double for Database object
     *
     * @var \Database | PHPUnit_Framework_MockObject_MockObject
     */
    private $_dbMock;
    /**
     * Maps config names to values
     * Used to set behavior of NDB_Config test double
     *
     * @var array config name => value
     */
    private $_configMap = array();
    /**
     * This method is called before each test is executed.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->_factory = NDB_Factory::singleton();
        $this->_factory->reset();
        $this->_factory->setTesting(false);
        $this->_configMock = $this->_factory->Config(CONFIG_XML);
        $database     = $this->_configMock->getSetting('database');
        $this->_dbMock     = Database::singleton(
            $database['database'],
            $database['username'],
            $database['password'],
            $database['host'],
            1
        );

        $this->_username = "968775";
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->_factory->reset();
    }

    /**
     * Test factory() method retrieves all _candidate and related info
     *
     * @return void
     * @covers User::singleton
     * @covers User::factory
     * @covers User::getData
     */
    public function testFactoryRetrievesUserInfo()
    {
        $this->_setUpTestDoublesForFactoryUser();
        $this->_user = \User::factory($this->_username);
        //validate _user Info
        $this->assertEquals($this->_userInfoComplete, $this->_user->getData());
    }

    /**
     * Test that getFullname returns the correct full name of the user
     *
     * @return void
     * @covers User::getFullname
     */
    public function testGetFullname()
    {
        $this->_user = \User::factory($this->_username);
        $this->assertEquals(
            $this->_userInfoComplete['Real_name'],        
            $this->_user->getFullname()
        );
    }

    /**
     * Test that getId returns the correct ID of the user
     *
     * @return void
     * @covers User::getId
     */
    public function testGetId()
    {
        $this->_user = \User::factory($this->_username);
        $this->assertEquals(
            $this->_userInfoComplete['ID'],
            $this->_user->getId()
        );
    }

    /**
     * Test that getUsername returns the correct UserID of the user
     *
     * @return void
     * @covers User::getUsername
     */
    public function testGetUsername()
    {
        $this->_user = \User::factory($this->_username);
        $this->assertEquals(
            $this->_userInfoComplete['UserID'],
            $this->_user->getUsername()
        );
    }

    /**
     * Test that getSiteNames returns the site names in the correct format
     * TODO Add more sites
     *
     * @return void
     * @covers User::getSiteNames
     */
    public function testSiteNames()
    {
        $this->_user = \User::factory($this->_username);
        $this->assertEquals(
            array('psc_test', 'psc_test2'),
            $this->_user->getSiteNames()
        );
    }

    /**
     * Test that getCenterIDs returns the correct array of center IDs of the user
     *
     * @return void
     * @covers User::getCenterIDs
     */
    public function testGetCenterIDs()
    {
        $this->_user = \User::factory($this->_username);
        $this->assertEquals(
            $this->_userInfoComplete['CenterIDs'],
            $this->_user->getCenterIDs()
        );
    }

    /**
     * Test that getLanguagePreference returns the correct integer from the user
     * TODO getLanguagePreference is returning a string rather than an int. 
     *
     * @return void
     * @covers User::getLanguagePreference
     */
    public function testGetLanguagePreference()
    {
        $this->markTestIncomplete("This test is incomplete");
        $this->_user = \User::factory($this->_username);
        $this->assertEquals(
            $this->_userInfoComplete['language_preference'],
            $this->_user->getLanguagePreference()
        );
    }

    /**
     * Test that getExaminerSites returns the correct array of sites for the user
     * and that the array is formatted correctly
     *
     * @return void
     * @covers User::getExaminerSites
     */
    public function testGetExaminerSites()
    {
        $this->_user = \User::factory($this->_username);
        $result = array('1' => array('Y', 1),
                        '4' => array('Y', 1));
        $this->assertEquals(
            $result,
            $this->_user->getExaminerSites()
        );
    }

    /**
     * Test that getStudySites returns the correct array of sites 
     * that are classified as study sites for the user 
     * and that the array is formatted correctly
     *
     * @return void
     * @covers User::getStudySites
     */
    public function testGetStudySites()
    {
        $this->_user = \User::factory($this->_username);
        $result = array('1' => 'psc_test',
                        '4' => 'psc_test2');
        $this->assertEquals(
            $result,
            $this->_user->getStudySites()
        );
    }

    /**
     * Test that hasStudySite returns true when the user has study sites
     *
     * @return void
     * @covers User::hasStudySite
     */
    public function testHasStudySites()
    {
        $this->_user = \User::factory($this->_username);
        $this->assertTrue($this->_user->hasStudySite());
    }
    
    /**
     * Test that isEmailValid returns true when the email is valid
     *
     * @return void
     * @covers User::isEmailValid
     */
    public function testIsEmailValid()
    {
        $this->_user = \User::factory($this->_username);
        $this->assertTrue($this->_user->isEmailValid());
    }

    /** 
     * Test that isEmailValid returns false when the email has an invalid format
     *
     * @return void
     * @covers User::isEmailValid
     */
    public function testIsEmailValidWhenInvalid()
    {
        $this->_dbMock->run("DROP TEMPORARY TABLE users");
        $invalidEmailInfo = $this->_userInfo;
        $invalidEmailInfo['Email'] = 'invalidemail.ca';
        $this->_dbMock->setFakeTableData(
            "users", 
            array(0=> $invalidEmailInfo)
        );
        $this->_user = \User::factory($this->_username);
        $this->assertFalse($this->_user->isEmailValid());
    }
 
    /**
     * Test that hasCenter returns true when the user has this center ID
     *
     * @return void
     * @covers User::hasCenter
     */
    public function testHasCenterWhenTrue()
    {
        $this->_user = \User::factory($this->_username);
        $this->assertTrue($this->_user->hasCenter(4));
    }

    /**
     * Test that hasCenter returns false when the user does not have this center ID
     *
     * @return void
     * @covers User::hasCenter
     */
    public function testHasCenterWhenFalse()
    {
        $this->_user = \User::factory($this->_username);
        $this->assertFalse($this->_user->hasCenter(5));
    }

    /**
     * Test that hasLoggedIn returns false when the user 
     * has not logged in succesfully
     * 
     * @return void
     * @covers User::hasLoggedIn
     */
    public function testHasLoggedInWhenFalse()
    {
        $this->_user = \User::factory($this->_username);
        $this->_dbMock->setFakeTableData(
            "user_login_history",
            array(0 => array('userID'  => '968775',
                             'Success' => 'N'))
        );
        $this->assertFalse($this->_user->hasLoggedIn());
    }

    /**
     * Test that hasLoggedIn returns true when the user
     * has logged in succesfully
     * TODO It is returning false rather than true. This is probably an issue
     *      with the query or with the fake table data being inputted!
     *
     * @return void
     * @covers User::hasLoggedIn
     */
    public function testHasLoggedInWhenTrue()
    {
        $this->markTestIncomplete(
            "This test is incomplete! Check out the TODO cooment"
        );
        $this->_user = \User::factory($this->_username);
        $this->_dbMock->setFakeTableData(
            "user_login_history",
            array(0 => array('userID' => '968775',
                             'Success' => 'Y'))
        ); 
        $this->assertFalse($this->_user->hasLoggedIn());
    }

    /**
     * Test that isUserDCC returns true when the user belongs to DCC
     * TODO The function isUserDCC needs to be changed because it is using the
     *      deprecated 'CenterID' rather than the array 'CenterIDs'
     *
     * @return void
     * @covers User::isUserDCC
     */
    public function testIsUserDCC()
    {
        $this->markTestIncomplete(
            "This test is incomplete! Check out the TODO comment!"
        );
        $this->_user = \User::factory($this->_username);
        $this->assertTrue($this->_user->isUserDCC());
    }

    /**
     * Set up the fake tables in the database to set up a new user object
     *
     * @return void
     */
    private function _setUpTestDoublesForFactoryUser()
    {
        $this->_dbMock->setFakeTableData(
            "users",
            array(0 => $this->_userInfo)
        );
        $this->_dbMock->setFakeTableData(
            "user_psc_rel",
            $this->_uprInfo
        );
        $this->_dbMock->setFakeTableData(
            "psc",
            $this->_pscInfo
        );
        $this->_dbMock->setFakeTableData(
            "examiners",
            $this->_examinerInfo
        );
        $this->_dbMock->setFakeTableData(
            "examiners_psc_rel",
            $this->_eprInfo
        );
    }
}

