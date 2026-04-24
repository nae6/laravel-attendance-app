<?php

namespace App\Providers;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Fortify;
use App\Actions\Fortify\CreateNewUser;
use App\Http\Responses\LogoutResponse;
use App\Http\Responses\LoginResponse;
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

        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // registerのカスタマイズ
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::registerView(function () {
            return view('auth.register');
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(10)->by($email . $request->ip());
        });

        // loginのカスタマイズ
        Fortify::authenticateUsing(function (Request $request) {
            $formRequest = LoginRequest::createFrom($request);
            $formRequest->setContainer(app())->validateResolved();
            $validated = $formRequest->validated();

            $user = User::where('email', $validated['email'])->first();
            $role = $request->login_type === 'admin' ? 'admin' : 'user';

            if (! $user) {
                throw ValidationException::withMessages([
                    'email' => 'ログイン情報が登録されていません。',
                ]);
            }
            if ($user->role !== $role) {
                throw ValidationException::withMessages([
                    'role' => 'この画面からはログインできません。',
                ]);
            }

            if (! Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => 'ログイン情報が登録されていません。',
                ]);
            }

            $request->session()->put('login_type', $request->login_type);

            return $user;
        });
    }
}
