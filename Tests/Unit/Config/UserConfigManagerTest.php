<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\ConfigBundle\Config\UserConfigManager;
use Oro\Bundle\ConfigBundle\Config\ConfigDefinitionImmutableBag;

class UserConfigManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserConfigManager
     */
    protected $object;

    /**
     * @var ObjectRepository
     */
    protected $repository;

    /**
     * @var SecurityContextInterface
     */
    protected $security;

    /**
     * @var ObjectManager
     */
    protected $om;

    /** @var EventDispatcher|\PHPUnit_Framework_MockObject_MockObject */
    protected $ed;

    /**
     * @var array
     */
    protected $loadedSettings = array(
        'oro_user' => array(
            'greeting' => array(
                'value' => true,
                'type'  => 'boolean',
            ),
            'level'    => array(
                'value' => 20,
                'type'  => 'scalar',
            )
        ),
        'oro_test' => array(
            'anysetting' => array(
                'value' => 'anyvalue',
                'type'  => 'scalar',
            ),
        ),
    );

    /**
     * @var array
     */
    protected $settings = array(
        'oro_user' => array(
            'level'    => array(
                'value' => 20,
                'type'  => 'scalar',
            )
        )
    );

    protected function setUp()
    {
        $this->om = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->ed = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcher');
        $this->object = new UserConfigManager($this->ed, $this->om, new ConfigDefinitionImmutableBag($this->settings));

        $this->security   = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $this->group1     = $this->getMock('Oro\Bundle\UserBundle\Entity\Group');
        $this->group2     = $this->getMock('Oro\Bundle\UserBundle\Entity\Group');

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $user  = new User();

        $this->security
            ->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        $this->group1
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(2));

        $this->group2
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(3));

        $token
            ->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($user));

        $user
            ->setId(1)
            ->addGroup($this->group1)
            ->addGroup($this->group2);

        $this->object = new UserConfigManager($this->ed, $this->om, new ConfigDefinitionImmutableBag($this->settings));
    }

    public function testSecurity()
    {
        $object = $this->getMock(
            'Oro\Bundle\ConfigBundle\Config\UserConfigManager',
            array('loadStoredSettings'),
            array($this->ed, $this->om, new ConfigDefinitionImmutableBag($this->settings))
        );

        $object->expects($this->exactly(3))
            ->method('loadStoredSettings');

        $object->setSecurity($this->security);

        $this->assertEquals('user', $object->getScopedEntityName());
        $this->assertEquals(0, $object->getScopeId());
    }

    /**
     * Test get loaded settings
     */
    public function testGetLoaded()
    {
        $loadedSettings = $this->loadedSettings;

        $repository = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Entity\Repository\ConfigRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->at(0))
            ->method('loadSettings')
            ->with('user', 0)
            ->will($this->returnValue($loadedSettings));
        $repository->expects($this->at(1))
            ->method('loadSettings')
            ->with('app', 0)
            ->will($this->returnValue($loadedSettings));

        $this->om
            ->expects($this->exactly(2))
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $this->object->get('oro_user.level');
    }
}
