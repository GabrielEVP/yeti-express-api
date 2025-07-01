<?php

namespace App\Auth\Services;

use App\Auth\Models\User;
use App\Auth\Repositories\IAuthRepository;
use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService implements IAuthRepository
{
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'type' => 'user'
        ];
    }

    public function login(array $credentials): array
    {
        if (Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            $user = User::where('email', $credentials['email'])->firstOrFail();
            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
                'type' => 'user'
            ];
        }

        $employee = Employee::where('email', $credentials['email'])
            ->where('active', true)
            ->first();

        if ($employee && Hash::check($credentials['password'], $employee->password)) {
            $employee->tokens()->delete();
            $token = $employee->createToken('employee_token')->plainTextToken;

            EmployeeEvent::create([
                'employee_id' => $employee->id,
                'event' => 'login_employee',
                'section' => 'employees',
                'reference_table' => 'employees',
                'reference_id' => $employee->id
            ]);

            return [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $employee,
                'type' => 'employee'
            ];
        }

        throw ValidationException::withMessages([
            'email' => ['Las credenciales proporcionadas son incorrectas.'],
        ]);
    }

    public function logout(int $userId): void
    {
        $user = Auth::user();
        if ($user) {
            $user->tokens()->delete();
        }
    }

    public function updateProfile(int $userId, array $data): array
    {
        $user = Auth::user();
        $user->update($data);

        return [
            'message' => 'Usuario actualizado exitosamente.',
            'user' => $user->fresh(),
        ];
    }

    public function changePassword(int $userId): array
    {
        $user = Auth::user();
        $password = request()->input('password');

        $user->password = Hash::make($password);
        $user->save();

        return ['message' => 'Contraseña actualizada exitosamente.'];
    }
}
