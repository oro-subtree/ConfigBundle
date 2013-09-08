<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Symfony\Component\Security\Core\SecurityContextInterface;

use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\UserBundle\Entity\User;

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
        $this->object = new UserConfigManager($this->om, $this->settings);

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

        $this->object = $this->getMock(
            'Oro\Bundle\ConfigBundle\Config\UserConfigManager',
            array('loadStoredSettings'),
            array($this->om, $this->settings)
        );
    }

    public function testSecurity()
    {
        $object      = $this->object;
        $object->expects($this->exactly(3))
            ->method('loadStoredSettings');

        $object->setSecurity($this->security);

        $this->assertEquals('user', $object->getScopedEntityName());
        $this->assertEquals(0, $object->getScopeId());
    }
}
