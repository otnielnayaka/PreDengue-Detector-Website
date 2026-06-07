<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // GET /api/users (admin)
    public function index()
    {
        return response()->json(
            User::select('id', 'name', 'email', 'role', 'is_active', 'created_at')->get()
        );
    }

    // POST /api/users (admin) - buat user baru
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,viewer',
        ]);
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        return response()->json(['message' => 'User dibuat', 'user' => $user], 201);
    }

    // PUT /api/users/{user} (admin) - update user
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'role' => 'sometimes|in:admin,viewer',
            'is_active' => 'sometimes|boolean',
            'password' => 'sometimes|min:6',
        ]);
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);
        return response()->json(['message' => 'User diupdate', 'user' => $user]);
    }

    // DELETE /api/users/{user} (admin)
    public function destroy(User $user)
    {
        $user->tokens()->delete();  // logout paksa
        $user->delete();
        return response()->json(['message' => 'User dihapus']);
    }
}
