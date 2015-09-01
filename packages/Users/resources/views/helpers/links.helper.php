<?php

/**
 * Array View Helper
 * 
 * @param  array  $items
 * @return array
 */
return function ($items = []) {

    $hrefSelf = Request::fullUrl();

    $links = [
        'self' => [
            'href' => $hrefSelf,
            'type' => 'application/json; version=1.0',
        ]
    ];

    if (count($items)) {
        $last = $items[count($items) - 1];

        $queries = Input::all();
        $queries = array_merge($queries, [
            'cursor' => $last->id,
        ]);
        $hrefNext = url(Request::url()) . '?' . http_build_query($queries);

        $links['next'] = [
            'href' => $hrefNext,
            'type' => 'application/json; version=1.0',
        ];
    }

    return $links;
};
