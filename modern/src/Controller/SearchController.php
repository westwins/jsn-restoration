<?php

namespace App\Controller;

use App\Exception\InvalidQueryException;
use App\Exception\QueryException;
use App\Query;
use App\SearchService;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SearchController
 * @package App\Controller
 */
class SearchController extends LegacyController
{
    #[Route(path: '/search/', name: 'search')]
    public function searchAction(Request $request)
    {
        $searchService = $this->get('jsn.search');

        try {
            $query = $searchService->getQueryFromRequest($request);
        } catch (QueryException $e) {
            $message = ($e instanceof InvalidQueryException)
                     ? 'Your search query could not be understood.'
                     : null;

            return $this->render('default/search.html.twig', [
                'message' => $message
            ]);
        }

        $search = $searchService->create();
        $search->execute($query);
        $qb = $search->getQueryBuilder();
        $filter = $searchService->getFilters(clone $qb);

        $pagination = $this->get('knp_paginator')->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            20,
            [
                'defaultSortFieldName' => 'name',
                'defaultSortDirection' => 'asc'
            ]
        );

        return $this->render('search/results.html.twig', [
            'pagination' => $pagination,
            'query' => $query,
            'params' => array_intersect_key(
                array_merge(array('types' => Query::types()), $request->query->all()),
                array_flip(SearchService::getParamsWhitelist())
            ),
            'filter' => $filter
        ]);
    }
}
