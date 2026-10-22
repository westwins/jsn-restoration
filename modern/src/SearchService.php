<?php

namespace App;

use App\DTO\Bounds;
use App\DTO\Radius;
use App\Entity\Source;
use App\Entity\SourceGroup;
use App\Entity\Subject;
use App\Entity\SubjectGroup;
use App\Exception\EmptyQueryException;
use App\Exception\InvalidQueryException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use App\Query as QueryDTO;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

class SearchService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var PaginatorInterface $paginator */
    private $paginator;

    /**
     * SearchService constructor
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em, PaginatorInterface $paginator)
    {
        $this->em = $em;
        $this->paginator = $paginator;
    }

    public static function getTypeWhere($field, $types)
    {
        $typeExpr = [];

        if ($types & \App\Query::TYPE_BIOGRAPHICAL) {
            $typeExpr[] = $field . ' = \'beginningOfLife\'';
            $typeExpr[] = $field . ' = \'entryInTheOrder\'';
            $typeExpr[] = $field . ' = \'resignationFromTheOrder\'';
            $typeExpr[] = $field . ' = \'expulsionFromTheOrder\'';
            $typeExpr[] = $field . ' = \'endOfLife\'';
        }

        if ($types & \App\Query::TYPE_CAREER) {
            $typeExpr[] = $field . ' = \'career\'';
        }

        if ($types & \App\Query::TYPE_EDUCATION) {
            $typeExpr[] = $field . ' = \'education\'';
        }

        if ($types & \App\Query::TYPE_MISCELLANEOUS) {
            $typeExpr[] = $field . ' = \'miscellaneous\'';
        }

        return $typeExpr ? implode(" OR ", $typeExpr) : '0=1';
    }

    public function render(QueryDTO $query, $page = 1)
    {
    }

    public function getFilters(QueryBuilder $qb)
    {
        $filters = [];

        $this->addCountriesFromQuery($filters, $qb);
        $this->addContinentsFromQuery($filters, $qb);
        $this->addSourcesFromQuery($filters, $qb);
        $this->addSubjectsFromQuery($filters, $qb);
        $this->addPlacesFromQuery($filters, $qb);
        $this->addOccupationsFromQuery($filters, $qb);

        return $filters;
    }

    /**
     * @return QueryBuilder
     */
    public function createQueryBuilder()
    {
        return $this->em->createQueryBuilder();
    }

    public function placesNear(Radius $radius)
    {
        $distanceSql = <<<EOSQL
SELECT
    id,
    (6371 * acos(cos(radians(:lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians(:lng)) + sin(radians(:lat)) * sin(radians(latitude)))) AS distance
FROM
    place
HAVING
    distance <= :radius
ORDER BY
    distance ASC
EOSQL;
        $ids = $this->em->getConnection()->executeQuery(
            $distanceSql,
            [
                'lat' => $radius->getCenter()->getLatitude(),
                'lng' => $radius->getCenter()->getLongitude(),
                'radius' => $radius->getRadius()
            ]
        )->fetchFirstColumn();

        return array_map('intval', $ids);
    }

    public function getSubjectGroupTree($whitelist = null)
    {
        /** @var QueryBuilder $qb */
        $qb = $this
            ->em
            ->getRepository('App\Entity\SubjectGroup')
            ->createQueryBuilder('g')
            ->select('g, s')
            ->innerJoin('g.subjects', 's')
            ->addOrderBy('g.title', \SortDirection::Ascending)
            ->addOrderBy('s.title', \SortDirection::Ascending)
        ;

        if (null !== $whitelist) {
            $qb->andWhere($qb->expr()->in('s.id', $whitelist));
        }

        $groups = $qb->getQuery()->execute();

        $callback = function (SubjectGroup $group) {
            return [
                'text' => $group->getTitle(),
                'selectable' => false,
                'disableCheckbox' => false,
                'nodes' => $group->getSubjects()->map(function (Subject $s) {
                    return [
                        'text' => $s->getTitle(),
                        'id' => $s->getId()
                    ];
                })->toArray(),
                'state' => ['expanded' => $group->getSubjects()->count() <= 3]
            ];
        };

        return array_map($callback, $groups);
    }

    public function getQueryFromRequest(Request $request)
    {
        $q = $request->query;
        $query = new \App\Query();
        $emptyQuery = true;

        $query->setTypes($q->get('types', \App\Query::types()));

        if ($q->has('radius')) {
            $emptyQuery = false;
            $query->setRadius(Radius::fromQuery($q));
        }

        if ($q->has('bounds')) {
            $emptyQuery = false;
            $query->setBounds(Bounds::fromUrlValue($q->get('bounds')));
        }

        if ($q->has('continent')) {
            $emptyQuery = false;
            $query->setContinent($q->get('continent'));
        }

        if ($q->has('country')) {
            $emptyQuery = false;
            $query->setCountry($q->get('country'));
        }

        if ($q->has('place')) {
            $emptyQuery = false;
            $place = $this
                ->em
                ->getRepository('App\Entity\Place')
                ->findOneBy(['slug' => $q->get('place')])
            ;

            $query->setPlace($place);
        }

        if ($q->has('sources')) {
            $emptyQuery = false;
            $qParts = explode(' ', $q->get('sources'));
            $groupIds = $sourceIds = [];
            foreach ($qParts as $qPart) {
                if (is_numeric($qPart)) {
                    $sourceIds[] = $qPart;
                } else {
                    $groupIds[] = $qPart;
                }
            }

            $sourceResult = $this->em->getRepository(Source::class)
                ->findBy(['id' => $sourceIds]);

            $groupResult = $this->em->getRepository(SourceGroup::class)
                ->findBy(['slug' => $groupIds]);

            $sources = array_merge($sourceResult, $groupResult);
            $query->setSources($sources);
        }

        if ($q->has('subjects')) {
            $emptyQuery = false;
            // what query
            $ids = explode(' ', $q->get('subjects'));
            $ids = array_map(function ($e) {
                return (int)$e;
            }, $ids);
            $ids = array_unique($ids);

            $subjResult = $this->em->getRepository('App\Entity\Subject')
                ->createQueryBuilder('s')
                ->select('s.id, s.title')
                ->orderBy('s.title', \SortDirection::Ascending)
                ->where('s.id IN(:ids)')
                ->setParameter('ids', $ids)
                ->getQuery()
                ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
            ;

            $subjects = [];
            foreach ($subjResult as $subject) {
                $subjects[$subject['id']] = $subject['title'];
            }
            $query->setSubjects($subjects);
        }

        if ($q->has('from') && $q->has('to')) {
            $emptyQuery = false;
            // when query
            $query->setFrom($q->get('from'));
            $query->setTo($q->get('to'));
        }

        if ($q->has('occupation')) {
            $emptyQuery = false;
            $query->setOccupation($q->get('occupation'));
        }

        if ($q->has('jesuit')) {
            $emptyQuery = false;
            $query->setJesuit((bool)$q->get('jesuit'));
        }

        if ($q->has('ems')) {
            $emptyQuery = false;
            $query->setEms((bool)$q->get('ems'));
        }

        if ($emptyQuery) {
            if ($request->query->count() > 0) {
                throw new InvalidQueryException();
            } else {
                throw new EmptyQueryException();
            }
        }

        return $query;
    }

    public static function getParamsWhitelist()
    {
        return [
            'types',
            'radius',
            'lat',
            'lng',
            'placeName',
            'bounds',
            'continent',
            'country',
            'place',
            'subjects',
            'from',
            'to',
            'occupation',
            'jesuit',
            'ems',
            'sources',
            // 'page' not included to reset results to first page
            'sort',
            'direction'
        ];
    }

    public function getPersonsForSubjects($ids)
    {
        $pqb = $this
            ->em
            ->getRepository('App\Entity\Person')
            ->createQueryBuilder('p')
            ->select('DISTINCT p.id')
            ->innerJoin('p.subjects', 'si')
            ->where('si.id IN (:ids)')
            ->setParameter('ids', array_map('intval', $ids))
        ;

        $pids = $pqb->getQuery()->getResult(Query::HYDRATE_ARRAY);
        $pids = array_map(function ($e) {
            return $e['id'];
        }, $pids);

        return $pids;
    }

    public function create()
    {
        return new Search($this);
    }

    private function addCountriesFromQuery(&$filters, QueryBuilder $qb)
    {
        $qb = clone $qb;
        $countries = $qb
            ->select('pl.country, count(pl.country) as cnt')
            ->andWhere('pl.country is not null')
            ->orderBy('cnt', \SortDirection::Descending)
            ->groupBy('pl.country')
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY)
        ;
        $countries = array_combine(
            array_map(function ($e) {
                return $e['country'];
            }, $countries),
            array_map(function ($e) {
                return Helper::formatCountry($e['country']);
            }, $countries)
        );

        $filters['countries_short'] = array_slice($countries, 0, 7, true);

        asort($countries);
        $filters['countries'] = $countries;
    }

    private function addContinentsFromQuery(&$filters, QueryBuilder $qb)
    {
        $qb = clone $qb;
        $continents = $qb
            ->select('distinct pl.continent')
            ->andWhere('pl.continent is not null')
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY)
        ;
        $continents = array_combine(
            array_map(function ($e) {
                return $e['continent'];
            }, $continents),
            array_map(function ($e) {
                return Helper::formatContinent($e['continent']);
            }, $continents)
        );

        asort($continents);
        $filters['continents'] = $continents;
    }

    private function addSourcesFromQuery(&$filters, QueryBuilder $qb)
    {
        $qb = clone $qb;

        // since it's not very helpful to only see the currently selected
        // source ids in the filter view, we have to remove the source
        // condition from the query.
        $qb_where_part = $qb->getDqlPart('where')->getParts();
        $qb->resetDQLPart('where');
        foreach ($qb_where_part as $where_clause) {
            if (is_object($where_clause) and $where_clause instanceof Query\Expr\Orx) {
                continue;
            }
            $qb->andWhere($where_clause);
        }
        $params = $qb->getParameters();
        foreach ($params as $key => $param) {
            if (strpos($param->getName(), 'src') === 0) {
                $params->remove($key);
            }
        }
        $qb->setParameters($params);

        $sourceIds = $qb
            ->select('distinct src.id, count(src.id) as srcCount')
            ->groupBy('src.id')
            ->orderBy('srcCount', \SortDirection::Descending)
            ->getQuery()->getResult(Query::HYDRATE_ARRAY);
        $sourceIds = array_map(function ($e) {
            return $e['id'];
        }, $sourceIds);

        $sources = $this
            ->em
            ->createQueryBuilder()
            ->select('s')
            ->from('App\Entity\Source', 's')
            ->where('s.id IN(:ids)')
            ->andWhere('s.sourceGroup IS NULL')
            ->orderBy('FIELD(s.id, :ids)')
            ->setParameter('ids', $sourceIds)
            ->getQuery()
            ->getResult()
        ;

        $groups = $this
            ->em
            ->createQueryBuilder()
            ->select('g')
            ->from('App\Entity\SourceGroup', 'g')
            ->innerJoin('g.sources', 's')
            ->where('s.id IN(:ids)')
            ->orderBy('g.title', \SortDirection::Ascending)
            ->setParameter('ids', $sourceIds)
            ->getQuery()
            ->getResult()
        ;

        $filters['sources'] = array(
            'sources' => $sources,
            'groups' => $groups,
        );
    }

    private function addSubjectsFromQuery(&$filters, QueryBuilder $qb)
    {
        $qb = clone $qb;
        $subjects = $qb
            ->select('s.id, s.title, count(s.id) as cnt')
            ->orderBy('cnt', \SortDirection::Descending)
            ->andWhere('s.id IS NOT NULL')
            ->groupBy('s.id')
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        $count = count($subjects);

        $filters['subjects_short'] = $count > 0 ? array_slice($subjects, 0, 7, true) : array();

        $filters['subjects_count'] = $count;
        if ($count > 7) {
            $filters['subjects'] = $this->getSubjectGroupTree(array_map(function ($s) {
                return $s['id'];
            }, $subjects));
        }

    }

    private function addPlacesFromQuery(&$filters, QueryBuilder $qb)
    {
        $qb = clone $qb;

        $filters['places'] = $qb
            ->select('distinct pl.placeName, pl.slug, pl.country, pl.continent, pl.id')
            ->orderBy('pl.continent, pl.country, pl.placeName')
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);
    }

    private function addOccupationsFromQuery(&$filters, QueryBuilder $qb)
    {
        $qb = clone $qb;

        $occupations = $qb
            ->select('a.occupation as occupation, a.occupationSlug, count(a.occupationSlug) as cnt')
            ->orderBy('cnt', \SortDirection::Descending)
            ->andWhere('a.occupationSlug > \'\'')
            ->groupBy('a.occupationSlug')
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY)
        ;

        $filters['occupations'] = array_combine(
            array_map(function ($o) {
                return $o['occupationSlug'];
            }, $occupations),
            array_map(function ($o) {
                return $o['occupation'];
            }, $occupations)
        );
    }
}
