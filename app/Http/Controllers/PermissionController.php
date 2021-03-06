<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Hash;
use App\Permission;
use App\User;
use Illuminate\Database\QueryException;

class PermissionController extends Controller
{

    public function getPermissions(Request $request)
    {
        $permissions = Permission::get();

        return response()->json([
            'message' => 'permissions_index',
            'data' => response()->json($permissions)
        ]);
    }

    public function createPermission(Request $request) 
    {

        $this->validate($request, [
            'name' => 'max:255|unique:permissions,name|required',
            'description' => 'max:255'
        ]);


        $permission = new Permission ($request->all());

        $permission->save();

        return response()->json([
             'message' => 'permission_created',
             'data' => response()->json($permission)
        ]);
    }

    public function deletePermission(Request $request, $id)
    {
        if (! $permission = Permission::find($id)) 
        {
            return response()->json(['message' => 'non_existing_permission']);
        }

        // first get affected users
        $users_ids = $permission->getUserIdsByPermission($id);

        // then delete with InnoDB cascade
        $permission->delete();

        return response()->json([
            'message' => 'permission_deleted',
            'UserIds_affected' => $users_ids
        ]);
    }

    public function getPermissionsByUid(Request $request, $uid)
    {
        $user = User::find($uid);
        $permissions = (new Permission())->getPermissionsForUser($user['id']);

        if (! $permissions->isEmpty()) {
            return response()->json([
                'message' => 'user_has_permissions',
                'data' => $permissions
            ]);
        }

        return response()->json(['message' => 'user_has_no_permissions']);

    }

    public function hasUserPermission(Request $request, $uid, $pid)
    {
        $permission = new Permission();
        $permissionExists = $permission->existsForUser($uid, $pid);

        if ($permissionExists) {
            return response()->json([
                    'message' => 'user_has_permission',
                    'permission' => $pid
            ]);
        }

        return response()->json(['message' => 'user_doesnt_have_permission']);
    }

    public function assignPermission(Request $request, $uid, $pid)
    {
        $permission = new Permission();

        if ($permission->existsForUser($uid, $pid)) {
            return response()->json(['message' => 'permission_already_exists']);
        }

        if($permission->assignToUser($uid, $pid))
        {
            return response()->json(['message' => 'permission_assigned_to_user']);
        }
        return response()->json(['message' => 'permission_not_assigned_to_user']);
    }

    public function dissociatePermission(Request $request, $uid, $pid)
    {
        $permission = new Permission();

        if (! $permission->existsForUser($uid, $pid)) {
            return response()->json(['message' => 'permission_not_exists_for_user']);
        }

        if($permission->dissociateFromUser($uid, $pid))
        {
            return response()->json(['message' => 'permission_dissociated_from_user']);
        }
        return response()->json(['message' => 'permission_not_dissociated_from_user']);
    }

}