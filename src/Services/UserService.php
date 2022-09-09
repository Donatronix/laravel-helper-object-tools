<?php

declare(strict_types=1);

namespace LaravelHelperObjectTools\Services;

use LaravelHelperObjectTools\Models\User;
use LaravelHelperObjectTools\Repositories\Interfaces\UserRepository;
use LaravelHelperObjectTools\Validators\UserValidator;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use LaravelDaily\LaravelCharts\Classes\LaravelChart;
use Throwable;

/**
 *
 */
class UserService extends BaseService implements Interfaces\UserServiceInterface
{
    /**
     * @var UserRepository
     */
    protected UserRepository $repository;

    /**
     * @var UserValidator
     */
    protected UserValidator $validator;

    /**
     * CountriesController constructor.
     */
    public function __construct(UserRepository $repository, UserValidator $validator)
    {
        $this->repository = $repository;
        $this->validator = $validator;
    }

    /**
     * @return UserValidator
     */
    public function getValidator(): UserValidator
    {
        return $this->validator;
    }


    /**
     * @return UserRepository
     */
    public function getRepository(): UserRepository
    {
        return $this->repository;
    }

    /**
     * Get all admins
     * @return Collection|null
     */
    public function admins(): ?Collection
    {
        return $this->repository->scopeQuery(function ($query) {
            return $query->role('admin')
                ->orderBy('name', 'asc');
        })->get();
    }

    /**
     * Get all super admins
     * @return Collection|null
     */
    public function superAdmins(): ?Collection
    {
        return $this->repository->scopeQuery(function ($query) {
            return $query->role('super admin')
                ->orderBy('name', 'asc');
        })->get();
    }

    /**
     * Get user with role
     * @param string $role
     * @return Collection|null
     */
    public function getUsersWithRoles(string $role): ?Collection
    {
        // Returns only users with the role
        return $this->repository->scopeQuery(function ($query) use ($role) {
            return $query->role($role)
                ->orderBy('created_at', 'desc');
        })->get();
    }

    /**
     * Get users registered within days
     * @param int $days
     * @return Collection|null
     */
    public function getRegisteredWithinDays(int $days): ?Collection
    {
        return $this->repository->findWhere(['created_at', '>=', now()->subDays($days)])->get();
    }

    /**
     * Group users by day of registration
     * @return Collection|null
     */
    public function groupUsersByDayOfRegistration(): ?Collection
    {
        return $this->repository->all()->groupBy(static function ($item) {
            return $item->created_at->format('Y-m-d');
        });
    }

    /**
     * @param string $title
     * @param string $groupByField
     * @param string $reportType
     * @return LaravelChart
     * @throws Exception
     */
    public function getChart(string $title, string $groupByField, string $reportType = 'group_by_date'): LaravelChart
    {
        $chart_options = [
            'chart_title' => $title,
            'report_type' => $reportType,
            'model' => 'App\Models\User',
            'group_by_field' => $groupByField,
            'group_by_period' => 'month',
            'chart_type' => 'bar',
        ];

        $chart = new ChartService($title, $this->repository->model());

        return $chart->setChartOptions($chart_options)->get();
    }


    /**
     * Get authenticated user
     * @return Model|Collection|Builder|array|null
     */
    public function authUser(): Model|Collection|Builder|array|null
    {
        return $this->repository->find(Auth::id());
    }

    /**
     * @param User $user
     * @param string $newPassword
     * @return bool
     */
    public function updateUserPassword(User $user, string $newPassword): bool
    {
        $user->password = Hash::make($newPassword);
        return $user->save();
    }

    /**
     * @param array $request
     * @return Model
     * @throws Throwable
     */
    public function register(array $request): Model
    {
        $request['password'] = Hash::make($request['password']);

        $user = $this->store($request);

        $this->repository->getModel()->roles->setRoleToMember($user, $request['membership']);

        if (isset($request['newsletter'])) {
            $this->newsletters->store($request);
        }

        return $user;
    }
}
