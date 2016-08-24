<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Prophecy\Prophecy\ObjectProphecy;

use Symfony\Component\HttpFoundation\File\File as HttpFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\ConfigBundle\Form\DataTransformer\ConfigFileDataTransformer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ConfigFileDataTransformerTest extends \PHPUnit_Framework_TestCase
{
    const FILE_ID = 1;
    const FILENAME = 'filename.jpg';

    /**
     * @var ConfigFileDataTransformer
     */
    protected $transformer;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var array
     */
    protected $constraints = ['constraints'];

    public function setUp()
    {
        $this->doctrineHelper = $this->prophesize(DoctrineHelper::class);
        $this->validator = $this->prophesize(ValidatorInterface::class);
        $this->fileManager = $this->prophesize(FileManager::class);

        $this->transformer = new ConfigFileDataTransformer(
            $this->doctrineHelper->reveal(),
            $this->validator->reveal(),
            $this->fileManager->reveal()
        );
        $this->transformer->setFileConstraints($this->constraints);
    }

    public function testTransformNull()
    {
        $this->assertNull($this->transformer->transform(null));
    }

    public function testTransformConfigValue()
    {
        $file = new File();

        $repo = $this->prophesize(EntityRepository::class);
        $repo->find(self::FILE_ID)->willReturn($file);

        $this->doctrineHelper->getEntityRepositoryForClass(File::class)->willReturn($repo->reveal());

        $this->assertEquals($file, $this->transformer->transform(self::FILE_ID));
    }

    public function testReverseTransformNull()
    {
        $this->assertNull($this->transformer->reverseTransform(null));
    }

    public function testReverseTransformEmptyFile()
    {
        $file = new File();
        $file->setEmptyFile(true);
        $file->setFilename(self::FILENAME);

        $em = $this->prepareEntityManager();
        $em->remove($file)->shouldBeCalled();
        $em->flush($file)->shouldBeCalled();

        $this->fileManager->deleteFile(self::FILENAME)->shouldBeCalled();

        $this->assertNull($this->transformer->reverseTransform($file));
    }

    public function testReverseTransformValidFile()
    {
        $httpFile = $this->prepareHttpFile();

        $file = $this->prepareFile($httpFile);
        $file->preUpdate()->shouldBeCalled();

        $this->validator->validate($httpFile, $this->constraints)->willReturn([]);

        $em = $this->prepareEntityManager();
        $em->persist($file)->shouldBeCalled();
        $em->flush($file)->shouldBeCalled();

        $this->assertEquals(self::FILE_ID, $this->transformer->reverseTransform($file->reveal()));
    }

    public function testReverseTransformInvalidFile()
    {
        $httpFile = $this->prepareHttpFile();

        $file = $this->prepareFile($httpFile);
        $file->preUpdate()->shouldNotBeCalled();

        $this->validator->validate($httpFile, $this->constraints)->willReturn(['violation']);

        $em = $this->prepareEntityManager();
        $em->persist($file)->shouldNotBeCalled();
        $em->flush($file)->shouldNotBeCalled();

        $this->assertEquals(self::FILE_ID, $this->transformer->reverseTransform($file->reveal()));
    }

    /**
     * @return EntityManager
     */
    private function prepareEntityManager()
    {
        $em = $this->prophesize(EntityManager::class);

        $this->doctrineHelper->getEntityManagerForClass(File::class)->willReturn($em->reveal());

        return $em;
    }

    /**
     * @param ObjectProphecy $httpFile
     * @return File|ObjectProphecy
     */
    protected function prepareFile(ObjectProphecy $httpFile)
    {
        $file = $this->prophesize(File::class);
        $file->getFile()->willReturn($httpFile->reveal());
        $file->getId()->willReturn(self::FILE_ID);
        $file->isEmptyFile()->willReturn(false);

        return $file;
    }

    /**
     * @return HttpFile|ObjectProphecy
     */
    protected function prepareHttpFile()
    {
        $httpFile = $this->prophesize(HttpFile::class);
        $httpFile->isFile()->willReturn(true);

        return $httpFile;
    }
}
