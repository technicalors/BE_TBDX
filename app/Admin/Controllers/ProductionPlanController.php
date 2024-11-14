<?php

namespace App\Admin\Controllers;

use App\Models\Line;
use App\Models\ProductionPlan;
use Carbon\Carbon;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProductionPlanController extends AdminController
{



    public function upload_xlsx($action, $title)
    {
        return view('import', [
            "action" => $action,
            "title" => $title
        ]);
    }



    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Kế hoạch sản xuất';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ProductionPlan());
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
        });

        $grid->tools(function ($tool) {
            $tool->append($this->upload_xlsx('/admin/production_plan/import', 'Chọn file kế hoạch sản xuất'));
        });

        // $grid->column('id', __('Id'));
        $grid->column('ngay_dat_hang', __('Ngày đặt hàng'))->display(function ($item) {
            return date('d/m/Y', strtotime($item));
        });
        $grid->column('cong_doan_sx', __('Công đoạn'));
        $grid->column('ca_sx', __('Ca SX'));
        $grid->column('ngay_sx', __('Ngày SX'))->display(function ($item) {
            return date('d/m/Y', strtotime($item));
        });;
        $grid->column('ngay_giao_hang', __('Ngày giao hàng'))->display(function ($item) {
            return date('d/m/Y', strtotime($item));
        });;
        $grid->column('machine_id', __('Máy SX'));
        $grid->column('product_id', __('Tên SP'));
        $grid->column('khach_hang', __('Khách hàng'));
        $grid->column('lo_sx', __('Lô SX'));
        $grid->column('so_bat', __('Số bát'));
        $grid->column('sl_nvl', __('Số lượng NVL'));
        $grid->column('sl_thanh_pham', __('Số lượng thành phẩm'));
        $grid->column('thu_tu_uu_tien', __('Thứ tự ưu tiên'));
        $grid->column('note', __('Ghi chú'));
        $grid->column('UPH', __('UPH'));
        $grid->column('nhan_luc', __('Nhân lực'));
        $grid->column('tong_tg_thuc_hien', __('Tổng t/g thực hiện'));
        $grid->column('thoi_gian_bat_dau', __('Thời gian bắt đầu'));
        $grid->column('status', __('Trạng thái'))->display(function ($item) {

            if ($item) {
                return "<span class='label label-success'>Xác nhận</span>";
            }
            return "<span class='label label-warning'>Dự định</span>";
        });



        // $grid->column('created_at', __('Created at'));
        // $grid->column('updated_at', __('Updated at'));


        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(ProductionPlan::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('ngay_dat_hang', __('Ngay dat hang'));
        $show->field('ca_sx', __('Ca sx'));
        $show->field('ngay_sx', __('Ngay sx'));
        $show->field('ngay_giao_hang', __('Ngay giao hang'));
        $show->field('machine_id', __('Machine id'));
        $show->field('product_id', __('Product id'));
        $show->field('khach_hang', __('Khach hang'));
        $show->field('lo_sx', __('Lo sx'));
        $show->field('so_bat', __('So bat'));
        $show->field('sl_nvl', __('Sl nvl'));
        $show->field('sl_thanh_pham', __('Sl thanh pham'));
        $show->field('thu_tu_uu_tien', __('Thu tu uu tien'));
        $show->field('note', __('Note'));
        $show->field('UPH', __('UPH'));
        $show->field('nhan_luc', __('Nhan luc'));
        $show->field('tong_tg_thuc_hien', __('Tong tg thuc hien'));
        $show->field('thoi_gian_bat_dau', __('Thoi gian bat dau'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new ProductionPlan());

        $form->datetime('ngay_dat_hang', __('Ngay dat hang'))->default(date('Y-m-d H:i:s'));
        $form->number('ca_sx', __('Ca sx'));
        $form->datetime('ngay_sx', __('Ngay sx'))->default(date('Y-m-d H:i:s'));
        $form->datetime('ngay_giao_hang', __('Ngay giao hang'))->default(date('Y-m-d H:i:s'));
        $form->text('machine_id', __('Machine id'));
        $form->text('product_id', __('Product id'));
        $form->text('khach_hang', __('Khach hang'));
        $form->text('lo_sx', __('Lo sx'));
        $form->number('so_bat', __('So bat'));
        $form->number('sl_nvl', __('Sl nvl'));
        $form->number('sl_thanh_pham', __('Sl thanh pham'));
        $form->text('thu_tu_uu_tien', __('Thu tu uu tien'));
        $form->text('note', __('Note'));
        $form->text('UPH', __('UPH'));
        $form->number('nhan_luc', __('Nhan luc'));
        $form->number('tong_tg_thuc_hien', __('Tong tg thuc hien'));
        $form->number('thoi_gian_bat_dau', __('Thoi gian bat dau'));

        return $form;
    }


    private function parseTime($time)
    {
        try {
            $arr = explode('/', $time);
            $str = $arr[1] . '/' . $arr[0] . '/' . $arr[2];
            return new Carbon($str);
        } catch (Exception $ex) {
            return $time;
        }
    }

    public function import($flag = false)
    {
        if (!isset($_FILES['files'])) { {
                admin_error('Định dạng file không đúng', 'error');
                return back();
            }
        }

        $hash = hash_file("md5", $_FILES['files']['tmp_name']);

        $lists = ProductionPlan::where("file", $hash);
        $lists->delete();

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
        $index=1;
        foreach ($allDataInSheet as $row) {
            $index++;
            if ($index >3)  {

                $plan = new  ProductionPlan();
                $plan->ngay_dat_hang = $row['R'];
                $plan->cong_doan_sx = Str::slug( $row['F']); //
                $plan->ca_sx = $row['K'];//
                $plan->ngay_sx = date('Y-m-d', strtotime(str_replace('/', '-', $row['E'])));
                // $plan->ngay_sx =new Carbon($row['E']);
                $plan->ngay_giao_hang = $row['S'];//
                $plan->machine_id = $row['G'];//
                $plan->product_id = str_replace( " ","",$row['H']);//
                $plan->khach_hang = $row['J'];//
                $plan->lo_sx = $row['L'];//
                $plan->so_bat = $row['M'];//
                $plan->sl_nvl = $row['N'];//
                $plan->sl_thanh_pham = $row['O'];//
                $plan->thu_tu_uu_tien = $row['B']; //
                $plan->note = $row['Q']??"";
                $plan->UPH = str_replace(',', '', $row['X']);//
                $plan->nhan_luc = $row['Y'];
                $plan->tong_tg_thuc_hien = filter_var($row['W'], FILTER_SANITIZE_NUMBER_INT);  ; //
                // $plan->thoi_gian_bat_dau = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $row['C'])));
                $plan->thoi_gian_bat_dau = date('Y-m-d H:i:s',strtotime($plan->ngay_sx.' '.$row['C']));

                $plan->thoi_gian_ket_thuc = date('Y-m-d H:i:s',strtotime($plan->ngay_sx.' '.$row['D'].(strtotime($row['C'])>strtotime($row['D']) ? " +1 day" : "")));
                $plan->status = 0;
                $plan->file = $hash;
                // dd($plan);
                try{
                    $plan->save();
                }catch( Exception $ex){

                }
                
            }
        }
        if ($flag) return true;
        admin_success('Tải lên thành công', 'success');
        return back();
    }
}
