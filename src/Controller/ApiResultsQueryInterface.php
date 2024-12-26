<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\{Request, Response};

/**
 * Interface ApiResultsQueryInterface
 *
 * @package App\Controller
 *
 */
interface ApiResultsQueryInterface
{
    public final const RUTA_API = '/api/v1/results';

    /**
     * **CGET** Action<br>
     * Summary: Retrieves the collection of Result resources.<br>
     * _Notes_: Returns all results from the system that the user has access to.
     */
    public function cgetAction(Request $request): Response;

    /**
     * **GET** Action<br>
     * Summary: Retrieves a Result resource based on a single ID.<br>
     * _Notes_: Returns the result identified by <code>resultId</code>.
     *
     * @param int $resultId Result id
     */
    public function getAction(Request $request, int $resultId): Response;

    /**
     * **OPTIONS** Action<br>
     * Summary: Provides the list of HTTP supported methods<br>
     * _Notes_: Return a <code>Allow</code> header with a list of HTTP supported methods.
     *
     * @param  int|null $resultId Result id
     */
    public function optionsAction(?int $resultId): Response;
}