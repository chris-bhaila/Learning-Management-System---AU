<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\CourseGroupRepositoryInterface;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\UnitRepositoryInterface;
use App\Repositories\Contracts\TokenRepositoryInterface;
use App\Repositories\Contracts\FileRepositoryInterface;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use App\Repositories\Contracts\NotificationReadRepositoryInterface;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Repositories\Eloquent\EloquentRoleRepository;
use App\Repositories\Eloquent\EloquentCourseGroupRepository;
use App\Repositories\Eloquent\EloquentCourseRepository;
use App\Repositories\Eloquent\EloquentUnitRepository;
use App\Repositories\Eloquent\EloquentTokenRepository;
use App\Repositories\Eloquent\EloquentFileRepository;
use App\Repositories\Eloquent\EloquentActivityLogRepository;
use App\Repositories\Eloquent\EloquentNotificationReadRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, EloquentRoleRepository::class);
        $this->app->bind(CourseGroupRepositoryInterface::class, EloquentCourseGroupRepository::class);
        $this->app->bind(CourseRepositoryInterface::class, EloquentCourseRepository::class);
        $this->app->bind(UnitRepositoryInterface::class, EloquentUnitRepository::class);
        $this->app->bind(TokenRepositoryInterface::class, EloquentTokenRepository::class);
        $this->app->bind(FileRepositoryInterface::class, EloquentFileRepository::class);
        $this->app->bind(ActivityLogRepositoryInterface::class, EloquentActivityLogRepository::class);
        $this->app->bind(NotificationReadRepositoryInterface::class, EloquentNotificationReadRepository::class);
    }
}