<?php

namespace App\Repositories;

use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Yajra\Datatables\Datatables;

class UserRepository implements UserRepositoryInterface {

    public function datatables() {
        return Datatables::of(User::with('role')->orderBy('id','desc')->get())
        ->editColumn('actions', function($col) {
            $actions = '';

            if (\Laratrust::isAbleTo('user-edit-data')) {
                $actions .= '
                    <a href="' . route('user.edit', ['user' => $col->id]) . '" class="btn btn-xs bg-gradient-info" data-toggle="tooltip" data-placement="top" title="Edit">
                        <i class="fa fa-pencil-alt" aria-hidden="true"></i>
                    </a>
                ';
            }

            if (\Laratrust::isAbleTo('user-destroy-data')) {
                $actions .= '
                    <a href="' . route('user.destroy', ['user' => $col->id]) . '" class="btn btn-xs bg-gradient-danger" onclick="return confirm(\'Anda yakin untuk menghapus data ?\');" data-toggle="tooltip" data-placement="top" title="Delete">
                        <i class="fa fa-trash-alt" aria-hidden="true"></i>
                    </a>
                ';
            }

            return $actions;
        })
        ->rawColumns(['actions'])
        ->addIndexColumn()
        ->make(true);
    }

    public function save($userData) {
        \DB::beginTransaction();

        try {
            $user = new User($userData);
            $user->password = \Hash::make($userData['password']);

            $user->save();
            $user->attachRole($userData['role_id']);

            \DB::commit();

            return true;
        } catch(\Exception $e) {
            \DB::rollback();

            return false;
        }
        
    }

    public function update($reqParam, $userData) {
        \DB::beginTransaction();

        try {
            $password = $reqParam->password;
            $update = $reqParam->all();

            if (!empty($password)) {
                $password = \Hash::make($password);
                
                $update['password'] = $password;
            } else {
                unset($update['password']);
            }

            $userData->update($reqParam->all());

            $roles = $userData->roles();

            foreach($roles as $item) {
                $userData->detachRole($item);
            }

            $userData->attachRole($reqParam->role_id);

            \DB::commit();
        } catch(\Exception $e) {
            \DB::rollback();

            return false;
        }
    }

    public function destroy($id) {
        return User::destroy($id);
    }
}
