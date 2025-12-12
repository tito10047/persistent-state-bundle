<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Selection\Loader;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Tito10047\PersistentStateBundle\Transformer\ValueTransformerInterface;

/**
 * Loader responsible for extracting identifiers and counts from a Doctrine ORM Query object.
 * This class modifies the underlying DQL for optimized SELECT and COUNT queries.
 */
final class DoctrineQueryLoader implements IdentityLoaderInterface
{
    public function supports(mixed $source): bool
    {
        // Podporuje iba Doctrine Query (nie QueryBuilder)
        return $source instanceof Query;
    }

    /**
     * @param Query|QueryBuilder $source
     *
     * @return array<int|string>
     */
    public function loadAllIdentifiers(?ValueTransformerInterface $transformer, mixed $source): array
    {
        if (!$this->supports($source)) {
            throw new \InvalidArgumentException('Source must be a Doctrine Query instance.');
        }

        /** @var Query $baseQuery */
        $baseQuery = clone $source;
        $entityManager = $baseQuery->getEntityManager();
        // Parametre si vezmeme z pôvodného zdroja (klon môže prísť o väzbu na parametre)
        $sourceParameters = $source->getParameters();

        [$rootEntity, $rootAlias] = $this->resolveRootFromDql($baseQuery);

        $metadata = $entityManager->getClassMetadata($rootEntity);
        $identifierFields = $metadata->getIdentifierFieldNames();
        if (1 !== count($identifierFields)) {
            throw new \RuntimeException('Composite alebo neštandardný identifikátor nie je podporovaný pre loadAllIdentifiers().');
        }

        $defaultIdField = $identifierFields[0];
        $identifierField = $defaultIdField;

        $dql = $baseQuery->getDQL();
        $posFrom = stripos($dql, ' from ');
        if (false === $posFrom) {
            throw new \RuntimeException('Neplatný DQL – chýba FROM klauzula.');
        }
        $newDql = 'SELECT '.$rootAlias.'.'.$identifierField.substr($dql, $posFrom);

        $idQuery = $entityManager->createQuery($newDql);
        // prenes parametre z pôvodného dotazu (aby WHERE ostal funkčný)
        $idQuery->setParameters($sourceParameters);

        $rows = $idQuery->getScalarResult();

        return array_map('current', $rows);
    }

    /**
     * @param Query $source the Doctrine Query instance
     */
    public function getTotalCount(mixed $source): int
    {
        if (!$this->supports($source)) {
            throw new \InvalidArgumentException('Source must be a Doctrine Query instance.');
        }

        // očakáva sa iba Query

        /** @var Query $baseQuery */
        $baseQuery = clone $source;
        $entityManager = $baseQuery->getEntityManager();

        [$rootEntity, $rootAlias] = $this->resolveRootFromDql($baseQuery);

        // ak je k dispozícii jednoduché ID pole, rátaj COUNT(DISTINCT alias.id), inak COUNT(alias)
        $metadata = $entityManager->getClassMetadata($rootEntity);
        $identifierFields = $metadata->getIdentifierFieldNames();
        $countExpr = null;
        if (1 === count($identifierFields)) {
            $countExpr = 'COUNT(DISTINCT '.$rootAlias.'.'.$identifierFields[0].')';
        } else {
            $countExpr = 'COUNT('.$rootAlias.')'; // fallback
        }

        // poskladaj COUNT dopyt z pôvodného DQL: vymeniť SELECT časť a odstrániť ORDER BY
        $dql = $baseQuery->getDQL();
        $posFrom = stripos($dql, ' from ');
        if (false === $posFrom) {
            throw new \RuntimeException('Neplatný DQL – chýba FROM klauzula.');
        }

        // odstráň ORDER BY (ak je)
        $dqlTail = substr($dql, $posFrom);
        $posOrderBy = stripos($dqlTail, ' order by ');
        if (false !== $posOrderBy) {
            $dqlTail = substr($dqlTail, 0, $posOrderBy);
        }

        $newDql = 'SELECT '.$countExpr.$dqlTail;

        $countQuery = $entityManager->createQuery($newDql);
        // použijeme parametre z pôvodného Query (nie z klonu)
        $countQuery->setParameters($source->getParameters());

        try {
            return (int) $countQuery->getSingleScalarResult();
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to execute optimized count query.', 0, $e);
        }
    }

    /**
     * Vytiahne root entitu a alias z Query DQL pomocou Doctrine Parsera.
     *
     * @return array{0:string,1:string} [entityClass, alias]
     */
    private function resolveRootFromDql(Query $query): array
    {
        $AST = $query->getAST();

        /** @var Query\AST\IdentificationVariableDeclaration $from */
        $from = $AST->fromClause->identificationVariableDeclarations[0] ?? null;
        if (null === $from || null === $from->rangeVariableDeclaration) {
            throw new \RuntimeException('Nepodarilo sa zistiť root entitu z DQL dotazu.');
        }

        $rootEntity = $from->rangeVariableDeclaration->abstractSchemaName;
        $rootAlias = $from->rangeVariableDeclaration->aliasIdentificationVariable;

        if (!is_string($rootEntity) || !is_string($rootAlias)) {
            throw new \RuntimeException('Neplatný FROM klauzula v DQL dotaze.');
        }

        return [$rootEntity, $rootAlias];
    }

    public function getCacheKey(mixed $source): string
    {
        if (!$this->supports($source)) {
            throw new \InvalidArgumentException('Source must be a Doctrine Query instance.');
        }

        /** @var Query $source */
        $dql = $source->getDQL();
        $params = $source->getParameters(); // Doctrine\Common\Collections\Collection of Parameter
        $normParams = [];
        foreach ($params as $p) {
            $name = method_exists($p, 'getName') ? $p->getName() : null;
            $value = method_exists($p, 'getValue') ? $p->getValue() : null;
            $normParams[] = [
                'name' => $name,
                'value' => self::normalizeValue($value),
            ];
        }
        // ensure deterministic order
        usort($normParams, function ($a, $b) {
            return strcmp((string) $a['name'], (string) $b['name']);
        });

        return 'doctrine_query:'.md5(serialize([$dql, $normParams]));
    }

    /**
     * Normalize values for a deterministic cache key.
     */
    private static function normalizeValue(mixed $value): mixed
    {
        if (is_scalar($value) || null === $value) {
            return $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return ['__dt__' => true, 'v' => $value->format(DATE_ATOM)];
        }
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $k => $v) {
                $normalized[$k] = self::normalizeValue($v);
            }
            if (!array_is_list($normalized)) {
                ksort($normalized);
            }

            return $normalized;
        }
        if (is_object($value)) {
            // try to reduce to public props for stability
            $vars = get_object_vars($value);
            ksort($vars);

            return ['__class__' => get_class($value), 'props' => self::normalizeValue($vars)];
        }

        return (string) $value;
    }
}
