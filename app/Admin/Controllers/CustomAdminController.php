<?php

namespace App\Admin\Controllers;

use App\Models\CustomUser;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Illuminate\Support\Facades\DB;

class CustomAdminController extends AdminController
{
    use API;
    /**
     * {@inheritdoc}
     */
    protected function title()
    {
        return trans('admin.administrator');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $userModel = config('admin.database.users_model');

        $grid = new Grid(new $userModel());

        // $grid->column('id', 'ID')->sortable();
        $grid->column('username', trans('Tài khoản đăng nhập'));
        $grid->column('name', trans('Họ tên'));
        $grid->column('roles', trans('Bộ phận'))->pluck('name')->label();
        // $grid->column('permissions', trans('Tổ'))->pluck('name')->label();

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            if ($actions->getKey() == 1) {
                $actions->disableDelete();
            }
        });

        $grid->tools(function (Grid\Tools $tools) {
            $tools->batch(function (Grid\Tools\BatchActions $actions) {
                $actions->disableDelete();
            });
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        $userModel = config('admin.database.users_model');

        $show = new Show($userModel::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('username', trans('admin.username'));
        $show->field('name', trans('admin.name'));
        $show->field('roles', trans('admin.roles'))->as(function ($roles) {
            return $roles->pluck('name');
        })->label();
        // $show->field('permissions', trans('admin.permissions'))->as(function ($permission) {
        //     return $permission->pluck('name');
        // })->label();
        $show->field('created_at', trans('admin.created_at'));
        $show->field('updated_at', trans('admin.updated_at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    public function form()
    {
        $userModel = config('admin.database.users_model');
        $permissionModel = config('admin.database.permissions_model');
        $roleModel = config('admin.database.roles_model');

        $form = new Form(new $userModel());

        $userTable = config('admin.database.users_table');
        $connection = config('admin.database.connection');

        $form->display('id', 'ID');
        $form->text('username', trans('admin.username'))
            ->creationRules(['required', "unique:{$connection}.{$userTable}"])
            ->updateRules(['required', "unique:{$connection}.{$userTable},username,{{id}}"]);

        $form->text('name', trans('admin.name'))->rules('required');
        $form->image('avatar', trans('admin.avatar'));
        $form->password('password', trans('admin.password'))->rules('required|confirmed');
        $form->password('password_confirmation', trans('admin.password_confirmation'))->rules('required')
            ->default(function ($form) {
                return $form->model()->password;
            });

        $form->ignore(['password_confirmation']);

        $form->multipleSelect('roles', trans('admin.roles'))->options($roleModel::all()->pluck('name', 'id'));
        // $form->multipleSelect('permissions', trans('admin.permissions'))->options($permissionModel::all()->pluck('name', 'id'));

        $form->display('created_at', trans('admin.created_at'));
        $form->display('updated_at', trans('admin.updated_at'));

        $form->saving(function (Form $form) {
            if ($form->password && $form->model()->password != $form->password) {
                $form->password = Hash::make($form->password);
            }
        });

        return $form;
    }

    public function getUsers(Request $request)
    {
        $query = CustomUser::with('roles');
        if(!isset($request->all_user)){
            $query->whereNull('deleted_at');
        }
        if (isset($request->name)) {
            $query->where('name', 'like', "%$request->name%");
        }
        if (isset($request->username)) {
            $query->where('username', 'like', "%$request->username%");
        }
        $users = $query->get();
        foreach ($users as $key => $user) {
            $user->usage_time = round($user->usage_time_in_day / 60);
        }
        return $this->success($users);
    }
    public function getUserRoles(Request $request)
    {
        $roles = config('admin.database.roles_model')::select('name as label', 'id as value')->get();
        return $this->success($roles);
    }
    public function updateUsers(Request $request)
    {
        $input = $request->all();
        $user = CustomUser::where('id', $input['id'])->first();
        if ($user) {
            $validated = CustomUser::validateUpdate($input);
            if ($validated->fails()) {
                return $this->failure('', $validated->errors()->first());
            }
            $update = $user->update($input);
            if ($update) {
                $user_roles = DB::table('admin_role_users')->where('user_id', $user->id)->delete();
                foreach ($input['roles'] as $role) {
                    DB::table('admin_role_users')->insert(['role_id' => $role, 'user_id' => $user->id]);
                }
                return $this->success($user);
            } else {
                return $this->failure('', 'Không thành công');
            }
        } else {
            return $this->failure('', 'Không tìm thấy tài khoản');
        }
    }

    public function createUsers(Request $request)
    {
        $input = $request->all();
        $input['password'] = Hash::make('123456');
        $user = CustomUser::create($input);
        foreach ($input['roles'] ?? [] as $role) {
            DB::table('admin_role_users')->insert(['role_id' => $role, 'user_id' => $user->id]);
        }
        return $this->success($user, 'Tạo thành công');
    }

    public function deleteUsers(Request $request)
    {
        $input = $request->all();
        CustomUser::whereIn('id', $input)->update(['deleted_at'=>now()]);
        return $this->success('Xoá thành công');
    }

    public function exportUsers(Request $request)
    {
        $query = CustomUser::with('roles')->whereNull('deleted_at');
        if (isset($request->name)) {
            $query->where('name', 'like', "%$request->name%");
        }
        $users = $query->get();
        foreach ($users as $user) {
            $bo_phan = [];
            foreach ($user->roles as $role) {
                $bo_phan[] = $role->name;
            }
            $user->bo_phan = implode(", ", $bo_phan);
        }
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $start_row = 2;
        $start_col = 1;
        $centerStyle = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => array(
                'outline' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $headerStyle = array_merge($centerStyle, [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => 'BFBFBF')
            ]
        ]);
        $titleStyle = array_merge($centerStyle, [
            'font' => ['size' => 16, 'bold' => true],
        ]);
        $border = [
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $header = ['Username', 'Tên', 'Bộ phận'];
        $table_key = [
            'A' => 'username',
            'B' => 'name',
            'C' => 'bo_phan',
        ];
        foreach ($header as $key => $cell) {
            if (!is_array($cell)) {
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
            }
            $start_col += 1;
        }

        $sheet->setCellValue([1, 1], 'Quản lý tài khoản')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row + 1;
        foreach ($users->toArray() as $key => $row) {
            $table_col = 1;
            $row = (array)$row;
            foreach ($table_key as $k => $value) {
                if (isset($row[$value])) {
                    $sheet->setCellValue($k . $table_row, $row[$value])->getStyle($k . $table_row)->applyFromArray($centerStyle);
                } else {
                    continue;
                }
                $table_col += 1;
            }
            $table_row += 1;
        }
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            $sheet->getStyle($column->getColumnIndex() . ($start_row) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
        }
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Danh sách tài khoản.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Danh sách tài khoản.xlsx');
        $href = '/exported_files/Danh sách tài khoản.xlsx';
        return $this->success($href);
    }

    public function importUsers(Request $request)
    {
        $extension = pathinfo($_FILES['files']['name'], PATHINFO_EXTENSION);
        if ($extension == 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        } elseif ($extension == 'xlsx') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        }
        // file path
        $spreadsheet = $reader->load($_FILES['files']['tmp_name']);
        $allDataInSheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $data = [];
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 4
            if ($key > 2) {
                $input = [];
                $user_name = "";
                foreach (preg_split("/\s+/", $row['C']) as $w) {
                    $user_name .= strtolower(mb_substr($w, 0, 1));
                }
                $input['username'] = $row['A'];
                $input['name'] = $row['B'];
                $input['bo_phan'] = $row['C'];
                // if(!$input['username'] &&  $input['name'] && $input['bo_phan']){
                //     break;
                // }
                $validated = CustomUser::validateUpdate($input);
                if ($validated->fails()) {
                    return $this->failure('', 'Lỗi dòng thứ ' . ($key) . ': ' . $validated->errors()->first());
                }
                $data[] = $input;
            }
        }
        foreach ($data as $key => $input) {
            $user = CustomUser::where('username', $input['username'])->first();
            if ($user) {
                $user->update($input);
                DB::table('admin_role_users')->where('user_id', $user->id)->delete();
                foreach (explode(',', $input['bo_phan']) as $bo_phan) {
                    $role = DB::table('admin_roles')->where('name', trim($bo_phan))->first();
                    if ($role) {
                        $exists = DB::table('admin_role_users')
                            ->where('role_id', $role->id)
                            ->where('user_id', $user->id)
                            ->exists();

                        if (!$exists) {
                            DB::table('admin_role_users')->insert([
                                'role_id' => $role->id,
                                'user_id' => $user->id,
                            ]);
                        }
                    }
                }
            } else {
                $input['password'] = Hash::make('123456');
                $user = CustomUser::create($input);
                foreach (explode(',', $input['bo_phan']) as $bo_phan) {
                    $role = DB::table('admin_roles')->where('name', trim($bo_phan))->first();
                    if ($role) {
                        $exists = DB::table('admin_role_users')
                            ->where('role_id', $role->id)
                            ->where('user_id', $user->id)
                            ->exists();

                        if (!$exists) {
                            DB::table('admin_role_users')->insert([
                                'role_id' => $role->id,
                                'user_id' => $user->id,
                            ]);
                        }
                    }
                }
            }
        }
        return $this->success([], 'Upload thành công');
    }
}
