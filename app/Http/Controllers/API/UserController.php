<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;

use App\Http\Controllers\Controller;

use App\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Create a new controller instance
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $this->authorize('isAdmin');
        if(Gate::allows('isAdmin') || Gate::allows('isAuthor')){
            return User::latest()->paginate(5);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:191',
            'email' => 'required|string|email|max:191|unique:users',
            'password' => 'required|string|min:6'
        ]);

        return User::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'type' => $request['type'],
            'bio' => $request['bio'],
            'photo' => $request['photo'],
            'password' => Hash::make($request['password']),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth('api')->user();
        $currentPhoto = $user->photo;

        $this->validate($request, [
            'name' => 'required|string|max:191',
            'email' => 'required|string|email|max:191|unique:users,email,'.$user->id,
            'password' => 'sometimes|required|min:6'
        ]);
        
        if ($request->photo != $currentPhoto) {
            $filenameExt = time().'-profile-picture.'.explode(';', explode('/', $request->photo)[1])[0];
            $dir = public_path('img/profile/');
            $photo64  = substr($request->photo, strpos($request->photo, ',') + 1);

            if(!File::isDirectory($dir))
                File::makeDirectory($dir, 0777,true);
            File::put($dir.$filenameExt, base64_decode($photo64, TRUE));

            $request->merge(['photo' => $filenameExt]);

            $userPhoto = public_path('img/profile/').$currentPhoto;
            if (File::exists($userPhoto)) {
                @unlink($userPhoto);
            }
        }

        if(!empty($request->password)){
            $request->merge(['password' => Hash::make($request['password'])]);
        }

        $user->update($request->all());
        return ['message' => 'Success'];
    }

    public function profile()
    {
        return auth('api')->user();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $this->validate($request, [
            'name' => 'required|string|max:191',
            'email' => 'required|string|email|max:191|unique:users,email,'.$user->id,
            'password' => 'sometimes|min:6'
        ]);

        $user->update($request->all());

        return ['message' => 'Updated'];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {        
        $this->authorize('isAdmin');

        $user = User::findOrFail($id);
        $user->delete();

        return ['message' => 'User Deleted'];
    }

    public function search(Request $request) {
        if ($search = $request->input('q')) {
            $users = User::where(function($query) use ($search){
                $query->where('name', 'LIKE', "%$search%")
                    ->orWhere('email', 'LIKE', "%$search%")
                    ->orWhere('type', 'LIKE', "%$search%");
            })->paginate(20);
        } else {
            $users = User::latest()->paginate(5);
        }

        return $users;
    }
}
