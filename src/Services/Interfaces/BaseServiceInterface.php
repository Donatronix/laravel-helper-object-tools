<?php

declare(strict_types=1);

namespace LaravelHelperObjectTools\Services\Interfaces;

use Exception;
use Throwable;

interface BaseServiceInterface
{
    /**
     * @param  array  $request
     *
     * @throws Exception|Throwable
     */
    public function store(array $request): mixed;

    /**
     * @throws Exception
     */
    public function getValidator(): mixed;

    /**
     * @throws Exception
     */
    public function getRepository(): mixed;

    /**
     * @param  array  $request
     * @param    $id
     * @return mixed
     *
     * @throws Exception|Throwable
     */
    public function update(array $request, $id): mixed;

    /**
     * @param $id
     * @return int|null
     *
     * @throws Throwable
     */
    public function delete($id): ?int;
}
