<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class BooksController extends Controller
{
    public function getSearch(Request $request)
    {
        $baseUrl = "https://www.googleapis.com/books/v1/volumes";

    $baseQuery = $request->query('q');
    $startIndex = $request->query('startIndex');
    $maxResults = $request->query('maxResults');
    $title = $request->query('title');
    $author = $request->query('author');
    $isbn = $request->query('isbn');

    // Build query parts with prefixes where needed
    $queryParts = [];
    if ($baseQuery) {
        $queryParts[] = $baseQuery;
    }
    if ($title) {
        $queryParts[] = 'intitle:' . $title;
    }
    if ($author) {
        $queryParts[] = 'inauthor:' . $author;
    }
    if ($isbn) {
        $queryParts[] = 'isbn:' . $isbn;
    }

    // Join parts with '+' to form the q param
    $finalQuery = implode('+', $queryParts);

    // Prepare the full params array
    $params = [
        'q' => $finalQuery,
        'key' => env('GOOGLE_BOOKS_API_KEY'),
        'printType' => 'books',
        'startIndex' => $startIndex,
        'maxResults' => $maxResults,
    ];

    // Build the full URL for debugging
    $queryString = http_build_query($params);
    $finalUrl = $baseUrl . '?' . $queryString;

    // Make the API call
    $response = Http::get($baseUrl, $params);

    // Return the API response + the final URL for debugging
    return response()->json([
        'finalUrl' => $finalUrl,
        'response' => $response->json(),
    ]);
    }
}
