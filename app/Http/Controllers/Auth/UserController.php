<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\Fsm\TreatmentPlant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Models\Fsm\ServiceProvider;
use Spatie\Permission\Models\Role;
use DB;
use App\Models\Application;
use Encore\Admin\Layout\Content;
use App\Services\Auth\UserService;
use App\Models\Fsm\HelpDesk;
use App\Enums\UserStatus;

class UserController extends Controller
{
    protected UserService $userService;
    /**
    * Create a new controller instance.
    *
    * @param UserService $userService The user service instance
    * @return void
    */
    public function __construct(UserService $userService)
    {
        $this->middleware('auth');
        $this->middleware('permission:List Users', ['only' => ['index','getData']]);
        $this->middleware('permission:View User', ['only' => ['show']]);
        $this->middleware('permission:Add User', ['only' => ['create','store']]);
        $this->middleware('permission:Edit User', ['only' => ['edit','update']]);
        $this->middleware('permission:Delete User', ['only' => ['destroy']]);
        $this->middleware('permission:Export Users to CSV', ['only' => ['export']]);
        $this->middleware('permission:View User Login Activity', ['only' => ['getLoginActivity']]);
        $this->userService = $userService;
    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $page_title = __("Users");
        $users = $this->userService->getAllData($request);
        return view('users.index')->with(compact('users', 'page_title'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $page_title = __("Create User");
       if (!$request->user()->hasRole("Super Admin") && !$request->user()->hasRole("Municipality - IT Admin")){
            if ($request->user()->hasRole("Municipality - Sanitation Department")){
                $roles = Role::where('name','!=', 'Super Admin')
                    ->where('name','LIKE','%Service Provider%')
                    ->orWhere('name','LIKE','%Treatment Plant%')
                    ->pluck('name','name');
                $helpDesks = HelpDesk::orderBy('name')->pluck('name', 'id');            }
            else {
                $roles = Role::where('name', '!=', 'Super Admin')
                    ->where('name', '!=', 'Service Provider - Admin')
                    ->where('name', '!=', 'Service Provider')
                    ->where('name', 'LIKE', '%Service Provider%')
                    ->pluck('name', 'name');
                $helpDesks = HelpDesk::where('service_provider_id', '=', $request->user()->service_provider_id)->orderBy('name')->pluck('name', 'id');
            }
        }else{
            $roles = Role::where('name', '!=', 'Super Admin')->pluck('name','name')->all();
            $helpDesks = HelpDesk::orderBy('name')->pluck('name', 'id');
        }
        $munhelpDesks = HelpDesk::orderBy('name')->whereNull('service_provider_id')->pluck('name', 'id');
        $treatmentPlants = TreatmentPlant::Operational()->orderBy('id')->pluck('name', 'id');
        $serviceProviders = ServiceProvider::Operational()->orderBy('company_name')->pluck('company_name', 'id');
        $status = UserStatus::asSelectArray();
        return view('users.create', compact('page_title', 'roles', 'treatmentPlants', 'helpDesks', 'serviceProviders', 'status', 'munhelpDesks'))
        ->with(['isEdit' => false]);
 
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        $data = $request->all();
        DB::beginTransaction();
        try {
        $this->userService->storeOrUpdate($id = null,$data);
        DB::commit();
        return redirect('auth/users')->with('success', __('User created successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating password: ' . $e->getMessage());
            return redirect()->back()->with('error', __('User could not be created.'));
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $userDetail = User::findorfail($id);
        $this->authorizeUserRecord($userDetail);

        $user = $this->userService->getUserRelatedData($id);
       
        $status = UserStatus::getDescription($userDetail->status);
        $userRoles = array();
        $munhelpDesks = HelpDesk::orderBy('name')->whereNull('service_provider_id')->pluck('name', 'id');
        foreach($userDetail->roles as $role) {
          $userRoles[] = $role->name;
        }
        if (!$userDetail->hasRole('Super Admin')) {
            $page_title = __("Users");
            return view('users.show')->with([ 'userDetail' => $userDetail, 'userRoles' => $userRoles, 'page_title' => $page_title, 'treatmentPlants' => $user['treatmentPlants'], 'helpDesks' => $user['helpDesks'], 'serviceProviders' => $user['serviceProviders'], 'status' => $status,'munhelpDesks'=>$munhelpDesks]);
        } else {
            abort(404);
        }
    }

    /**
     * Prevent users with a general view permission from enumerating user
     * records outside the organisation they administer.
     */
    private function authorizeUserRecord(User $target): void
    {
        $actor = Auth::user();

        if ($actor->hasAnyRole([
            'Super Admin',
            'Municipality - Super Admin',
            'Municipality - IT Admin',
            'Municipality - Executive',
        ])) {
            return;
        }

        $authorized = $actor->id === $target->id;

        if ($actor->hasRole('Municipality - Sanitation Department')) {
            $authorized = in_array($target->user_type, [
                'Service Provider',
                'Treatment Plant',
                'Help Desk',
            ], true);
        } elseif ($actor->service_provider_id) {
            $authorized = $actor->service_provider_id === $target->service_provider_id;
        } elseif ($actor->treatment_plant_id) {
            $authorized = $actor->treatment_plant_id === $target->treatment_plant_id;
        }

        abort_unless($authorized, 403);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request,$id)
    {
        $user = User::findorfail($id);
      
        if (!$user->hasRole('Super Admin')) {
            $page_title = __("Edit User");
            if (!$request->user()->hasRole("Super Admin") && !$request->user()->hasRole("Municipality - Super Admin") && !$request->user()->hasRole("Municipality - IT Admin")){
                $roles = Role::where('name','!=', 'Super Admin')
                    ->where('name','!=', 'Service Provider - Admin')
                    ->where('name','!=', 'Service Provider')
                    ->where('name','LIKE','%Service Provider%')
                    ->pluck('name','name');
                $helpDesks = HelpDesk::where('service_provider_id','=',$request->user()->service_provider_id)->orderBy('name')->pluck('name', 'id');
            }
            else if ($request->user()->hasRole("Service Provider - Admin")) {
              
                $helpDesks = HelpDesk::where('service_provider_id', '=', $request->user()->service_provider_id)->orderBy('name')->pluck('name', 'id');
              
            }
            else{
                $roles = Role::where('name', '!=', 'Super Admin')->pluck('name','name')->all();
                $helpDesks = HelpDesk::orderBy('name')->pluck('name', 'id');
            }
            $treatmentPlants = TreatmentPlant::Operational()->orderBy('id')->pluck('name', 'id');
            $serviceProviders = ServiceProvider::Operational()->orderBy('company_name')->pluck('company_name', 'id');
            $munhelpDesks = HelpDesk::orderBy('name')->whereNull('service_provider_id')->pluck('name', 'id');
            $userRole = $user->roles->pluck('name','name')->all();
            $role_arr = array();
            foreach ($user->roles as $role) {
                $role_arr[] = $role->name;
            }
            $user->roles = $role_arr;
            $status = UserStatus::asSelectArray();
            return view('users.edit', compact('page_title', 'user', 'roles', 'treatmentPlants', 'helpDesks', 'serviceProviders', 'status', 'munhelpDesks'))
            ->with(['isEdit' => true]);
     
        } else {
            abort(404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UserRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = User::findorfail($id);
            if (!$user->hasRole('Super Admin')) {
            $data = $request->all();
            $this->userService->storeOrUpdate($user->id,$request);
            DB::commit(); 
            return redirect('auth/users')->with('success', __('User updated successfully.'));
            } else {
                abort(404);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating user: ' . $e->getMessage());
            return redirect()->back()->with('error', __('User could not be updated.'));
        }
    }

   /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::findorfail($id);
        if (!$user->hasRole('Super Admin')) {

            if( $user->buildingSurveys()->exists() ||
                $user->buildings()->exists() ||
                $user->applications()->exists() ||
                $user->containments()->exists() ||
                $user->employees()->exists() ||
                $user->emptyings()->exists() ||
                $user->feedbacks()->exists() ||
                $user->serviceProviders()->exists() ||
                $user->sludgeCollections()->exists() ||
                $user->treatmetnPlantTests()->exists() ||
                $user->lics()->exists() ||
                $user->waterSamples()->exists() ||
                $user->hotSpots()->exists() ||
                $user->yearlyWaterborneCases()->exists() ||
                $user->sewerConnections()->exists() ||
                $user->drains()->exists() ||
                $user->sewers()->exists() ||
                $user->watersupplys()->exists() ||
                $user->roads()->exists()    )
                {
                    return redirect('auth/users')->with('error', __('User has created some records and cannot be deleted; Update Status to Inactive to revoke access'));
                }
                User::destroy($id);
            return redirect('auth/users')->with('success', __('User deleted successfully.'));
        } else {
            return redirect('auth/users')->with('error', __('User could not be deleted.'));
        }
    }

     /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getLoginActivity($id)
    {
        $userDetail = User::findorfail($id);
        //$authentications = User::find($id)->authentications;

        $last_login_at = User::find($id)->lastLoginAt();
        $last_login_ip = User::find($id)->lastLoginIp();

        if (!$userDetail->hasRole('Super Admin') ) {
            $page_title = __("Login Activity");
            return view('users.login-activity',compact('page_title', 'last_login_at', 'last_login_ip', 'userDetail'));
        } else {
            abort(404);
        }
    }

    public function getHelpDeskData($spid)
    {
        $data = HelpDesk::where('service_provider_id', $spid)
        ->orderBy('name')
        ->distinct('name')// Fetch only the id and name
        ->pluck('name', 'id'); // Fetch only the id and name
       
        return $data;

    }
   

    public function export(Request $request)
    {

        $data = $request->all();
        return $this->userService->download($data);

    }

}
