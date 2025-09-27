<?php

namespace App\DataTables\Admin;

use App\Models\Shopkeeper;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ShopkeepersDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param  QueryBuilder<Shopkeeper>  $query  Results from query() method.
     */
    public function dataTable(QueryBuilder $query, Request $request): EloquentDataTable
    {

        return (new EloquentDataTable($query))
            ->filterColumn('name', function ($query, $keyword) {
                return $query->forName($keyword);
            })
            ->editColumn('name', function ($shopKeeper) {
                $shopkeeprViewUrl = route('admin.shopkeepers', ['user_id' => $shopKeeper->id]);
                $user = User::find($shopKeeper->id);
                if($user && !$user->hasRole('user')){
                    return '<a href="'.$shopkeeprViewUrl.'" class="text-primary">'.$shopKeeper->name.'</a>';
                }
                return $shopKeeper->name;
            })
            ->addColumn('total_draws', function ($user) {
                return $user->drawDetails->count();
            })
            ->editColumn('password_plain', function ($shopKeeper) {
                $password = $shopKeeper->password_plain;

                return "<div class='d-flex justify-content-start'>
                <span class='star-password'>********</span>
                <span class='show-password d-none'>$password</span>
                <i class='fa fa-eye show-password-button ms-2' role='button'></i></div>";
            })
            ->addColumn('action', function ($shopKeeper) {
                $shopkeeprEditUrl = route('admin.shopkeeper_form', ['user_id' => $shopKeeper->id]);
                $shopkeeprViewUrl = route('admin.shopkeeper.view', ['user_id' => $shopKeeper->id]);

                return <<<HTML
                <div class="d-flex justify-content-center">
                <a href="$shopkeeprEditUrl" class="btn btn-warning ms-3 text-white"><i class="fa fa-pencil"></i> Edit</a>
                <a href="$shopkeeprViewUrl" class="btn btn-primary ms-3 text-white"><i class="fa fa-eye"></i> View</a>

                </div>
                HTML;
            })
            ->setRowId('id')
            ->rawColumns(['action', 'total_draws', 'password_plain','name']);
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Shopkeeper>
     */
    public function query(User $model): QueryBuilder
    {

        $user = auth()->user();
        $shopkeeperId = request()->route('user_id');
        // dd($shopkeeperId);
        if($shopkeeperId){
            $user = User::find($shopkeeperId);
        }
        
        if ( $user->hasRole('master')) {
            return $model->newQuery()
                ->role('admin')
                ->with('children')
                ->select('users.*');
        }

        if ($user->hasRole('admin')) {
            return $model->newQuery()
                ->where('created_by', $user->id)
                ->role('shopkeeper')
                ->with('children')
                ->select('users.*');
        }

         if ($user->hasRole('shopkeeper')) {
            return $model->newQuery()
                ->role('user')
                ->where('created_by', $user->id)
                ->select('users.*');
        }

        return $model->newQuery()->whereRaw('1=0');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('shopkeepers-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0)
            ->selectStyleSingle()
            ->buttons([
                Button::make('excel'),
                Button::make('csv'),
                Button::make('pdf'),
                Button::make('print'),

            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            // Column::computed('action')
            //     ->exportable(false)
            //     ->printable(false)
            //     ->width(60)
            //     ->addClass('text-center'),
            Column::make('id')->title('#ID'),
            Column::make('name')->title('Name'),
            Column::make('email'),
            Column::make('password_plain')->title('Password'),

            Column::make('mobile_number'),
            Column::make('total_draws')->title('Draws'),
            // Column::make('created_at'),
            // Column::make('updated_at'),
            Column::make('action')->addClass('text-center'),

        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Shopkeepers_'.date('YmdHis');
    }
}
