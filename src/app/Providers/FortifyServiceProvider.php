<?php

namespace App\Providers;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Fortify;
use App\Actions\Fortify\CreateNewUser;
use App\Http\Requests\LoginRequest;
use App\Models\User;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FortifyLoginRequest::class, LoginRequest::class);

        $this->app->instance(RegisterResponse::class, new class implements RegisterResponse {
            public function toResponse($request)
            {
                return redirect()->intended('/');
            }
        });

        $this->app->instance(LoginResponse::class, new class implements LoginResponse {
            public function toResponse($request)
            {
                return redirect()->intended('/');
            }
        });

        $this->app->instance(LogoutResponse::class, new class implements LogoutResponse {
            public function toResponse($request)
            {
                return redirect()->route('login');
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::registerView(function () {
            return view('auth.register');
        });

        Fortify::loginView(function () {
            return view('auth.login');
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(10)->by($email . $request->ip());
        });

        Fortify::authenticateUsing(function (Request $request) {
            $formRequest = LoginRequest::createFrom($request);
            $formRequest->setContainer(app())->validateResolved();

            $validated = $formRequest->validated();

            $user = User::where('email', $validated['email'])->first();

            if ($user && Hash::check($validated['password'], $user->password)) {
                return $user;
            }

            throw ValidationException::withMessages([
                    'password' => 'ログイン情報が登録されていません',
                ]);
        });
    }
}
