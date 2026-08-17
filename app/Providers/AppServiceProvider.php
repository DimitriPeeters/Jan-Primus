<?php

declare(strict_types=1);

namespace App\Providers;

use AEFS\Core\Config;
use AEFS\Core\Container;
use AEFS\Core\Database;
use AEFS\Core\Logger;

use App\Mappers\MemberMapper;

use App\Repositories\MemberRepository;
use App\Repositories\UserRepository;

use App\Services\AuthenticationService;
use App\Services\EncryptionService;
use App\Services\MemberService;

use App\Validators\MemberValidator;

final class AppServiceProvider
{
    public static function register(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Core
        |--------------------------------------------------------------------------
        */

        Container::singleton(
            Config::class,
            fn() => new Config()
        );

        Container::singleton(
            Database::class,
            fn() => Database::getInstance()
        );

        Container::singleton(
            Logger::class,
            fn() => new Logger()
        );

        /*
        |--------------------------------------------------------------------------
        | Services
        |--------------------------------------------------------------------------
        */

        Container::singleton(
            EncryptionService::class,
            fn() => new EncryptionService()
        );

        /*
        |--------------------------------------------------------------------------
        | Validators
        |--------------------------------------------------------------------------
        */

        Container::singleton(
            MemberValidator::class,
            fn() => new MemberValidator()
        );

        /*
        |--------------------------------------------------------------------------
        | Mappers
        |--------------------------------------------------------------------------
        */

        Container::singleton(
            MemberMapper::class,
            fn() => new MemberMapper(
                Container::get(
                    EncryptionService::class
                )
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Repositories
        |--------------------------------------------------------------------------
        */

        Container::singleton(
            MemberRepository::class,
            fn() => new MemberRepository(

                Container::get(
                    Database::class
                ),

                Container::get(
                    MemberMapper::class
                )

            )
        );

        Container::singleton(
            UserRepository::class,
            fn() => new UserRepository(
                Container::get(
                    Database::class
                )
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Business Services
        |--------------------------------------------------------------------------
        */

        Container::singleton(
            MemberService::class,
            fn() => new MemberService(

                Container::get(
                    MemberRepository::class
                ),

                Container::get(
                    MemberValidator::class
                )

            )
        );

        Container::singleton(
            AuthenticationService::class,
            fn() => new AuthenticationService(

                Container::get(
                    UserRepository::class
                )

            )
        );
    }
}