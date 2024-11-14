<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineParameter extends Model
{
    use HasFactory;

    protected $fillable=['machine_id', 'name', 'parameter_id', 'is_if'];
    public function machine(){
        return $this->belongsTo(Machine::class, 'machine_id');
    }
    const PARAMETER_ARRAY = [
        'So01' => [
            'Roll_CounterE'=>'Số m đã chạy cụm lô E',
            'Roll_CounterB'=>'Số m đã chạy cụm lô B',
            'Roll_CounterC'=>'Số m đã chạy cụm lô C',
            'Machine_Status'=>'Trạng thái máy',
            'Roll1_Alarm'=>'Cảnh báo hết nguyên liệu lô cuốn 1',
            'Roll2_Alarm'=>'Cảnh báo hết nguyên liệu lô cuốn 2',
            'Roll3_Alarm'=>'Cảnh báo hết nguyên liệu lô cuốn 3',
            'Roll4_Alarm'=>'Cảnh báo hết nguyên liệu lô cuốn 4',
            'Roll5_Alarm'=>'Cảnh báo hết nguyên liệu lô cuốn 5',
            'Roll6_Alarm'=>'Cảnh báo hết nguyên liệu lô cuốn 6',
            'Roll7_Alarm'=>'Cảnh báo hết nguyên liệu lô cuốn 7',
            'Roll_SpeedE'=>'Tốc độ cụm lô cuốn E',
            'Roll_SpeedB'=>'Tốc độ cụm lô cuốn B',
            'Roll_SpeedC'=>'Tốc độ cụm lô cuốn C',
            'Rem_Counter'=>'Số m còn lại của đơn hàng',
            'SlitLot'=>'Khe hở lô hồ',
            'PresLotE'=>'Áp ép lô láng E',
            'PresWaveE'=>'Áp ép lô sóng E',
            'TempLotE'=>'Nhiệt độ E',
            'PresLotB'=>'Áp ép lô láng B',
            'PresWaveB'=>'Áp ép lô sóng B',
            'TempLotB'=>'Nhiệt độ B',
            'PresLotC'=>'Áp ép lô láng C',
            'PresWaveC'=>'Áp ép lô sóng C',
            'TempLotC'=>'Nhiệt độ C',
            'Length_Cut'=>'Chiều dài chặt',
            'In_Counter'=>'Tổng sản phẩm đầu vào (m)',
            'Err_Counter'=>'Tổng sản phẩm lỗi',
            'Pre_Counter'=>'Tổng sản phẩm đầu ra (tấm hoặc m)',
            'RST_Counter'=>'Reset bộ đếm',
            'PLC_Connect'=>'Tình trạng kết nối của PLC'
        ],
        'P15' => [
            'Machine_Speed'=>'Tốc độ máy',
            'Machine_Status'=>'Trạng thái máy',
            'Film1_Angle'=>'Góc chỉnh film khối 1',
            'Film2_Angle'=>'Góc chỉnh film khối 2',
            'Film3_Angle'=>'Góc chỉnh film khối 3',
            'Film4_Angle'=>'Góc chỉnh film khối 4',
            'Film5_Angle'=>'Góc chỉnh film khối 5',
            'Lot_Angle'=>'GÓc chỉnh lô bế (1 lô)',
            'Error_Code'=>'Lỗi máy',
            'Pre_Counter'=>'Tổng sản phẩm đầu vào',
            'Out_Counter'=>'Tổng sản phẩm đầu ra',
            'RST_Counter'=>'Reset bộ đếm',
            'PLC_Connect'=>'Tình trạng kết nối của PLC'
        ],
        'P06' => [
            'Machine_Speed'=>'Tốc độ máy',
            'Machine_Status'=>'Trạng thái máy',
            'Film1_Angle'=>'Góc chỉnh film khối 1',
            'Film2_Angle'=>'Góc chỉnh film khối 2',
            'Film3_Angle'=>'Góc chỉnh film khối 3',
            'Film4_Angle'=>'Góc chỉnh film khối 4',
            'Film5_Angle'=>'Góc chỉnh film khối 5',
            'Lot_Angle'=>'GÓc chỉnh lô bế (1 lô)',
            'Error_Code'=>'Lỗi máy',
            'Pre_Counter'=>'Tổng sản phẩm đầu vào',
            'Out_Counter'=>'Tổng sản phẩm đầu ra',
            'RST_Counter'=>'Reset bộ đếm',
            'PLC_Connect'=>'Tình trạng kết nối của PLC'
        ],
        'D06' => [
            'Machine_Speed'=>'Tốc độ máy',
            'Machine_Status'=>'Trạng thái máy',
            'Pre_Counter'=>'Tổng sản phẩm đầu vào',
            'Out_Counter'=>'Tổng sản phẩm đầu ra',
            'RST_Counter'=>'Reset bộ đếm',
            'PLC_Connect'=>'Tình trạng kết nối của PLC'
        ],
        'D05' => [
            'Machine_Speed'=>'Tốc độ máy',
            'Machine_Status'=>'Trạng thái máy',
            'Pre_Counter'=>'Tổng sản phẩm đầu vào',
            'Out_Counter'=>'Tổng sản phẩm đầu ra',
            'RST_Counter'=>'Reset bộ đếm',
            'PLC_Connect'=>'Tình trạng kết nối của PLC'
        ]
    ];
}
