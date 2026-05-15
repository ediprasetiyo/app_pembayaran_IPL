<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->orderByDesc('created_at');

        if ($request->role) {
            $query->where('role', $request->role);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->status) {
            $query->where('is_active', $request->status === 'aktif');
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|unique:users,phone',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:super_admin,admin,warga',
        ]);

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'User berhasil ditambahkan.',
            'user' => $user,
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['user' => $user->load('warga')]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:100',
            'phone' => 'sometimes|string|unique:users,phone,' . $user->id,
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'role' => 'sometimes|in:super_admin,admin,warga',
            'is_active' => 'sometimes|boolean',
            'password' => 'nullable|string|min:6',
        ]);

        // Cegah user mengubah role/non-aktifkan dirinya sendiri
        if ($user->id === $request->user()->id) {
            if ($request->has('role') && $request->role !== $user->role) {
                return response()->json(['message' => 'Tidak bisa mengubah role akun sendiri.'], 422);
            }
            if ($request->has('is_active') && !$request->is_active) {
                return response()->json(['message' => 'Tidak bisa menonaktifkan akun sendiri.'], 422);
            }
        }

        $data = $request->only(['name', 'phone', 'email', 'role', 'is_active']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'message' => 'User berhasil diperbarui.',
            'user' => $user->fresh(),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Tidak bisa menghapus akun sendiri.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User berhasil dihapus.']);
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'password' => 'required|string|min:6',
        ]);

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Password berhasil direset.']);
    }
}
