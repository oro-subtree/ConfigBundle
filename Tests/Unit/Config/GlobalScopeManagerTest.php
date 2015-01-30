<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\EventDispatcher\EventDispatcher;

use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SystemConfiguration\ProcessorDecorator;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\ConfigBundle\Form\Type\FormFieldType;
use Oro\Bundle\ConfigBundle\Form\Type\FormType;
use Oro\Bundle\ConfigBundle\Provider\SystemConfigurationFormProvider;
use Oro\Bundle\ConfigBundle\Form\Type\ParentScopeCheckbox;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;

class GlobalScopeManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GlobalScopeManager
     */
    protected $object;

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
            'level'    => array(
                'value' => 2000,
                'type'  => 'scalar',
            )
        ),
    );

    protected function setUp()
    {
        if (!interface_exists('Doctrine\Common\Persistence\ObjectManager')) {
            $this->markTestSkipped('Doctrine Common has to be installed for this test to run.');
        }

        $this->om = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->ed = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcher');
        $this->object = new GlobalScopeManager($this->om);
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
        $repository->expects($this->once())
            ->method('loadSettings')
            ->with('app', 0)
            ->will($this->returnValue($loadedSettings));

        $this->om
            ->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $object = $this->object;

        $this->assertEquals(
            $this->loadedSettings['oro_user']['level']['value'],
            $object->getSettingValue('oro_user.level')
        );

        $this->assertNull($object->getSettingValue('oro_user.greeting'));
        $this->assertNull($object->getSettingValue('oro_test.nosetting'));
        $this->assertNull($object->getSettingValue('noservice.nosetting'));
    }

    /**
     * Test get info from loaded settings
     */
    public function testGetInfoLoaded()
    {
        $datetime = new \DateTime('now', new \DateTimeZone('UTC'));
        $loadedSettings = array(
            'oro_user' => array(
                'level'    => array(
                    'value' => 2000,
                    'type'  => 'scalar',
                    'createdAt' => $datetime,
                    'updatedAt' => $datetime,
                )
            ),
        );

        $repository = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Entity\Repository\ConfigRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('loadSettings')
            ->with('app', 0)
            ->will($this->returnValue($loadedSettings));

        $this->om
            ->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $object = $this->object;
        list($created, $updated, $isNullValue) = $object->getInfo('oro_user.level');

        $this->assertEquals($loadedSettings['oro_user']['level']['createdAt'], $created);
        $this->assertEquals($loadedSettings['oro_user']['level']['updatedAt'], $updated);
        $this->assertFalse($isNullValue);
    }

    /**
     * Test saving settings
     */
    public function testSave()
    {
        $settings = array(
            'oro_user___level' => array(
                'value' => 50,
            ),
        );

        $removed = array(
            'oro_user___greeting' => array(
                'oro_user', 'greeting',
            ),
        );

        $object = $this->getMock(
            'Oro\Bundle\ConfigBundle\Config\GlobalScopeManager',
            array('calculateChangeSet', 'getSettingValue'),
            array($this->om)
        );

        $changes = array(
            $settings, $removed
        );

        $object->expects($this->once())
            ->method('calculateChangeSet')
            ->with($this->equalTo($settings))
            ->will($this->returnValue($changes));

        $configMock = $this->getMock('Oro\Bundle\ConfigBundle\Entity\Config');
        $configMock->expects($this->once())
            ->method('getOrCreateValue')
            ->will($this->returnValue(new ConfigValue()));
        $configMock->expects($this->once())
            ->method('getValues')
            ->will($this->returnValue(new ArrayCollection()));

        $repository = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Entity\Repository\ConfigRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('getByEntity')
            ->with('app', 0)
            ->will($this->returnValue($configMock));

        $this->om
            ->expects($this->at(0))
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $this->om
            ->expects($this->at(1))
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $this->om
            ->expects($this->once())
            ->method('persist');
        $this->om
            ->expects($this->once())
            ->method('flush');

        $object->save($settings);
    }

    /**
     * Test getChanged
     */
    public function testGetChanged()
    {
        $settings = array(
            'oro_user___level' => array(
                'value' => 50,
            ),
        );

        $object = $this->getMock(
            'Oro\Bundle\ConfigBundle\Config\GlobalScopeManager',
            array('getSettingValue'),
            array($this->om)
        );

        $currentValue = array(
            'value' => 20,
            'use_parent_scope_value' => false,
        );
        $object->expects($this->once())
            ->method('getSettingValue')
            ->with('oro_user.level')
            ->will($this->returnValue($currentValue));

        $object->calculateChangeSet($settings);
    }

    /**
     * @param string $configPath
     *
     * @return SystemConfigurationFormProvider
     */
    protected function getProviderWithConfigLoaded($configPath)
    {
        $config = Yaml::parse(file_get_contents($configPath));

        $processor = new ProcessorDecorator(
            new Processor(),
            ['some_field', 'some_another_field', 'some_ui_only_field', 'some_api_only_field']
        );
        $config = $processor->process($config);

        $subscriber    = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Form\EventListener\ConfigSubscriber')
            ->setMethods(array('__construct'))
            ->disableOriginalConstructor()->getMock();

        $formType       = new FormType($subscriber);
        $formFieldType  = new FormFieldType();
        $useParentScope = new ParentScopeCheckbox();

        $extensions = array(
            new PreloadedExtension(
                array(
                    $formType->getName()       => $formType,
                    $formFieldType->getName()  => $formFieldType,
                    $useParentScope->getName() => $useParentScope
                ),
                array()
            ),
        );

        $factory = Forms::createFormFactoryBuilder()
            ->addExtensions($extensions)
            ->addTypeExtension(
                new DataBlockExtension()
            )
            ->getFormFactory();

        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
                    ->disableOriginalConstructor()->getMock();

        $provider = new SystemConfigurationFormProvider($config, $factory, $securityFacade);

        return $provider;
    }
}
