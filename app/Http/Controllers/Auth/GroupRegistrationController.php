<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use App\Services\Blogger\GroupMemberService;
use App\Services\TranslationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class GroupRegistrationController extends Controller
{
    public function __construct(private readonly TranslationService $translations) {}

    /**
     * Show the group registration page.
     *
     * @throws HttpException
     */
    public function create(Group $group): Response
    {
        $this->ensureRegistrationAllowed($group);

        return Inertia::render('app/auth/Register', [
            'registrationEnabled' => true,
            'registrationAction' => route('group.register.store', $group),
            'groupName' => $group->name,
            'groupUrl' => route('group.landing', $group),
            'translations' => [
                'locale' => app()->getLocale(),
                'messages' => $this->translations->getPageTranslations('auth'),
            ],
        ]);
    }

    /**
     * Handle an incoming group registration request.
     *
     * @throws ValidationException
     * @throws HttpException
     * @throws Throwable
     */
    public function store(Request $request, Group $group, GroupMemberService $groupMemberService): RedirectResponse
    {
        $this->ensureRegistrationAllowed($group);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            DB::transaction(function () use ($validated, $group, $groupMemberService): void {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                ]);

                event(new Registered($user));

                Auth::login($user);

                $groupMemberService->addMember($group, $user->email);
            });
        } catch (Throwable $exception) {
            Auth::logout();

            throw $exception;
        }

        return redirect()->intended(route('group.landing', $group));
    }

    private function ensureRegistrationAllowed(Group $group): void
    {
        abort_unless($group->allow_registration, 404);
    }
}
