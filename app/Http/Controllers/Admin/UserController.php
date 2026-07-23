<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Muestra la tabla de usuarios con buscador y paginación.
     */
    public function index(Request $request)
    {
        $buscar = $request->input('buscar');

        $usuarios = User::when($buscar, function ($query, $buscar) {
            return $query->where('name', 'LIKE', "%{$buscar}%")
                         ->orWhere('email', 'LIKE', "%{$buscar}%");
        })
        ->orderBy('id', 'desc')
        ->paginate(10);

        return view('maestros.gestion_usuarios', compact('usuarios'));
    }

    /**
     * Muestra el formulario para crear usuario (si usas vista separada).
     */
public function create()
{
    return view('maestros.crear_usuario');
}
    /**
     * Guarda un nuevo usuario.
     */
    public function store(Request $request)
{
    $request->validate([
        'name'     => 'required|string|max:255',
        'email'    => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8|confirmed',
        'role'     => 'required|in:ADMINISTRADOR,USUARIO',
        'status'   => 'required|in:0,1',
    ]);

   User::create([
    'name'     => $request->name,
    'email'    => $request->email,
    'password' => Hash::make($request->password),
    'role'     => $request->role,
    'status'   => 1, // Se fuerza el estado 1 siempre en la base de datos
]);

    return redirect()->route('admin.users.index')->with('success', 'Usuario registrado correctamente.');
}

    /**
     * Actualiza nombre y correo del usuario.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
        ]);

        return redirect()->route('admin.users.index')->with('success', '¡Datos del usuario actualizados!');
    }

    /**
     * Cambia la contraseña del usuario.
     */
    public function updatePassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.users.index')->with('success', '¡Contraseña actualizada correctamente!');
    }

    /**
     * Elimina un usuario del sistema.
     */
    public function destroy($id)
    {
        // Evita que el usuario se elimine a sí mismo
        if (auth()->id() == $id) {
            return redirect()->route('admin.users.index')->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', '¡Usuario eliminado exitosamente!');
    }


    // Muestra el formulario de cambio de contraseña
public function editPassword()
{
    return view('maestros.cambiar_password');
}


// Procesa y guarda la nueva contraseña del usuario autenticado
public function updateOwnPassword(Request $request)
{
    $validated = $request->validate([
        'current_password' => ['required', 'current_password'],
        'password'         => ['required', 'confirmed', Password::defaults()],
    ], [
        'current_password.current_password' => 'La contraseña actual no es correcta.',
        'password.confirmed'                => 'La confirmación de la nueva contraseña no coincide.',
        'password.required'                 => 'Debe ingresar una nueva contraseña.',
    ]);

    $request->user()->update([
        'password' => Hash::make($validated['password']),
    ]);

    return back()->with('status', '¡Contraseña actualizada con éxito!');
}


/**
     * Inactivar o Activar la cuenta de un usuario usando la columna 'status' (1 = Activo, 0 = Inactivo)
     */
public function toggleStatus($id)
{
    $usuario = User::findOrFail($id);
    
    // Cambiamos el estado (si es 1 pasa a 0, si es 0 pasa a 1)
    $usuario->status = (int)$usuario->status === 1 ? 0 : 1;
    $usuario->save(); // <-- Guarda la actualización en el registro existente

    return redirect()->back()->with('success', 'El estado del usuario se ha actualizado correctamente.');
}
    /**
     * Cambiar el rol del usuario usando la columna 'role'
     */
    public function updateRole(Request $request, User $user)
{
    $request->validate([
        'role' => 'required|in:ADMINISTRADOR,USUARIO',
    ]);

    $user->update([
        'role' => $request->role
    ]);

    return back()->with('success', 'Rol actualizado correctamente.');
}
}