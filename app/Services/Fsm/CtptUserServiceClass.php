<?php
// Last Modified Date: 19-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Services\Fsm;

use App\Models\Fsm\Ctpt;
use App\Models\Fsm\CtptUsers;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\WriterFactory;
use Auth;
use Yajra\DataTables\DataTables;
use DB;

class CtptUserServiceClass
{

    public function fetchData($request)
    {

        $cwis_mof = CtptUsers::whereNull('deleted_at');
        return Datatables::of($cwis_mof)
            ->filter(function ($query) use ($request) {

                if ($request->toilet_id) {
                        $query->whereHas('toilet', function ($query) use ($request) {
                            $query->whereRaw("concat(id, ' - ', name) ILIKE ?", ['%' . $request->toilet_id . '%']);
                        }); 
                }
                if ($request->date) {

                    $query->where('date',$request->date);
                }
            })
            ->addColumn('action', function ($model) {

                $content = \Form::open(['method' => 'DELETE', 'route' => ['ctpt-users.destroy', $model->id]]);

                if (Auth::user()->can('Edit PT Users Log')) {
                    $content .= '<a title="' . e(__("Edit")) . '" href="' . action("Fsm\CtptUserController@edit", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-edit"></i></a> ';
                }

                if (Auth::user()->can('View PT Users Log')) {
                    $content .= '<a title="' . e(__("Detail")) . '" href="' . action("Fsm\CtptUserController@show", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-list"></i></a> ';
                }
                if (Auth::user()->can('View PT Users Log History')) {
                    $content .= '<a title="' . e(__("History")) . '" href="' . action("Fsm\CtptUserController@history", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-history"></i></a> ';
                }
                if (Auth::user()->can('Delete PT Users Log')) {
                    $content .= '<a title="' . e(__("Delete")) . '"  class="delete btn btn-danger btn-sm mb-1"><i class="fa fa-trash"></i></a> ';
                }
                $content .= \Form::close();
                return $content;
            })
            ->editColumn('toilet_id', function ($model){
                $ctptData = Ctpt::select('id', 'name')
                    ->where('id', $model->toilet_id)
                    ->first();
                $name = $ctptData ? ($ctptData->name ? $ctptData->id . ' - ' . $ctptData->name : $ctptData->id) : '-';
                return $name;
            })
            
            ->make(true);
    }

    public function storeData($request)
    {

        if (CtptUsers::where('toilet_id', $request->toilet_id)
        ->where('date', $request->date)
        ->where('deleted_at', null)
        ->exists()) {
            return redirect('fsm/ctpt-users')
            ->with('error', 'The record of toilet' . $request->toilet_id . ' for the year ' . $request->date . 'already exists!!');
        } else {
            $info = new CtptUsers();
            $info->no_male_user = $request->no_male_user ? $request->no_male_user : null;
            $info->no_female_user = $request->no_female_user ? $request->no_female_user : null;
            $info->date = $request->date ? $request->date : null;
            $info->toilet_id = $request->toilet_id ? $request->toilet_id : null;
            $info->save();

            return redirect('fsm/ctpt-users')->with('success', 'PT Users Log Added Successfully');
        }
    }

    public function updateData($request, $id)
    {
        $info = CtptUsers::find($id);
        if ($info) {
            $info->no_male_user = $request->no_male_user ? $request->no_male_user : null;
            $info->no_female_user = $request->no_female_user ? $request->no_female_user : null;
            $info->date = $request->date ? $request->date : null;
            $info->save();
            return redirect('fsm/ctpt-users')->with('success', 'PT Users Log Updated successfully');
        } else {
            return redirect('fsm/ctpt-users')->with('error', 'Failed to update info');
        }
    }

    public function exportData()
    {

        $toilet_name = $_GET['toilet_id'] ?? null;
        $date = $_GET['date'] ?? null;
        $columns = ['ID','Toilet Name','Date','No. of Male Users (daily)', 'No. of Female Users (daily)'];
        $query = DB::table('fsm.ctpt_users as ctpt')
            ->leftJoin('fsm.toilets as t', 'ctpt.toilet_id', '=', 't.id')
            ->select('ctpt.id as id',
            't.name as name',
            'ctpt.toilet_id',
             'ctpt.date as date',
             'ctpt.no_male_user as no_male_user','ctpt.no_female_user as no_female_user')
           ->orderBy('ctpt.id')->whereNull('ctpt.deleted_at');

        if (!empty($toilet_name)) {
             $query->whereRaw("concat(ctpt.toilet_id, '-', name) ILIKE ?", ['%' . $toilet_name . '%']);
        }
        if (!empty($date)) {
            $query->where('date', $date);
        }
        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(13)
            ->setBackgroundColor(Color::rgb(228, 228, 228))
            ->build();

        $writer = WriterFactory::create(Type::CSV);
        $writer->openToBrowser('PT Users Log.csv')
            ->addRowWithStyle($columns, $style); //Top row of excel
            $query->chunk(5000, function ($ctpt_users) use ($writer) {

                foreach($ctpt_users as $ctpt_user) {
                    $values = [];
                    $values[] = $ctpt_user->id;
                    $values[] = $ctpt_user->name ? $ctpt_user->toilet_id . ' - ' . $ctpt_user->name : '-';
                    $values[] = $ctpt_user->date;
                    $values[] = $ctpt_user->no_male_user;
                    $values[] = $ctpt_user->no_female_user;
                                    
        
                    $writer->addRow($values);
                }
        
            });
        $writer->close();
    }
}
