<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;

class AuthController extends Controller
{
    public function showRegister(): void
    {
        $this->view('auth/register', [
            'title' => 'Registro',
        ]);
    }

    public function register(): void
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($nombre === '' || $email === '' || $password === '') {
            $this->view('auth/register', [
                'title' => 'Registro',
                'error' => 'Debes completar todos los campos.'
            ]);
            return;
        }

        // Validar email único
        if (\App\Models\User::findByEmail($email)) {
            $this->view('auth/register', [
                'title' => 'Registro',
                'error' => 'El email ya está registrado.'
            ]);
            return;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $userId = \App\Models\User::create([
            'nombre' => $nombre,
            'email' => $email,
            'estado' => 'inactivo',
            'fecha_ingreso' => date('Y-m-d'),
            'password_hash' => $hash,
        ]);

        // Asignar rol colaborador automáticamente
        $roles = \App\Models\Role::all();
        $colaboradorRole = null;
        foreach ($roles as $role) {
            if (strtolower($role['nombre']) === 'colaborador') {
                $colaboradorRole = $role;
                break;
            }
        }
        if ($colaboradorRole) {
            \App\Models\Role::assignToUser($userId, $colaboradorRole['id']);
        }

        $this->view('auth/register', [
            'title' => 'Registro',
            'error' => null,
            'success' => 'Registro exitoso. Tu cuenta será activada por un administrador.'
        ]);
    }

    public function showLogin(): void
    {
        $this->view('auth/login', [
            'title' => 'Acceso',
        ]);
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $this->view('auth/login', [
                'title' => 'Acceso',
                'error' => 'Debes completar email y contraseña.',
            ]);
            return;
        }

        if (! Auth::attempt($email, $password)) {
            $this->view('auth/login', [
                'title' => 'Acceso',
                'error' => 'Credenciales invalidas o usuario inactivo.',
            ]);
            return;
        }

        $this->redirect('/');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/');
    }
}
