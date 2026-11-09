<?php
// Last Modified Date: 18-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Services\Fsm;

use App\Models\Fsm\HelpDesk;
use Illuminate\Support\Collection;
use Illuminate\Session\SessionManager;
use DB;
use Carbon\Carbon;
use Auth;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\WriterFactory;
use Yajra\DataTables\DataTables;

class HelpDeskService {

    protected $session;
    protected $instance;

    /**
     * Constructs a new HelpDesk object.
     *
     *
     */
    public function __construct()
    {
        /*Session code
        ....
         here*/


    }

    /**
     * Get all the All Employee Info.
     *
     *
     * @return EmployeeInfo[]|Collection
     */
    public function getAllHelpDesks($data)
    {
        if (Auth::user()->hasRole('Service Provider - Admin') ){
            $helpDesk = HelpDesk::Join('fsm.service_providers','service_providers.id', '=', 'fsm.help_desks.service_provider_id')
            ->select('fsm.help_desks.*', 'service_providers.company_name as service_provider')
            ->whereNull('fsm.help_desks.deleted_at')
            ->where('service_provider_id','=',Auth::user()->service_provider_id)
            ->latest('created_at');
        }
        else
        {  
            $helpDesk = DB::table('fsm.help_desks')
            ->leftJoin('fsm.service_providers', 'fsm.service_providers.id', '=', 'fsm.help_desks.service_provider_id')
            ->select('fsm.help_desks.*', 'fsm.service_providers.company_name as service_provider')
            ->whereNull('fsm.help_desks.deleted_at')
            ->groupBy('fsm.help_desks.id', 'fsm.service_providers.company_name');
        }
        return Datatables::of($helpDesk)
            ->filter(function ($query) use ($data) {
                if ($data['help_desk_id']){
                    $query->where('fsm.help_desks.id',$data['help_desk_id']);
                }
                if ($data['name']){
                    $query->where('fsm.help_desks.name','ILIKE','%'.$data['name'].'%');
                }
                 if ($data['servprov']){
                     $query->where('fsm.help_desks.service_provider_id',$data['servprov']);
                 }
        
            })
            ->addColumn('action', function ($model) {
                $content = \Form::open(['method' => 'DELETE',

                'route' => ['help-desks.destroy', $model->id]]);

                if (Auth::user()->can('Edit Help Desk')) {
                    $content .= '<a title="' . e(__("Edit")) . '" href="' . action("Fsm\HelpDeskController@edit", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-edit"></i></a> ';
                }
                if (Auth::user()->can('View Help Desk')) {
                    $content .= '<a title="' . e(__("Detail")) . '" href="' . action("Fsm\HelpDeskController@show", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-list"></i></a> ';
                }
                if (Auth::user()->can('View Help Desk History')) {
                    $content .= '<a title="' . e(__("History")) . '" href="' . action("Fsm\HelpDeskController@history", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-history"></i></a> ';
                }

                if (Auth::user()->can('Delete Help Desk')) {
                    $content .= '<a href="#" title="' . e(__("Delete")) . '"  class="delete btn btn-danger btn-sm mb-1"><i class="fa fa-trash"></i></a> ';
                }

                $content .= \Form::close();
                return $content;
            })
            ->make(true);
    }
    /**
     * Store or update a newly created resource in storage.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function storeOrUpdate($id = null,$data)
    {
        if(is_null($id)){
            $helpDesk = new HelpDesk();
            $helpDesk->name = $data['name'] ? $data['name'] : null;
            $helpDesk->description = $data['description'] ? $data['description'] : null;
            $helpDesk->contact_number = $data['contact_number'] ? $data['contact_number'] : null;
            $helpDesk->email = $data['email'] ? $data['email'] : null;
            $helpDesk->service_provider_id = Auth::user()->service_provider_id?Auth::user()->service_provider_id:null;
            $helpDesk->save();
        }
        else{
            $helpDesk = HelpDesk::find($id);
            $helpDesk->name = $data['name'] ? $data['name'] : null;
            $helpDesk->description = $data['description'] ? $data['description'] : null;
            $helpDesk->contact_number = $data['contact_number'] ? $data['contact_number'] : null;
            $helpDesk->email = $data['email'] ? $data['email'] : null;
            $helpDesk->service_provider_id = Auth::user()->service_provider_id?Auth::user()->service_provider_id:null;
            $helpDesk->save();
        }
    }

    /**
     * Download a listing of the specified resource from storage.
     *
     * @param array $data
     * @return null
     */
    public function download($data)
    {
        $searchData = $data['searchData'] ? $data['searchData'] : null;

        $help_desk_id = $data['help_desk_id'] ? $data['help_desk_id'] : null;
        $help_desk_name = $data['name'] ? $data['name'] : null;
        $servprov = $data['servprov'] ? $data['servprov'] : null;
        $columns = [__('ID'), __('Help Desk Name'), __('Description'), __('Contact Number'), __('Email Address'), __('Service Provider Name')];


        if (Auth::user()->hasRole('Service Provider - Admin') || Auth::user()->hasRole('Service Provider - Emptying Operator')
        || Auth::user()->hasRole('Service Provider - Help Desk') || !empty(Auth::user()->service_provider_id))
        {
            $query = HelpDesk::leftjoin('fsm.service_providers','fsm.service_providers.id', '=', 'fsm.help_desks.service_provider_id')
                ->select('fsm.help_desks.id', 'fsm.help_desks.name',
                    'fsm.help_desks.description', 'fsm.help_desks.contact_number',
                    'fsm.help_desks.email', 'fsm.service_providers.company_name as service_provider')
                ->whereNull('fsm.help_desks.deleted_at')
                ->where('fsm.help_desks.service_provider_id','=',Auth::user()->service_provider_id)
                ->latest('fsm.help_desks.created_at');

        }else{
        $query = HelpDesk::leftjoin('fsm.service_providers', 'fsm.service_providers.id', '=', 'fsm.help_desks.service_provider_id')
            ->select('fsm.help_desks.id', 'fsm.help_desks.name',
                    'fsm.help_desks.description', 'fsm.help_desks.contact_number',
                    'fsm.help_desks.email', 'fsm.service_providers.company_name as service_provider')
            ->whereNull('fsm.help_desks.deleted_at')
            ->groupBy('fsm.help_desks.id', 'fsm.service_providers.company_name')
            ->orderBy('fsm.help_desks.id');

        }

        if(!empty($help_desk_id)){
            $query->where('fsm.help_desks.id',$help_desk_id);
        }

        if(!empty($help_desk_name)){
            $query->where('fsm.help_desks.name','ILIKE','%'.$help_desk_name.'%');
        }
        
        if(!empty($servprov)){
            $query->where('fsm.help_desks.service_provider_id',$servprov);
        }
        
        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(13)
            ->setBackgroundColor(Color::rgb(228, 228, 228))
            ->build();

        $writer = WriterFactory::create(Type::CSV);
        $writer->openToBrowser('Help Desks.csv')
            ->addRowWithStyle($columns, $style); //Top row of excel

        $query->chunk(5000, function ($helpDesks) use ($writer) {
            $writer->addRows($helpDesks->toArray());
        });

        $writer->close();

    }

}
