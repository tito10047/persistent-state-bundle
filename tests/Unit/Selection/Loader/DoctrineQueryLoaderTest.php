<?php

namespace Tito10047\PersistentStateBundle\Tests\Unit\Selection\Loader;

use Doctrine\ORM\EntityManagerInterface;
use Tito10047\PersistentStateBundle\Selection\Loader\DoctrineQueryLoader;
use Tito10047\PersistentStateBundle\Tests\App\AssetMapper\Src\Entity\RecordInteger;
use Tito10047\PersistentStateBundle\Tests\App\AssetMapper\Src\Factory\RecordIntegerFactory;
use Tito10047\PersistentStateBundle\Tests\App\AssetMapper\Src\Factory\TestCategoryFactory;
use Tito10047\PersistentStateBundle\Tests\Integration\Kernel\AssetMapperKernelTestCase;

class DoctrineQueryLoaderTest extends AssetMapperKernelTestCase
{
    public function testBasic()
    {
        $records = RecordIntegerFactory::createMany(10);

        $loader = new DoctrineQueryLoader();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();
        $query = $em->createQueryBuilder()
                ->select('i')
                ->from(RecordInteger::class, 'i')
                ->orderBy('i.id', 'ASC')
                ->setMaxResults(5)
                ->getQuery();

        $this->assertTrue($loader->supports($query));
        $this->assertEquals(10, $loader->getTotalCount($query));

        $ids = array_map(fn (RecordInteger $record) => $record->getId(), $records);
        sort($ids);
        $foundIds = $loader->loadAllIdentifiers(null, $query);
        sort($foundIds);

        $this->assertEquals($ids, $foundIds);
    }

    public function testWithWhere(): void
    {
        $records = RecordIntegerFactory::createMany(10);

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();

        // očakávané ID podľa vygenerovaného mena z factory
        $expectedIds = array_values(array_map(
            fn (RecordInteger $r) => $r->getId(),
            array_filter($records, fn (RecordInteger $r) => 'keep' === $r->getName(), ARRAY_FILTER_USE_BOTH)
        ));

        $loader = new DoctrineQueryLoader();

        $qb = $em->createQueryBuilder()
                ->select('i')
                ->from(RecordInteger::class, 'i')
                ->where('i.name = :name')
                ->setParameter('name', 'keep')
                ->orderBy('i.id', 'DESC')
                ->setFirstResult(2)
                ->setMaxResults(3);

        $query = $qb->getQuery();

        $this->assertTrue($loader->supports($query));
        $this->assertEquals(count($expectedIds), $loader->getTotalCount($query));
        sort($expectedIds);

        $foundIds = $loader->loadAllIdentifiers(null, $query);
        sort($foundIds);

        $this->assertEquals($expectedIds, $foundIds);
    }

    public function testWithJoin(): void
    {       /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();

        // vytvor pár kategórií s názvom "A" (nemusia byť priradené žiadnemu záznamu)
        TestCategoryFactory::createOne(['name' => 'A']);
        TestCategoryFactory::createOne(['name' => 'A']);

        $records = RecordIntegerFactory::createMany(10);
        $loader = new DoctrineQueryLoader();

        // očakávané ID podľa kategórie s názvom A
        $expectedIds = array_values(array_map(
            fn (RecordInteger $r) => $r->getId(),
            array_filter($records, fn (RecordInteger $r) => $r->getCategory() && 'A' === $r->getCategory()->getName(), ARRAY_FILTER_USE_BOTH)
        ));

        $qb = $em->createQueryBuilder()
            ->select('i')
            ->from(RecordInteger::class, 'i')
            ->join('i.category', 'c')
            ->where('c.name = :name')
            ->setParameter('name', 'A')
            ->orderBy('i.id', 'DESC')
            ->setFirstResult(1)
            ->setMaxResults(2);

        $query = $qb->getQuery();

        $this->assertTrue($loader->supports($query));
        $this->assertEquals(count($expectedIds), $loader->getTotalCount($query));

        $foundIds = $loader->loadAllIdentifiers(null, $query);
        sort($foundIds);

        $this->assertEquals($expectedIds, $foundIds);
    }

    public function testGetCacheKeyStableAndDistinct(): void
    {
        RecordIntegerFactory::createMany(3);

        $loader = new DoctrineQueryLoader();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();

        $qb1 = $em->createQueryBuilder()
            ->select('i')
            ->from(RecordInteger::class, 'i')
            ->where('i.name = :name')
            ->setParameter('name', 'keep');
        $q1 = $qb1->getQuery();

        $qb2 = $em->createQueryBuilder()
            ->select('i')
            ->from(RecordInteger::class, 'i')
            ->where('i.name = :name')
            ->setParameter('name', 'keep');
        $q2 = $qb2->getQuery();

        // Rovnaká filtrácia → rovnaký cache key
        $k1a = $loader->getCacheKey($q1);
        $k1b = $loader->getCacheKey($q1);
        $k2 = $loader->getCacheKey($q2);
        $this->assertSame($k1a, $k1b);
        $this->assertSame($k1a, $k2);

        // Zmena parametra → iný cache key
        $qb2->setParameter('name', 'drop');
        $q3 = $qb2->getQuery();
        $k3 = $loader->getCacheKey($q3);
        $this->assertNotSame($k2, $k3);
    }
}
