<?php

/**
 * Testing the controller functionality.
 *
 * @category  Testing
 * @package   XH
 * @author    The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @copyright 2014-2017 The CMSimple_XH developers <http://cmsimple-xh.org/?The_Team>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://cmsimple-xh.org/
 */

namespace XH;

use PHPUnit_Extensions_MockFunction;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStream;

/**
 * Testing the handling of file backup requests.
 *
 * @category Testing
 * @package  XH
 * @author   The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://cmsimple-xh.org/
 * @since    1.6.3
 */
class ControllerFileBackupTest extends TestCase
{
    /**
     * The test subject.
     *
     * @var Controller
     */
    protected $subject;

    /**
     * Sets up the test fixture.
     *
     * @return void
     *
     * @global string            The name of a special file to be handled.
     * @global CSRFProtection The CRSF protector.
     */
    public function setUp()
    {
        global $file, $_XH_csrfProtection;

        $_POST['xh_suffix'] = 'extra';
        $file = 'content';
        $_XH_csrfProtection = $this->getMockBuilder('XH\CSRFProtection')
            ->disableOriginalConstructor()->getMock();
        $this->subject = new Controller();
        $this->extraBackupMock = new PHPUnit_Extensions_MockFunction('XH_extraBackup', $this->subject);
    }

    /**
     * Tests that the CSRF token is checked.
     *
     * @return void
     *
     * @global CSRFProtection The CRSF protector.
     */
    public function testChecksCsrfToken()
    {
        global $_XH_csrfProtection;

        $_XH_csrfProtection->expects($this->once())->method('check');
        $this->subject->handleFileBackup();
    }

    /**
     * Tests that XH_extraBackup() is called.
     *
     * @return void
     */
    public function testCallsExtraBackup()
    {
        $this->extraBackupMock->expects($this->once());
        $this->subject->handleFileBackup();
    }
}