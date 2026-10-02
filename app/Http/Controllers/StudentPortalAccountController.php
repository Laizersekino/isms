<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentPortalAccountController extends Controller
{
    public function store(Student $student): RedirectResponse
    {
        $storedEmail = (string) $student->email;
        $email = trim($storedEmail);
        $normalizedEmail = mb_strtolower($email);

        if (
            $storedEmail !== $email
            || mb_strlen($email) > 255
            || ! filter_var($email, FILTER_VALIDATE_EMAIL)
            ||
            Student::query()
                ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
                ->count() !== 1
        ) {
            return back()->withErrors([
                'student' => 'A unique student email address is required to create a portal account.',
            ]);
        }

        $studentRole = Role::query()->where('name', 'Student')->first();

        if (! $studentRole) {
            return back()->withErrors([
                'student' => 'The Student role is not configured.',
            ]);
        }

        $matchingUsers = User::query()
            ->with('roles')
            ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
            ->get();

        if ($matchingUsers->count() > 1) {
            return back()->withErrors([
                'student' => 'This email maps to multiple user accounts. Review it before proceeding.',
            ]);
        }

        $user = $matchingUsers->first();

        if ($user) {
            if (
                $user->email !== $email
                || $user->roles->count() !== 1
                || $user->roles->first()?->name !== 'Student'
            ) {
                return back()->withErrors([
                    'student' => 'This email is already assigned to a conflicting account. Review it before proceeding.',
                ]);
            }

            return $this->sendResetLink($student, $user);
        }

        $user = DB::transaction(function () use ($student, $studentRole, $email): User {
            $user = User::query()->create([
                'name' => trim(implode(' ', array_filter([
                    $student->first_name,
                    $student->middle_name,
                    $student->last_name,
                ]))),
                'email' => $email,
                'password' => Str::random(64),
            ]);

            $user->roles()->attach($studentRole->id);

            $status = Password::sendResetLink(['email' => $email]);

            if ($status !== Password::RESET_LINK_SENT) {
                throw ValidationException::withMessages([
                    'student' => 'The portal account was not created because a password setup link could not be sent. Please try again later.',
                ]);
            }

            return $user;
        });

        $this->logPortalAccountAction($student, $user, 'created');

        return back()->with('success', 'Student portal account created and password setup link sent.');
    }

    private function sendResetLink(Student $student, User $user): RedirectResponse
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            return back()->withErrors([
                'student' => 'The password setup link could not be sent. Please try again later.',
            ]);
        }

        $this->logPortalAccountAction($student, $user, 'setup_link_sent');

        return back()->with('success', 'Password setup link sent to the Student email address.');
    }

    private function logPortalAccountAction(
        Student $student,
        User $user,
        string $action
    ): void {
        Log::info('Student portal account action completed.', [
            'action' => $action,
            'actor_user_id' => auth()->id(),
            'student_id' => $student->id,
            'portal_user_id' => $user->id,
        ]);
    }
}
