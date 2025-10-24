<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|min:3|max:50',
            'email'    => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role'     => 'nullable|string|in:administrator,manager,user',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'role'     => $data['role'] ?? 'user',
            'active'   => array_key_exists('active', $data) ? (bool) $data['active'] : true,
        ]);

        return response()->json([
            'message' => 'User created',
            'data' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role,
                'created_at' => $user->created_at,
            ],
        ], 201);
    }

    public function index(Request $request) {
        $request->validate([
            'search'        => 'nullable|string',
            'page'          => 'nullable|integer|min:1',
            'sortBy'        => 'nullable|string|in:name,email,created_at',
            'limit'         => 'nullable|integer|min:1|max:100',
            'currentRole'   => 'nullable|string|in:administrator,manager,user',
            'currentUserId' => 'nullable|integer|min:1',
        ]);

        $search        = (string) $request->query('search', '');
        $sortBy        = (string) $request->query('sortBy', 'created_at');
        $page          = (int) ($request->query('page', 1));
        $limit         = (int) ($request->query('limit', 10));
        $currentRole   = (string) $request->query('currentRole', 'user');
        $currentUserId = $request->has('currentUserId') ? (int) $request->query('currentUserId') : null;

        $query = User::query()
            ->where('active', true)
            ->withCount('orders');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $term = '%'.str_replace('%', '\\%', $search).'%';
                $q->where('name', 'ilike', $term)
                  ->orWhere('email', 'ilike', $term);
            });
        }

        $query->orderBy($sortBy);

        $paginator = $query->paginate($limit, ['*'], 'page', max($page, 1));

        $paginator->getCollection()->transform(function (User $user) use ($currentRole, $currentUserId) {
            $canEdit = false;
            if ($currentRole === 'administrator') {
                $canEdit = true;
            } elseif ($currentRole === 'manager') {
                $canEdit = ($user->role === 'user');
            } elseif ($currentRole === 'user') {
                $canEdit = ($currentUserId !== null && $currentUserId === (int) $user->id);
            }

            return [
                'id'           => $user->id,
                'name'         => $user->name,
                'email'        => $user->email,
                'role'         => $user->role,
                'active'       => $user->active,
                'created_at'   => $user->created_at,
                'orders_count' => $user->orders_count,
                'can_edit'     => $canEdit,
            ];
        });

        return response()->json($paginator);
    }
}
