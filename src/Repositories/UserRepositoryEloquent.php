<?php

declare(strict_types=1);

namespace LaravelHelperObjectTools\Repositories;

use LaravelHelperObjectTools\Models\User;
use LaravelHelperObjectTools\Presenters\UserPresenter;
use LaravelHelperObjectTools\Repositories\Interfaces\UserRepository;
use LaravelHelperObjectTools\Validators\UserValidator;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Exceptions\RepositoryException;

/**
 * Class UserRepositoryEloquent.
 */
class UserRepositoryEloquent extends BaseRepository implements UserRepository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return User::class;
    }

    /**
     * Specify Validator class name
     */
    public function validator(): string
    {
        return UserValidator::class;
    }

    /**
     * Boot up the repository, pushing criteria
     * @throws RepositoryException
     */
    public function boot(): void
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    public function presenter(): string
    {
        return UserPresenter::class;
    }
}
