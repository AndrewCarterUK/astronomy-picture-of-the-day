<?php

namespace AndrewCarterUK\APOD;

interface APIInterface
{
    /**
     * Retrieve a page of pictures.
     *
     * @param int $page
     * @param int $numberPerPage
     *
     * @return array
     */
    public function getPage($page = 0, $numberPerPage = 24);

    /**
     * Update the store.
     *
     * @param int|bool $limit        The maximum number of pictures to attempt to download, false if there is no limit
     * @param callable $newPicture   Called when a new picture is added, parameter is the picture array
     * @param callable $errorPicture Called when an error occurs, parameter is an exception
     */
    public function updateStore($limit = 1, callable $newPicture = null, callable $errorPicture = null);

    /**
     * Clear the store.
     */
    public function clearStore();
}
