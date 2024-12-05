<?php

namespace App\Admin\Controllers;

use App\Helpers\QueryHelper;
use App\Models\CustomUser;
use App\Models\DRC;
use App\Models\GroupPlanOrder;
use App\Models\LocatorMLTMap;
use App\Models\Material;
use App\Models\Order;
use App\Models\Vehicle;
use App\Models\VOCRegister;
use App\Models\VOCType;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VOCRegisterController extends AdminController
{
    use API;

    public function getList(Request $request)
    {
        $query = VOCRegister::query()->with(['type', 'register', 'replier'])->orderByDesc('created_at');

        if (isset($request->no)) {
            $query->where('no', 'like', "%{$request->no}%");
        }
        if (isset($request->requested_by)) {
            $query->whereHas('register', function($q) use($request){
                $q->where('username', $request->requested_by);
            });
        }
        if (isset($request->registered_by)) {
            $query->where('registered_by', $request->registered_by);
        }

        if (isset($request->replied_by)) {
            $query->where('replied_by', $request->replied_by);
        }

        if (isset($request->start_date) && isset($request->end_date)) {
            $query->whereDate('registered_at', '>=', date('Y-m-d', strtotime($request->start_date)));
            $query->whereDate('registered_at', '<=', date('Y-m-d', strtotime($request->end_date)));
        }

        if (isset($request->type)) {
            $query->where('voc_type_id', $request->type);
        }
        if (isset($request->status)) {
            $query->where('status', $request->status);
        }
        if (isset($request->register)) {
            $query->whereHas('register', function($q) use($request){
                $q->where('name', 'like', "%$request->register%");
            });
        }
        $records = $query->get();
        foreach($records as $record){
            $file_names = [];
            foreach(array_filter(explode("|", $record->files)) as $file_name){
                if(File::exists('voc_files/'.$file_name)){
                    $file_names[] = 'voc_files/'.$file_name;
                }
            }
            $record->file_names = $file_names;
        }
        return $this->success($records);
    }

    public function createRecord(Request $request) {
        $request->validate([
            'voc_type_id' => 'required|exists:voc_types,id',
            'title' => 'required',
            'content' => 'required',
            'solution' => 'nullable',
        ]);
        $prefix = date('Ymd');
        $no = QueryHelper::generateNewId(new VOCRegister(), $prefix, 3, 'no');
        $file_names = "";
        if(!empty($request->file_names) && is_array($request->file_names)){
            $file_names = implode("|", array_column($request->file_names, 'name') ?? []);
        }
        $result = VOCRegister::create([
            'no' => $no,
            'voc_type_id' => $request->voc_type_id,
            'title' => $request->title,
            'content' => $request->content,
            'solution' => $request->solution ?? null,
            'registered_by' => auth()->user()->id,
            'registered_at' => date('Y-m-d H:i:s'),
            'reply' => null,
            'replied_by' => null,
            'replied_at' => null,
            'status' => VOCRegister::STATUS_PENDING,
            'expected_date' => $request->expected_date ? date("Y-m-d H:i:s", strtotime($request->expected_date)) : null,
            'files' => $file_names,
        ]);
        $this->clearUnusedFiles();
        return $this->success($result, 'Thao tác thành công', 200);
    }

    public function updateRecord(Request $request, $id) {
        $request->validate([
            'reply' => 'required',
        ]);

        $record = VOCRegister::find($id);
        if (empty($record)) return $this->failure([], 'Không tìm thấy dữ liệu', 404);

        $record->update([
            'reply' => $request->reply,
            'replied_by' => auth()->user()->id,
            'replied_at' => date('Y-m-d H:i:s'),
            'status' => VOCRegister::STATUS_REPLIED,
        ]);
        
        return $this->success($record, 'Thao tác thành công', 200);
    }

    public function deleteRecord($id) {
        $record = VOCRegister::find($id);
        if (empty($record)) return $this->failure([], 'Không tìm thấy dữ liệu', 404);

        $record->delete();
        $this->clearUnusedFiles();
        return $this->success([], 'Thao tác thành công', 200);
    }

    public function uploadFile(Request $request){
        // Kiểm tra file được upload
        $request->validate([
            'files' => 'required|file|max:10240',
        ]);

        try {
            if ($request->hasFile('files')) {
                // Lấy file từ request
                $file = $request->file('files');
                
                // Tạo tên file mới
                $filename = time() . '_' . $file->getClientOriginalName();
    
                // Đường dẫn lưu file
                $destinationPath = public_path('voc_files');
    
                // Lưu file vào thư mục public/files
                $file->move($destinationPath, $filename);
    
                return$this->success('voc_files/'.$filename);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
        // Lưu file vào thư mục storage/app/public/images
        
        return $this->success('');
    }

    function clearUnusedFiles(){
        $voc = VOCRegister::all();
        $linkedFiles = [];
        foreach($voc as $record){
            $remainFile = [];
            $files = array_filter(explode("|", $record->files ?? ""));
            foreach ($files as $key => $file) {
                if(File::exists('voc_files/'.$file)){
                    $remainFile[] = $file;
                    $linkedFiles[] = $file;
                }
            }
            $record->update(['files'=>implode("|", $remainFile)]);
        }
        // Thư mục chứa file
        $directory = public_path('voc_files');

        // Kiểm tra thư mục có tồn tại không
        if (!File::exists($directory)) {
            return $this->failure('Thư mục không tồn tại: ' . $directory);
        }

        // Lấy danh sách tất cả các file trong thư mục
        $filesInDirectory = File::files($directory);

        $deletedCount = 0;

        // Duyệt qua từng file và xóa file không liên kết
        foreach ($filesInDirectory as $file) {
            $fileName = $file->getFilename();
            if (!in_array($fileName, $linkedFiles)) {
                File::delete($file);
                $deletedCount++;
            }
        }
        return $this->success('', 'Đã xoá');
    }
}
