<?php

declare(strict_types=1);

namespace LaravelHelperObjectTools\Services;

use LaravelHelperObjectTools\Services\Interfaces\BaseServiceInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use Prettus\Validator\Contracts\ValidatorInterface;
use Prettus\Validator\Exceptions\ValidatorException;
use Throwable;

class BaseService implements BaseServiceInterface
{
    /**
     * @param  array  $request
     *
     * @throws Exception|Throwable
     */
    public function store(array $request): mixed
    {
        try {
            DB::beginTransaction();
            $this->getValidator()->with($request)->passesOrFail(ValidatorInterface::RULE_CREATE);
            $value = $this->getRepository()->create($request);
            DB::commit();

            return $value;
        } catch (ValidatorException $e) {
            DB::rollBack();
            throw new Exception($e->getMessageBag());
        }
    }

    /**
     * @throws Exception
     */
    public function getValidator(): mixed
    {
        throw new Exception('The service should implement `getValidator` method');
    }

    /**
     * @throws Exception
     */
    public function getRepository(): mixed
    {
        throw new Exception('The service should implement `getRepository` method');
    }

    /**
     * @param  array  $request
     * @param    $id
     * @return mixed
     *
     * @throws Exception|Throwable
     */
    public function update(array $request, $id): mixed
    {
        try {
            DB::beginTransaction();
            $this->getValidator()->with($request)->passesOrFail(ValidatorInterface::RULE_UPDATE);
            $this->getRepository()->update($request, $id);
            DB::commit();

            return $this->getRepository()->find($id);
        } catch (ValidatorException $th) {
            throw new Exception($th->getMessageBag());
        }
    }

    /**
     * @param $id
     * @return int|null
     *
     * @throws Throwable
     */
    public function delete($id): ?int
    {
        try {
            return $this->getRepository()->delete($id);
        } catch (Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }
}
