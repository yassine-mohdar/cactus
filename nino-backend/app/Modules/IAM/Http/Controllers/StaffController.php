<?php

namespace App\Modules\IAM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Audit\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    /**
     * Display a listing of the staff members.
     */
    public function index()
    {
        $this->authorize('viewAny', User::class);

        $query = User::staff()->with('roles');

        // Apply scoping automatically: if platform/super admin, see all.
        // If franchise/branch, only see users in their scope.
        $user = auth()->user();
        if ($user->organization_scope === 'franchise') {
            $query->where(function ($q) use ($user) {
                $q->where('organization_id', $user->organization_id)
                  ->orWhereHas('organization', function ($q2) use ($user) {
                      $q2->where('parent_id', $user->organization_id);
                  });
            });
        } elseif ($user->organization_scope === 'branch') {
            $query->where('organization_id', $user->organization_id);
        }

        $staff = $query->paginate(20);

        return view('admin.staff.index', compact('staff'));
    }

    /**
     * Show the form for creating a new staff member.
     */
    public function create()
    {
        $this->authorize('create', User::class);

        // Fetch roles that the current user is allowed to assign.
        // For now, let's simplify and just exclude Super Admin if not super admin.
        $rolesQuery = Role::query();
        if (!auth()->user()->isSuperAdmin()) {
            $rolesQuery->where('name', '!=', 'Super Admin');
        }
        $roles = $rolesQuery->get();

        return view('admin.staff.create', compact('roles'));
    }

    /**
     * Store a newly created staff member in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,name'],
            'organization_scope' => ['nullable', Rule::in(['platform', 'franchise', 'branch'])],
        ]);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->password = Hash::make($validated['password']);
        $user->type = 'staff';
        $user->status = $validated['status'];
        
        // Scope constraints based on creator
        $creator = auth()->user();
        if ($creator->organization_scope !== 'platform' && !$creator->isSuperAdmin()) {
            // Force inheritance of scope if not a platform admin
            $user->organization_scope = $creator->organization_scope;
            $user->organization_id = $creator->organization_id;
        } else {
            $user->organization_scope = $validated['organization_scope'] ?? 'platform';
            // TODO: Organization ID selection UI for platform admins
        }

        $user->save();

        if (!empty($validated['roles'])) {
            // Check if creator can assign these roles
            $allowedRoles = [];
            foreach ($validated['roles'] as $roleName) {
                $role = Role::findByName($roleName);
                if ($creator->can('assign', $role)) {
                    $allowedRoles[] = $roleName;
                }
            }
            $user->assignRole($allowedRoles);
        }

        AuditLog::record('staff.created', $user, null, $user->toArray(), 'Staff created manually');

        return redirect()->route('admin.staff.index')->with('success', 'Staff member created successfully.');
    }

    /**
     * Show the form for editing the specified staff member.
     */
    public function edit(User $staff)
    {
        $this->authorize('update', $staff);

        $rolesQuery = Role::query();
        if (!auth()->user()->isSuperAdmin()) {
            $rolesQuery->where('name', '!=', 'Super Admin');
        }
        $roles = $rolesQuery->get();

        return view('admin.staff.edit', compact('staff', 'roles'));
    }

    /**
     * Update the specified staff member in storage.
     */
    public function update(Request $request, User $staff)
    {
        $this->authorize('update', $staff);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($staff->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,name'],
            'organization_scope' => ['nullable', Rule::in(['platform', 'franchise', 'branch'])],
        ]);

        $oldValues = $staff->toArray();

        $staff->name = $validated['name'];
        $staff->email = $validated['email'];
        $staff->phone = $validated['phone'] ?? null;
        $staff->status = $validated['status'];
        
        if (!empty($validated['password'])) {
            $staff->password = Hash::make($validated['password']);
        }

        $creator = auth()->user();
        if ($creator->organization_scope === 'platform' || $creator->isSuperAdmin()) {
            $staff->organization_scope = $validated['organization_scope'] ?? $staff->organization_scope;
        }

        $staff->save();

        if (isset($validated['roles'])) {
            // Check if creator can assign these roles
            $allowedRoles = [];
            foreach ($validated['roles'] as $roleName) {
                $role = Role::findByName($roleName);
                if ($creator->can('assign', $role)) {
                    $allowedRoles[] = $roleName;
                }
            }
            // Preserve Super Admin if the current editor is not Super Admin but the target is
            if ($staff->isSuperAdmin() && !$creator->isSuperAdmin()) {
                $allowedRoles[] = 'Super Admin';
            }
            $staff->syncRoles($allowedRoles);
        }

        AuditLog::record('staff.updated', $staff, $oldValues, $staff->toArray(), 'Staff updated manually');

        return redirect()->route('admin.staff.index')->with('success', 'Staff member updated successfully.');
    }

    /**
     * Remove the specified staff member from storage.
     */
    public function destroy(User $staff)
    {
        $this->authorize('delete', $staff);
        
        $oldValues = $staff->toArray();
        $staff->delete();
        
        AuditLog::record('staff.deleted', null, $oldValues, null, 'Staff ID ' . $staff->id . ' deleted manually');

        return redirect()->route('admin.staff.index')->with('success', 'Staff member deleted successfully.');
    }
}
