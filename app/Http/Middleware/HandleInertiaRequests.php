<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\CompanyDetail;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        if (in_array($request->getHost(), config('tenancy.central_domains'))) {
            return [
                ...parent::share($request),
                'auth' => [
                    'user' => $request->user(),
                ],
            ];
        }

        App::setLocale('es');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'companyDetails' => fn (): array => CompanyDetail::getAll(),
            'defaultCurrency' => fn (): ?\App\Models\Currency => Currency::query()->default()->first(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'newlyCreatedContact' => fn () => $request->session()->get('newly_created_contact') ?: null,
            'workspaceUsers' => fn (): array => $this->getWorkspaceUsers($request),
            'unreadNotifications' => fn () => $request->user()?->unreadNotifications()->count() ?? 0,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * Get users in the current workspace for mention autocomplete
     */
    private function getWorkspaceUsers(Request $request): array
    {
        if (! $request->user()) {
            return [];
        }

        $currentWorkspace = $request->user()->getCurrentWorkspaceSafely();

        if (! $currentWorkspace) {
            return [];
        }

        return User::whereHas('workspaces', function ($query) use ($currentWorkspace): void {
            $query->where('workspace_id', $currentWorkspace->id);
        })
            ->select(['id', 'name', 'email'])
            ->orderBy('name')
            ->get()
            ->toArray();
    }
}
