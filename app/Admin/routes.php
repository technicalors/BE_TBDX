<?php

use App\Admin\Controllers\ApiMobileController;
use App\Admin\Controllers\ApiUIController;
use App\Admin\Controllers\ApiController;
use App\Admin\Controllers\CustomAdminController;
use App\Admin\Controllers\DeliveryNoteController;
use App\Admin\Controllers\DepartmentController;
use App\Admin\Controllers\InfoCongDoanController;
use App\Admin\Controllers\KPIController;
use App\Admin\Controllers\LSXPalletController;
use App\Admin\Controllers\MESUsageRateController;
use App\Admin\Controllers\OrderController;
use App\Admin\Controllers\RoleController;
use App\Admin\Controllers\ShiftAssignmentController;
use App\Admin\Controllers\ShiftController;
use App\Admin\Controllers\UserLineMachineController;
use App\Admin\Controllers\VOCRegisterController;
use App\Admin\Controllers\VOCTypeController;
use App\Admin\Controllers\ChatController;
use App\Admin\Controllers\SupplierController;
use App\Models\Attachment;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Admin::routes();


//API
Broadcast::routes(['middleware' => ['auth:sanctum']]);
// UI-API
Route::group([
    'prefix'        => "/api",
    'middleware'    => [],
    'as'            => "/api" . '.',
], function (Router $router) {
    $router->get('/warehouse/convert-import', [ApiMobileController::class, 'converImportWarehouseFG']);

    $router->get('/produce/history', [ApiUIController::class, 'produceHistory']);
    $router->get('/produce/fmb', [ApiUIController::class, 'fmb']);
    $router->get('/qc/history', [ApiUIController::class, 'qcHistory']);
    $router->get('/qc/detail-data-error', [ApiUIController::class, 'getDetailDataError']);

    $router->get('/machine/error', [ApiUIController::class, 'machineError']);

    $router->get('/warning/alert', [ApiUIController::class, 'getAlert']);
    $router->get('/machine/perfomance', [ApiUIController::class, 'apimachinePerfomance']);

    $router->get('/kpi', [ApiUIController::class, 'apiKPI']);

    $router->get('/oqc', [ApiUIController::class, 'oqc']);

    $router->get('/dashboard/monitor', [ApiMobileController::class, 'dashboardMonitor']);
    $router->post('/dashboard/insert-monitor', [ApiMobileController::class, 'insertMonitor']);
    $router->get('/dashboard/get-monitor', [ApiMobileController::class, 'getMonitor']);

    $router->get('/inventory', [ApiUIController::class, 'inventory']);
});

// END UI-API;

Route::group([
    'prefix'        => "/api",
    'middleware'    => "auth:sanctum",
    'as'            => "mobile/api" . '.',
], function (Router $router) {
    // USER
    $router->get('/user/info', [ApiMobileController::class, 'userInfo']);
    $router->get('/user/logout', [ApiMobileController::class, 'logout']);
    $router->post('/user/password/update', [ApiMobileController::class, 'userChangePassword']);


    // LINE
    $router->get('/line/list', [ApiMobileController::class, 'listLine']);
    $router->get('/line/list-machine', [ApiMobileController::class, 'listMachineOfLine']);

    $router->get('/scenario/list', [ApiMobileController::class, 'listScenario']);
    $router->post('/scenario/update', [ApiMobileController::class, 'updateScenario']);
    //
    $router->get('/warehouse/propose-import', [ApiMobileController::class, 'getProposeImport']);
    $router->post('/warehouse/import', [ApiMobileController::class, 'importWareHouse']);
    $router->get('/warehouse/list-import', [ApiMobileController::class, 'listImportWareHouse']);
    $router->get('/warehouse/info-import', [ApiMobileController::class, 'infoImportWareHouse']);
    $router->get('/warehouse/list-customer', [ApiMobileController::class, 'listCustomerExport']);
    $router->get('/warehouse/propose-export', [ApiMobileController::class, 'getProposeExport']);
    $router->post('/warehouse/export', [ApiMobileController::class, 'exportWareHouse']);
    $router->get('/warehouse/info-export', [ApiMobileController::class, 'infoExportWareHouse']);
    $router->get('/material/list-log', [ApiMobileController::class, 'listLogMaterial']);
    $router->post('/material/update-log', [ApiMobileController::class, 'updateLogMaterial']);
    $router->post('/material/update-log-record', [ApiMobileController::class, 'updateLogMaterialRecord']);
    $router->post('/material/store-log', [ApiMobileController::class, 'storeLogMaterial']);
    $router->get('/material/list-lsx', [ApiMobileController::class, 'listLsxUseMaterial']);
    $router->post('/barrel/split', [ApiMobileController::class, 'splitBarrel']);
    $router->get('/warehouse/history', [ApiMobileController::class, 'getHistoryWareHouse']);
    $router->delete('/warehouse-export/destroy', [ApiMobileController::class, 'destroyWareHouseExport']);
    $router->post('/warehouse-export/update', [ApiMobileController::class, 'updateWareHouseExport']);
    $router->post('/warehouse-export/create', [ApiMobileController::class, 'createWareHouseExport']);
    $router->get('/warehouse-export/get-thung', [ApiMobileController::class, 'prepareGT']);
    $router->post('/warehouse-export/gop-thung', [ApiMobileController::class, 'gopThungIntem']);

    //PLAN PRODUCTION

    $router->get('/plan/detail', [ApiMobileController::class, 'planDetail']);
    $router->get('/plan/list/machine', [ApiMobileController::class, 'planMachineDetail']);
    $router->get('/plan/lsx/list', [ApiMobileController::class, 'lsxList']);
    $router->get('/plan/lsx/detail', [ApiMobileController::class, 'lsxDetail']);
    $router->post('/plan/lsx/update', [ApiMobileController::class, 'lsxUpdate']);
    $router->get('/plan/lsx/log', [ApiMobileController::class, 'lsxLog']);
    $router->delete('product_plan/destroy', [ApiMobileController::class, 'destroyProductPlan']);
    $router->post('product_plan/store', [ApiMobileController::class, 'storeProductPlan']);
    $router->post('product_plan/update', [ApiMobileController::class, 'updateProductPlan']);
    $router->post('/plan/lsx/test', [ApiMobileController::class, 'lsxTest']);


    //Machine
    $router->get('/machine/detail', [ApiMobileController::class, 'detailMachine']);

    //Warehouse
    $router->get('/warehouse/product/detail', [ApiMobileController::class, 'productDetail']);
    $router->get('/warehouse/detail', [ApiMobileController::class, 'warehouseDetail']);
    $router->get('/warehouse/log', [ApiMobileController::class, 'warehouseLog']);
    $router->post('/warehouse/cell_product/update', [ApiMobileController::class, 'cellProductUpdate']);
    $router->get('/warehouse/material', [ApiMobileController::class, 'material']);
    $router->get('/warehouse/list', [ApiMobileController::class, 'warehouseList']);
    $router->get('/warehouse/cell/empty', [ApiMobileController::class, 'cellEmpty']);



    // Meterial

    $router->get('/material/list', [ApiMobileController::class, 'materialList']);
    $router->get('/material/detail', [ApiMobileController::class, 'materialDetail']);
    $router->post('/material/create', [ApiMobileController::class, 'materialCreate']);
    $router->get('/material/log', [ApiMobileController::class, 'materialLog']);


    //Color
    $router->get('/color/list', [ApiMobileController::class, 'colorList']);


    //Unusual
    $router->get('/machine/log', [ApiMobileController::class, 'machineLog']);
    $router->get('/reason/list', [ApiMobileController::class, 'reasonList']);
    $router->post('/machine/log/update', [ApiMobileController::class, 'machineLogUpdate']);
    $router->get('/machine/reason/list', [ApiMobileController::class, 'machineReasonList']);


    //UIUX

    $router->get('/ui/plan', [ApiMobileController::class, 'uiPlan']);
    // ui-MAIN
    $router->get('/ui/lines', [ApiMobileController::class, 'ui_getLines']);
    $router->get('/ui/line/list-machine', [ApiMobileController::class, 'ui_getLineListMachine']);
    $router->get('/ui/machines', [ApiMobileController::class, 'ui_getMachines']);
    $router->get('/ui/products', [ApiMobileController::class, 'ui_getProducts']);
    $router->get('/ui/staffs', [ApiMobileController::class, 'ui_getStaffs']);
    $router->get('/ui/lo-san-xuat', [ApiMobileController::class, 'ui_getLoSanXuat']);
    $router->get('/ui/warehouses', [ApiMobileController::class, 'ui_getWarehouses']);
    $router->get('/ui/ca-san-xuat-s', [ApiMobileController::class, 'ui_getCaSanXuats']);
    $router->get('/ui/errors', [ApiMobileController::class, 'ui_getErrors']);
    $router->get('/ui/errors-machine', [ApiMobileController::class, 'ui_getErrorsMachine']);

    $router->get('/ui/thong-so-may', [ApiMobileController::class, 'uiThongSoMay']);


    // Test Criteria
    $router->get('/testcriteria/list', [ApiMobileController::class, 'testCriteriaList']);
    $router->post('/testcriteria/result', [ApiMobileController::class, 'testCriteriaResult']);
    $router->get('/error/list', [ApiMobileController::class, 'errorList']);
    $router->get('/testcriteria/lsx/choose', [ApiMobileController::class, 'testCriteriaChooseLSX']);
    $router->get('/testcriteria/history', [ApiMobileController::class, 'testCriteriaHistory']);
    $router->get('/machine/info', [ApiMobileController::class, 'getInfoMachine']);


    $router->get('ui/manufacturing', [ApiMobileController::class, 'uiManufacturing']);
    $router->get('ui/quality', [ApiMobileController::class, 'uiQuality']);


    //MATERIAL

    //LOT /PALLET

    $router->get('lot/list', [ApiMobileController::class, 'palletList']);
    $router->delete('pallet/destroy', [ApiMobileController::class, 'destroyPallet']);
    $router->post('lot/update-san-luong', [ApiMobileController::class, 'updateSanLuong']);
    $router->get('lot/check-san-luong', [ApiMobileController::class, 'checkSanLuong']);
    $router->post('lot/bat-dau-tinh-dan-luong', [ApiMobileController::class, 'batDauTinhSanLuong']);
    $router->get('lot/detail', [ApiMobileController::class, 'detailLot']);

    // Production-Process

    $router->post('lot/scanPallet', [ApiMobileController::class, 'scanPallet']);

    $router->post('lot/input', [ApiMobileController::class, 'inputPallet']);
    $router->get('line/overall', [ApiMobileController::class, 'lineOverall']);
    $router->get('line/user', [ApiMobileController::class, 'lineUser']);
    $router->post('line/assign', [ApiMobileController::class, 'lineAssign']);
    $router->get('line/table/list', [ApiMobileController::class, 'listTable']);
    $router->post('line/table/work', [ApiMobileController::class, 'lineTableWork']);

    $router->post('lot/intem', [ApiMobileController::class, 'inTem']);



    //QC
    $router->post('qc/scanPallet', [ApiMobileController::class, 'scanPalletQC']);

    $router->get('qc/test/list', [ApiMobileController::class, 'testList']);
    $router->post('qc/test/result', [ApiMobileController::class, 'resultTest']);
    $router->post('qc/error/result', [ApiMobileController::class, 'errorTest']);
    $router->get('qc/overall', [ApiMobileController::class, 'qcOverall']);

    $router->post('qc/update-temvang', [ApiMobileController::class, 'updateSoLuongTemVang']);
    $router->post('qc/intemvang', [ApiMobileController::class, 'inTemVang']);
    $router->get('qc/pallet/info', [ApiMobileController::class, 'infoQCPallet']);
    $router->get('qc/losx/detail', [ApiMobileController::class, 'detailLoSX']);

    //DASHBOARD


    $router->get('dashboard/giam-sat', [ApiMobileController::class, 'dashboardGiamSat']);
    $router->get('dashboard/giam-sat-chat-luong', [ApiMobileController::class, 'dashboardGiamSatChatLuong']);

    $router->get('dashboard/status', [ApiMobileController::class, 'dashboardKhiNen']);

    $router->get('dashboard/sensor', [ApiMobileController::class, 'dashboardSensor']);

    //Parameters
    $router->get('machine/parameters', [App\Admin\Controllers\ApiMobileController::class, 'getMachineParameters']);
    $router->post('machine/parameters/update', [App\Admin\Controllers\ApiMobileController::class, 'updateMachineParameters']);

    $router->get('lot/table-data-chon', [ApiMobileController::class, 'getTableAssignData']);


    $router->post('machine/machine-log/save', [ApiMobileController::class, 'logsMachine_save']);
    $router->post('update/test', [ApiMobileController::class, 'updateWarehouseEportPlan']);

    //Monitor 
    $router->get('/monitor/history', [ApiMobileController::class, 'historyMonitor']);

    $router->get('/info/chon', [ApiMobileController::class, 'infoChon']);

    $router->get('/iot/status', [ApiMobileController::class, 'statusIOT']);
    $router->get('/list-product', [ApiMobileController::class, 'listProduct']);
    $router->post('/tao-tem', [ApiMobileController::class, 'taoTem']);
});



Route::group([
    'prefix'        => "/api",
    'middleware'    => [],
    'as'            => "mobile/api" . '.',
], function (Router $router) {

    $router->post('/fix-order', [OrderController::class, 'fixBug']);
    $router->get('/update-data', [OrderController::class, 'updateData']);
    $router->get('/update-role', [OrderController::class, 'updateRole']);

    $router->post('/machine/update', [ApiMobileController::class, 'exMachineUpdate']);
    $router->get('/material/barcode', [ApiMobileController::class, 'inNhanMuc']);
    $router->get('/tem/print', [ApiMobileController::class, 'temPrint']);
    $router->get('/location/barcode', [ApiMobileController::class, 'locationBarcode']);

    $router->get('/product/barcode', [ApiMobileController::class, 'productBarcode']);
    $router->post('/upload-ke-hoach-xuat-kho-tong', [ApiMobileController::class, 'uploadKHXKT']);
    $router->post('/upload-ke-hoach-san-xuat', [ApiMobileController::class, 'uploadKHSX']);
    $router->post('/upload-ton-kho', [ApiMobileController::class, 'uploadTonKho']);
    $router->post('/upload-buyer', [ApiController::class, 'uploadBUYER']);
    $router->post('/upload-layout', [ApiController::class, 'uploadLAYOUT']);
    $router->post('/lot/store', [ApiMobileController::class, 'storeLot']);
    $router->get('lot/list-table', [ApiMobileController::class, 'listLot']);
    $router->post('/upload-ke-hoach-xuat-kho', [ApiMobileController::class, 'uploadKHXK']);
    $router->get('/production-plan/list', [ApiMobileController::class, 'getListProductionPlan']);
    $router->get('/warehouse/list-export-plan', [ApiMobileController::class, 'getListWareHouseExportPlan']);

    //// ROUTE CỦA AN
    $router->get('line/list-machine', [ApiMobileController::class, 'getMachineOfLine']);
    $router->get('line/machine/check-sheet', [ApiMobileController::class, 'getChecksheetOfMachine']);
    $router->post('line/check-sheet-log/save', [ApiMobileController::class, 'lineChecksheetLogSave']);
    $router->get('line/error', [ApiMobileController::class, 'lineError']);
    $router->get('machine/overall', [ApiMobileController::class, 'machineOverall']);
    ///HẾT

    //EXPORT
    $router->get('/export/machine_error', [ApiUIController::class, 'exportMachineError']);
    $router->get('/export/thong-so-may', [ApiUIController::class, 'exportThongSoMay']);
    $router->get('/export/warehouse/history', [ApiUIController::class, 'exportHistoryWarehouse']);
    $router->get('/export/qc/history/pqc', [ApiUIController::class, 'exportQCHistoryPQC']);
    $router->get('/export/oqc', [ApiUIController::class, 'exportOQC']);
    $router->get('/export/pqc', [ApiController::class, 'exportPQC']);
    $router->get('/export/qc-history', [ApiUIController::class, 'exportQCHistory']);
    $router->get('/export/report-qc', [ApiUIController::class, 'exportReportQC']);
    $router->get('/export/report-produce-history', [ApiUIController::class, 'exportReportProduceHistory']);
    $router->get('/export/warehouse/summary', [ApiUIController::class, 'exportSummaryWarehouse']);
    $router->get('/export/warehouse/bmcard', [ApiUIController::class, 'exportBMCardWarehouse']);
    $router->get('/export/kpi', [ApiUIController::class, 'exportKPI']);
    $router->get('/export/history-monitors', [ApiUIController::class, 'exportHistoryMonitors']);

    $router->get('ui/data-filter', [ApiUIController::class, 'getDataFilterUI']);
});

//New route
Route::group([
    'prefix'        => "/api",
    'middleware'    => [],
    'as'            => "/api" . '.',
], function (Router $router) {
    $router->post('/login', [ApiMobileController::class, 'login']);
});
Route::group([
    'prefix'        => "/api",
    'middleware'    => [],
    'as'            => '',
], function (Router $router) {
    $router->post('websocket', [ApiController::class, 'websocket']);
    $router->post('websocket-machine-status', [ApiController::class, 'websocketMachineStatus']);
    $router->post('websocket-machine-params', [ApiController::class, 'websocketMachineParams']);
});

Route::group([
    'prefix'        => "/api/oi",
    'middleware'    => "auth:sanctum",
    'as'            => '',
], function (Router $router) {
    $router->get('machine/list', [ApiController::class, 'listMachine']);

    $router->get('manufacture/tracking-status', [ApiController::class, 'getTrackingStatus']);
    $router->get('manufacture/current', [ApiController::class, 'getCurrentManufacturing']);
    $router->post('manufacture/start-produce', [ApiController::class, 'startProduce']);
    $router->post('manufacture/stop-produce', [ApiController::class, 'stopProduce']);
    $router->get('manufacture/overall', [ApiController::class, 'getManufactureOverall']);
    $router->get('manufacture/list-lot', [ApiController::class, 'listLotOI']);
    $router->get('manufacture/intem', [ApiController::class, 'inTem']);
    $router->post('manufacture/scan', [ApiController::class, 'scan'])->middleware('prevent-duplicate-requests');
    $router->post('manufacture/start-tracking', [ApiController::class, 'startTracking']);
    $router->post('manufacture/stop-tracking', [ApiController::class, 'stopTracking']);
    $router->post('manufacture/reorder-priority', [ApiController::class, 'reorderPriority']);
    $router->get('manufacture/paused-plan-list', [ApiController::class, 'getPausedPlanList']);
    $router->post('manufacture/pause-plan', [ApiController::class, 'pausePlan']);
    $router->post('manufacture/resume-plan', [ApiController::class, 'resumePlan']);
    $router->post('manufacture/update-quantity-info-cong-doan', [ApiController::class, 'updateQuantityInfoCongDoan'])->middleware('prevent-duplicate-requests');
    $router->post('manufacture/delete-paused-plan-list', [ApiController::class, 'deletePausedPlanList']);
    $router->post('manufacture/update-so-du', [ApiController::class, 'updateSodu']);
    $router->post('manufacture/corrugating-machine-scan', [ApiController::class, 'corrugatingMachineScan']);

    $router->post('manufacture/manual/input', [ApiController::class, 'manualInput']);
    $router->post('manufacture/manual/scan', [ApiController::class, 'scanManual'])->middleware('prevent-duplicate-requests');
    $router->get('manufacture/manual/list', [ApiController::class, 'manualList']);
    $router->post('manufacture/manual/print', [ApiController::class, 'manualPrintStamp']);

    $router->get('qc/check-permission', [ApiController::class, 'checkUserPermission']);
    $router->get('qc/line', [ApiController::class, 'getQCLine']);

    $router->get('pqc/error/list', [ApiController::class, 'getLoiNgoaiQuanPQC']);
    $router->get('pqc/checksheet/list', [ApiController::class, 'getLoiTinhNangPQCTest']);
    $router->post('pqc/save-result', [ApiController::class, 'saveQCResult'])->middleware('prevent-duplicate-requests');
    $router->get('pqc/lot/list', [ApiController::class, 'pqcLotList']);
    $router->get('pqc/overall', [ApiController::class, 'pqcOverall']);

    $router->get('iqc/error/list', [ApiController::class, 'getLoiNgoaiQuanIQC']);
    $router->get('iqc/checksheet/list', [ApiController::class, 'getLoiTinhNangIQC']);
    $router->post('iqc/save-result', [ApiController::class, 'saveIQCResult'])->middleware('prevent-duplicate-requests');
    $router->get('iqc/lot/list', [ApiController::class, 'iqcLotList']);
    $router->get('iqc/overall', [ApiController::class, 'iqcOverall']);

    $router->get('equipment/overall', [ApiController::class, 'overallMachine']);
    $router->get('equipment/mapping-list', [ApiController::class, 'getMappingList']);
    $router->get('equipment/error/log', [ApiController::class, 'errorMachineLog']);
    $router->get('equipment/error/list', [ApiController::class, 'errorMachineList']);
    $router->get('equipment/error/detail', [ApiController::class, 'errorMachineDetail']);
    $router->post('equipment/error/result', [ApiController::class, 'errorMachineResult']);
    $router->get('equipment/parameters', [ApiController::class, 'getMachineParameters']);
    $router->get('equipment/parameters/list', [ApiController::class, 'getMachineParameterList']);
    $router->post('equipment/parameters/save', [ApiController::class, 'saveMachineParameters'])->middleware('prevent-duplicate-requests');
    $router->get('equipment/mapping/list', [ApiController::class, 'getListMappingRequire']);
    $router->get('equipment/mapping/check-material', [ApiController::class, 'checkMapping']);
    $router->post('equipment/mapping/result', [ApiController::class, 'resultMapping'])->middleware('prevent-duplicate-requests');


    $router->get('warehouse/mlt/import/log', [ApiController::class, 'importMLTLog']);
    $router->get('warehouse/mlt/import/scan', [ApiController::class, 'importMLTScan']);
    $router->post('warehouse/mlt/import/save', [ApiController::class, 'importMLTSave'])->middleware('prevent-duplicate-requests');
    $router->post('warehouse/mlt/import/reimport', [ApiController::class, 'importMLTReimport'])->middleware('prevent-duplicate-requests');
    $router->get('warehouse/mlt/import/overall', [ApiController::class, 'importMLTOverall']);
    $router->post('warehouse/mlt/import/warehouse13', [ApiController::class, 'handleNGMaterial'])->middleware('prevent-duplicate-requests');;

    $router->get('warehouse/mlt/export/log-list', [ApiController::class, 'getExportMLTLogs']);
    $router->get('warehouse/mlt/export/scan', [ApiController::class, 'exportMLTScan']);
    $router->get('warehouse/mlt/export/result', [ApiController::class, 'updateExportMLTLogs'])->middleware('prevent-duplicate-requests');
    $router->get('warehouse/mlt/export/list', [ApiController::class, 'exportMLTList']);
    $router->post('warehouse/mlt/export/save', [ApiController::class, 'exportMLTSave'])->middleware('prevent-duplicate-requests');

    $router->get('warehouse/fg/list-pallet', [ApiController::class, 'listPallet']);
    $router->get('warehouse/fg/info-pallet', [ApiController::class, 'infoPallet']);
    // $router->get('warehouse/fg/overall', [ApiController::class, 'getOverallWarehouseFG']);
    $router->get('warehouse/fg/list', [ApiController::class, 'getWarehouseFGLogs']);
    $router->get('warehouse/fg/suggest-pallet', [ApiController::class, 'suggestPallet']);
    $router->get('warehouse/fg/quantity-lot', [ApiController::class, 'quantityLosx']);
    $router->post('warehouse/fg/store-pallet', [ApiController::class, 'storePallet'])->middleware('prevent-duplicate-requests');
    $router->post('warehouse/fg/update-pallet', [ApiController::class, 'updatePallet'])->middleware('prevent-duplicate-requests');
    $router->post('warehouse/fg/import/save', [ApiController::class, 'importFGSave'])->middleware('prevent-duplicate-requests');
    $router->get('warehouse/fg/import/logs', [ApiController::class, 'getLogImportWarehouseFG']);
    $router->get('warehouse/fg/overall', [ApiController::class, 'getFGOverall']);
    $router->get('warehouse/fg/check-losx', [ApiController::class, 'checkLosx']);

    $router->get('warehouse/fg/export/logs', [ApiController::class, 'getLogExportWarehouseFG']);
    $router->get('warehouse/fg/export/list-delivery-note', [ApiController::class, 'getDeliveryNoteList']);
    $router->get('warehouse/fg/export/check-pallet', [ApiController::class, 'checkLoSXPallet']);
    $router->post('warehouse/fg/export/handle-export-pallet', [ApiController::class, 'exportPallet'])->middleware('prevent-duplicate-requests');
    $router->get('warehouse/fg/export/download-delivery-note', [ApiController::class, 'exportWarehouseFGDeliveryNote']);
});

Route::group([
    'prefix'        => "/api/ui",
    'middleware'    => "auth:sanctum",
    'as'            => '',
], function (Router $router) {

    $router->get('customers', [ApiController::class, 'ui_getCustomers']);
    $router->get('orders', [ApiController::class, 'ui_getOrders']);
    $router->get('lo_sx', [ApiController::class, 'ui_getLoSanXuat']);

    $router->get('machine/list', [ApiController::class, 'listMachineUI']);
    $router->get('manufacture/line', [ApiController::class, 'lineList']);
    $router->get('manufacture/production-plan/list', [ApiController::class, 'productionPlan']);
    $router->post('manufacture/handle-plan', [ApiController::class, 'handlePlan']);
    $router->post('manufacture/production-plan/handle', [ApiController::class, 'handleProductionPlan']);
    $router->get('manufacture/production-plan/export', [ApiController::class, 'exportKHSX']);
    $router->post('manufacture/production-plan/export-preview-plan', [ApiController::class, 'exportPreviewPlan']);
    $router->post('manufacture/production-plan/export-preview-plan-xa-lot', [ApiController::class, 'exportPreviewPlanXaLot']);
    $router->get('manufacture/production-plan/export-xa-lot', [ApiController::class, 'exportKHXaLot']);
    $router->get('manufacture/produce-percent', [ApiController::class, 'producePercent']);
    $router->get('manufacture/produce-overall', [ApiController::class, 'produceOverall']);
    $router->get('manufacture/produce-table', [ApiController::class, 'produceHistory']);
    $router->get('export/produce/history', [ApiController::class, 'exportProduceHistory']);
    $router->get('manufacture/buyer/list', [ApiController::class, 'listBuyer']);
    $router->delete('manufacture/production-histoy/delete/{id}', [ApiController::class, 'deleteProductionHistory']);

    $router->get('manufacture/order/list', [ApiController::class, 'getOrderList']);

    $router->get('manufacture/layout/list', [ApiController::class, 'listLayout']);
    $router->post('manufacture/handle-order', [ApiController::class, 'handleOrder']);
    $router->post('manufacture/create-plan', [ApiController::class, 'createProductionPlan']);
    $router->get('manufacture/drc/list', [ApiController::class, 'listDRC']);

    $router->post('manufacture/tem/upload', [ApiController::class, 'uploadTem']);
    $router->get('manufacture/tem/list', [ApiController::class, 'listTem']);
    $router->post('manufacture/tem/update', [ApiController::class, 'updateTem']);
    $router->post('manufacture/tem/create-from-order', [ApiController::class, 'createStampFromOrder']);
    $router->delete('manufacture/tem/delete/{id}', [ApiController::class, 'deleteTem']);
    $router->post('manufacture/tem/delete-multiple', [ApiController::class, 'deleteTems']);

    $router->get('quality/overall', [ApiController::class, 'qualityOverall']);
    $router->get('quality/table-error-detail', [ApiController::class, 'errorTable']);
    $router->post('quality/recheck', [ApiController::class, 'recheckQC']);
    $router->get('quality/error-trending', [ApiController::class, 'errorQC']);
    $router->get('quality/top-error', [ApiController::class, 'topErrorQC']);
    $router->get('quality/qc-history', [ApiController::class, 'qcHistory']);
    $router->get('quality/qc-history/export', [ApiController::class, 'exportQCHistory']);
    $router->get('quality/iqc-history', [ApiController::class, 'iqcHistory']);
    $router->get('quality/iqc-history/export', [ApiController::class, 'exportIQCHistory']);
    $router->get('quality/qc-history/detail', [ApiController::class, 'getQCdetailHistory']);
    $router->get('quality/pqc-history/export', [ApiController::class, 'exportPQCHistory']);

    $router->get('equipment/performance', [ApiController::class, 'machinePerformance']);
    $router->get('equipment/error-machine-list', [ApiController::class, 'getErrorMachine']);
    $router->get('equipment/error-machine-list/export', [ApiController::class, 'exportErrorMachine']);
    $router->get('equipment/get-machine-param-logs', [ApiController::class, 'machineParameterTable']);
    $router->get('equipment/error-machine-frequency', [ApiController::class, 'errorMachineFrequency']);
    $router->get('equipment/parameter-machine-chart', [ApiController::class, 'getMachineParameterChart']);

    $router->get('warehouse/list-material-import', [ApiController::class, 'listMaterialImport']);
    $router->patch('warehouse/update-material-import', [ApiController::class, 'updateWarehouseMTLImport']);
    $router->post('warehouse/delete-material-import', [ApiController::class, 'deleteWarehouseMTLImport']);
    $router->post('warehouse/create-material-import', [ApiController::class, 'createWarehouseMTLImport']);
    $router->get('warehouse/list-material-export', [ApiController::class, 'listMaterialExport']);
    $router->get('warehouse/export-list-material-export', [ApiController::class, 'exportListMaterialExport']);
    $router->get('warehouse/import-material-ticket/export', [ApiController::class, 'exportWarehouseTicket']);
    $router->get('warehouse/vehicle-weight-ticket/export', [ApiController::class, 'exportVehicleWeightTicket']);

    $router->get('warehouse/fg/export/list', [ApiController::class, 'getWarehouseFGExportList']);
    $router->post('warehouse/fg/export/update', [ApiController::class, 'updateWarehouseFGExport']);
    $router->post('warehouse/fg/export/create', [ApiController::class, 'createWarehouseFGExport'])->middleware('prevent-duplicate-requests');;
    $router->delete('warehouse/fg/export/delete/{id}', [ApiController::class, 'deleteWarehouseFGExport']);
    $router->get('warehouse/list-pallet', [ApiController::class, 'getListPalletWarehouse']);
    $router->get('warehouse/fg/export/list/export', [ApiController::class, 'exportWarehouseFGExportList']);
    $router->post('warehouse/fg/update-export-log', [ApiController::class, 'updateExportFGLog']);

    $router->get('item-menu', [ApiController::class, 'getUIItemMenu']);
    $router->get('warehouse/mtl/goods-receipt-note', [ApiController::class, 'getGoodsReceiptNote']);
    $router->patch('goods-receipt-note/update', [ApiController::class, 'updateGoodsReceiptNote']);
    $router->delete('goods-receipt-note/delete', [ApiController::class, 'deleteGoodsReceiptNote']);

    $router->get('warehouse/mlt/log', [ApiController::class, 'warehouseMLTLog2']);
    $router->get('warehouse/mlt/detail-log', [ApiController::class, 'warehouseMLTDetailLog']);
    $router->get('export/warehouse-mlt-logs', [ApiController::class, 'exportWarehouseMLTLog']);

    $router->get('warehouse/fg/log', [ApiController::class, 'warehouseFGLog']);
    $router->get('export/warehouse-fg-logs', [ApiController::class, 'exportWarehouseFGLog']);
    $router->get('warehouse/fg/export/log-list', [ApiController::class, 'warehouseFGExportList']);
    $router->get('warehouse/fg/export/plan/list', [ApiController::class, 'getWarehouseFGExportPlan']);
    $router->post('warehouse/fg/export/plan/divide', [ApiController::class, 'divideFGExportPlan']);

    $router->get('delivery-note/list', [DeliveryNoteController::class, 'getDeliveryNoteList']);
    $router->post('delivery-note/create', [DeliveryNoteController::class, 'createDeliveryNote']);
    $router->post('delivery-note/delete', [DeliveryNoteController::class, 'deleteDeliveryNote']);
    $router->patch('delivery-note/update', [DeliveryNoteController::class, 'updateDeliveryNote']);

    $router->get('lsx-pallet/list', [LSXPalletController::class, 'getLSXPallet']);
    // $router->post('lsx-pallet/create', [LSXPalletController::class, 'createLSXPallet']);
    // $router->post('lsx-pallet/delete', [LSXPalletController::class, 'deleteLSXPallet']);
    // $router->patch('lsx-pallet/update', [LSXPalletController::class, 'updateLSXPallet']);
    $router->get('lsx-pallet/export', [LSXPalletController::class, 'exportLSXPallet']);
    $router->get('lsx-pallet/print-pallet', [LSXPalletController::class, 'printPallet']);
});

//Unrequired route
Route::group([
    'prefix'        => "/api",
    'middleware'    => "auth:sanctum",
    'as'            => '',
], function (Router $router) {
    $router->post('locate-by-supplier', [ApiController::class, 'phanKhuTheoNCC']);
    $router->post('import/tieu_chuan_ncc', [ApiController::class, 'importTieuChuanNCC']);
    $router->post('locator-mtl-map-import', [ApiController::class, 'importLocatorMLTMap']);
    $router->post('orders/import-from-plan', [App\Admin\Controllers\OrderController::class, 'importOrdersFromPLan']);

    $router->get('info-cong-doan/list', [InfoCongDoanController::class, 'getInfoCongDoan']);
    $router->post('info-cong-doan/update', [InfoCongDoanController::class, 'updateInfoCongDoan']);
    $router->get('info-cong-doan/export', [InfoCongDoanController::class, 'exportInfoCongDoan']);
    $router->post('info-cong-doan/import', [InfoCongDoanController::class, 'importInfoCongDoan']);

    $router->get('machines/list', [App\Admin\Controllers\MachineController::class, 'getMachines']);
    $router->patch('machines/update', [App\Admin\Controllers\MachineController::class, 'updateMachine']);
    $router->post('machines/create', [App\Admin\Controllers\MachineController::class, 'createMachine']);
    $router->post('machines/delete', [App\Admin\Controllers\MachineController::class, 'deleteMachines']);
    $router->get('machines/export', [App\Admin\Controllers\MachineController::class, 'exportMachines']);
    $router->post('machines/import', [App\Admin\Controllers\MachineController::class, 'importMachines']);

    $router->get('spec-product/list', [App\Admin\Controllers\ProductController::class, 'getSpecProduct']);
    $router->patch('spec-product/update', [App\Admin\Controllers\ProductController::class, 'updateSpecProduct']);
    $router->post('spec-product/create', [App\Admin\Controllers\ProductController::class, 'createSpecProduct']);
    $router->post('spec-product/delete', [App\Admin\Controllers\ProductController::class, 'deleteSpecProduct']);
    $router->get('spec-product/export', [App\Admin\Controllers\ProductController::class, 'exportSpecProduct']);
    $router->post('spec-product/import', [App\Admin\Controllers\ProductController::class, 'importSpecProduct']);

    $router->get('errors/list', [App\Admin\Controllers\ErrorController::class, 'getErrors']);
    $router->patch('errors/update', [App\Admin\Controllers\ErrorController::class, 'updateErrors']);
    $router->post('errors/create', [App\Admin\Controllers\ErrorController::class, 'createErrors']);
    $router->post('errors/delete', [App\Admin\Controllers\ErrorController::class, 'deleteErrors']);
    $router->get('errors/export', [App\Admin\Controllers\ErrorController::class, 'exportErrors']);
    $router->post('errors/import', [App\Admin\Controllers\ErrorController::class, 'importErrors']);

    $router->get('test_criteria/list', [App\Admin\Controllers\TestCriteriaController::class, 'getTestCriteria']);
    $router->patch('test_criteria/update', [App\Admin\Controllers\TestCriteriaController::class, 'updateTestCriteria']);
    $router->post('test_criteria/create', [App\Admin\Controllers\TestCriteriaController::class, 'createTestCriteria']);
    $router->post('test_criteria/delete', [App\Admin\Controllers\TestCriteriaController::class, 'deleteTestCriteria']);
    $router->get('test_criteria/export', [App\Admin\Controllers\TestCriteriaController::class, 'exportTestCriteria']);
    $router->post('test_criteria/import', [App\Admin\Controllers\TestCriteriaController::class, 'importTestCriteriaVer2']);

    $router->get('cong-doan/list', [App\Admin\Controllers\LineController::class, 'getLine']);
    $router->patch('cong-doan/update', [App\Admin\Controllers\LineController::class, 'updateLine']);
    $router->post('cong-doan/create', [App\Admin\Controllers\LineController::class, 'createLine']);
    $router->post('cong-doan/delete', [App\Admin\Controllers\LineController::class, 'deleteLine']);
    $router->get('cong-doan/export', [App\Admin\Controllers\LineController::class, 'exportLine']);
    $router->post('cong-doan/import', [App\Admin\Controllers\LineController::class, 'importLine']);

    $router->get('users/list', [App\Admin\Controllers\CustomAdminController::class, 'getUsers']);
    $router->get('users/roles', [App\Admin\Controllers\CustomAdminController::class, 'getUserRoles']);
    $router->patch('users/update', [App\Admin\Controllers\CustomAdminController::class, 'updateUsers']);
    $router->post('users/create', [App\Admin\Controllers\CustomAdminController::class, 'createUsers']);
    $router->post('users/delete', [App\Admin\Controllers\CustomAdminController::class, 'deleteUsers']);
    $router->post('users/disable', [App\Admin\Controllers\CustomAdminController::class, 'disableUsers']);
    $router->post('users/enable', [App\Admin\Controllers\CustomAdminController::class, 'enableUsers']);
    $router->get('users/export', [App\Admin\Controllers\CustomAdminController::class, 'exportUsers']);
    $router->post('users/import', [App\Admin\Controllers\CustomAdminController::class, 'importUsers']);

    $router->get('roles/tree', [App\Admin\Controllers\RoleController::class, 'getRoles']);
    $router->get('roles/list', [App\Admin\Controllers\RoleController::class, 'getRolesList']);
    $router->get('roles/permissions', [App\Admin\Controllers\RoleController::class, 'getPermissions']);
    $router->patch('roles/update', [App\Admin\Controllers\RoleController::class, 'updateRole']);
    $router->post('roles/create', [App\Admin\Controllers\RoleController::class, 'createRole']);
    $router->post('roles/delete', [App\Admin\Controllers\RoleController::class, 'deleteRoles']);
    $router->get('roles/export', [App\Admin\Controllers\RoleController::class, 'exportRoles']);
    $router->post('roles/import', [App\Admin\Controllers\RoleController::class, 'importRoles']);

    $router->get('permissions/list', [App\Admin\Controllers\PermissionController::class, 'getPermissions']);
    $router->patch('permissions/update', [App\Admin\Controllers\PermissionController::class, 'updatePermission']);
    $router->post('permissions/create', [App\Admin\Controllers\PermissionController::class, 'createPermission']);
    $router->post('permissions/delete', [App\Admin\Controllers\PermissionController::class, 'deletePermissions']);
    $router->get('permissions/export', [App\Admin\Controllers\PermissionController::class, 'exportPermissions']);
    $router->post('permissions/import', [App\Admin\Controllers\PermissionController::class, 'importPermissions']);

    $router->get('error-machines/list', [App\Admin\Controllers\ErrorMachineController::class, 'getErrorMachines']);
    $router->patch('error-machines/update', [App\Admin\Controllers\ErrorMachineController::class, 'updateErrorMachine']);
    $router->post('error-machines/create', [App\Admin\Controllers\ErrorMachineController::class, 'createErrorMachine']);
    $router->post('error-machines/delete', [App\Admin\Controllers\ErrorMachineController::class, 'deleteErrorMachines']);
    $router->get('error-machines/export', [App\Admin\Controllers\ErrorMachineController::class, 'exportErrorMachines']);
    $router->post('error-machines/import', [App\Admin\Controllers\ErrorMachineController::class, 'importErrorMachines']);

    $router->get('material/list', [App\Admin\Controllers\MaterialController::class, 'getMaterials']);
    $router->patch('material/update', [App\Admin\Controllers\MaterialController::class, 'updateMaterial']);
    $router->post('material/create', [App\Admin\Controllers\MaterialController::class, 'createMaterial']);
    $router->post('material/delete', [App\Admin\Controllers\MaterialController::class, 'deleteMaterials']);
    // $router->get('material/export', [App\Admin\Controllers\MaterialController::class, 'exportMaterials']);
    // $router->post('material/import', [App\Admin\Controllers\MaterialController::class, 'importMaterials']);

    $router->get('warehouses/list', [App\Admin\Controllers\WarehouseController::class, 'getWarehouses']);
    $router->patch('warehouses/update', [App\Admin\Controllers\WarehouseController::class, 'updateWarehouse']);
    $router->post('warehouses/create', [App\Admin\Controllers\WarehouseController::class, 'createWarehouse']);
    $router->post('warehouses/delete', [App\Admin\Controllers\WarehouseController::class, 'deleteWarehouses']);
    $router->get('warehouses/export', [App\Admin\Controllers\WarehouseController::class, 'exportWarehouses']);
    $router->post('warehouses/import', [App\Admin\Controllers\WarehouseController::class, 'importWarehouses']);

    $router->get('cells/list', [App\Admin\Controllers\CellController::class, 'getCells']);
    $router->patch('cells/update', [App\Admin\Controllers\CellController::class, 'updateCell']);
    $router->post('cells/create', [App\Admin\Controllers\CellController::class, 'createCell']);
    $router->post('cells/delete', [App\Admin\Controllers\CellController::class, 'deleteCells']);
    $router->get('cells/export', [App\Admin\Controllers\CellController::class, 'exportCells']);
    $router->post('cells/import', [App\Admin\Controllers\CellController::class, 'importCells']);

    $router->get('khuon/list', [App\Admin\Controllers\KhuonController::class, 'getKhuon']);
    $router->patch('khuon/update', [App\Admin\Controllers\KhuonController::class, 'updateKhuon']);
    $router->post('khuon/create', [App\Admin\Controllers\KhuonController::class, 'createKhuon']);
    $router->post('khuon/delete', [App\Admin\Controllers\KhuonController::class, 'deleteKhuon']);
    $router->get('khuon/export', [App\Admin\Controllers\KhuonController::class, 'exportKhuon']);
    $router->post('khuon/import', [App\Admin\Controllers\KhuonController::class, 'importKhuon']);

    $router->get('jig/list', [App\Admin\Controllers\JigController::class, 'getJig']);
    $router->patch('jig/update', [App\Admin\Controllers\JigController::class, 'updateJig']);
    $router->post('jig/create', [App\Admin\Controllers\JigController::class, 'createJig']);
    $router->post('jig/delete', [App\Admin\Controllers\JigController::class, 'deleteJig']);
    $router->get('jig/export', [App\Admin\Controllers\JigController::class, 'exportJig']);
    $router->post('jig/import', [App\Admin\Controllers\JigController::class, 'importJig']);

    $router->get('maintenance/list', [App\Admin\Controllers\MaintenanceController::class, 'getMaintenance']);
    $router->get('maintenance/detail', [App\Admin\Controllers\MaintenanceController::class, 'getMaintenanceDetail']);
    $router->patch('maintenance/update', [App\Admin\Controllers\MaintenanceController::class, 'updateMaintenance']);
    $router->post('maintenance/create', [App\Admin\Controllers\MaintenanceController::class, 'createMaintenance']);
    $router->post('maintenance/delete', [App\Admin\Controllers\MaintenanceController::class, 'deleteMaintenance']);
    $router->get('maintenance/export', [App\Admin\Controllers\MaintenanceController::class, 'exportMaintenance']);
    $router->post('maintenance/import', [App\Admin\Controllers\MaintenanceController::class, 'importMaintenance']);

    $router->get('orders/list', [App\Admin\Controllers\OrderController::class, 'getOrders']);
    $router->patch('orders/update', [App\Admin\Controllers\OrderController::class, 'updateOrders'])->middleware('check.permission:edit-order');
    $router->post('orders/create', [App\Admin\Controllers\OrderController::class, 'createOrder'])->middleware('check.permission:edit-order');;
    $router->delete('orders/delete', [App\Admin\Controllers\OrderController::class, 'deleteOrders'])->middleware('check.permission:edit-order');;
    $router->get('orders/export', [App\Admin\Controllers\OrderController::class, 'exportOrders']);
    $router->post('orders/import', [App\Admin\Controllers\OrderController::class, 'importOrders'])->middleware('check.permission:edit-order');;
    $router->post('orders/split', [App\Admin\Controllers\OrderController::class, 'splitOrders'])->middleware('check.permission:edit-order');;
    $router->post('orders/restore', [App\Admin\Controllers\OrderController::class, 'restoreOrders'])->middleware('check.permission:edit-order');;

    $router->get('customer/list', [App\Admin\Controllers\CustomerController::class, 'getCustomerByShortName']);
    $router->patch('customer/update', [App\Admin\Controllers\CustomerController::class, 'updateCustomer']);
    $router->post('customer/create', [App\Admin\Controllers\CustomerController::class, 'createCustomer']);
    $router->delete('customer/delete', [App\Admin\Controllers\CustomerController::class, 'deleteCustomer']);
    $router->get('customer/export', [App\Admin\Controllers\CustomerController::class, 'exportCustomer']);
    $router->post('customer/import', [App\Admin\Controllers\CustomerController::class, 'importCustomer']);
    $router->get('real-customer-list', [App\Admin\Controllers\CustomerController::class, 'getCustomers']);

    $router->post('buyers/create', [ApiController::class, 'createBuyers']);
    $router->patch('buyers/update', [ApiController::class, 'updateBuyers']);
    $router->delete('buyers/delete', [ApiController::class, 'deleteBuyers']);
    $router->get('buyers/export', [ApiController::class, 'exportBuyers']);

    $router->post('layouts/create', [ApiController::class, 'createLayouts']);
    $router->patch('layouts/update', [ApiController::class, 'updateLayouts']);
    $router->delete('layouts/delete', [ApiController::class, 'deleteLayouts']);

    $router->get('vehicles/list', [App\Admin\Controllers\VehicleController::class, 'getVehicles']);
    $router->patch('vehicles/update', [App\Admin\Controllers\VehicleController::class, 'updateVehicles']);
    $router->post('vehicles/create', [App\Admin\Controllers\VehicleController::class, 'createVehicles']);
    $router->delete('vehicles/delete', [App\Admin\Controllers\VehicleController::class, 'deleteVehicles']);
    $router->get('vehicles/export', [App\Admin\Controllers\VehicleController::class, 'exportVehicles']);
    $router->post('vehicles/import', [App\Admin\Controllers\VehicleController::class, 'importVehicles']);

    $router->get('machine-assignment/list', [UserLineMachineController::class, 'getMachineAssignment']);
    $router->post('machine-assignment/create', [UserLineMachineController::class, 'createMachineAssignment']);
    $router->post('machine-assignment/delete', [UserLineMachineController::class, 'deleteMachineAssignment']);
    $router->patch('machine-assignment/update', [UserLineMachineController::class, 'updateMachineAssignment']);

    $router->get('shift-assignment/list', [ShiftAssignmentController::class, 'getShiftAssignment']);
    $router->post('shift-assignment/create', [ShiftAssignmentController::class, 'createShiftAssignment']);
    $router->post('shift-assignment/delete', [ShiftAssignmentController::class, 'deleteShiftAssignment']);
    $router->patch('shift-assignment/update', [ShiftAssignmentController::class, 'updateShiftAssignment']);

    $router->get('shift/list', [ShiftController::class, 'getShift']);

    $router->post('parameters/import', [App\Admin\Controllers\MachineController::class, 'parametersImport']);
    $router->get('update-order', [ApiUIController::class, 'updateHGOrder']);

    $router->post('manufacture/production-plan/import', [ApiController::class, 'importKHSX']);
    $router->post('import/vehicle', [ApiUIController::class, 'importVehicle']);
    $router->post('update-tem', [ApiUIController::class, 'updateTem']);
    $router->post('import-khuon-link', [ApiUIController::class, 'importKhuonLink']);
    $router->post('upload-nhap-kho-nvl', [ApiController::class, 'uploadNKNVL']);

    $router->get('voc-types', [VOCTypeController::class, 'getList']);
    $router->get('voc', [VOCRegisterController::class, 'getList']);
    $router->post('voc', [VOCRegisterController::class, 'createRecord']);
    $router->put('voc/{id}', [VOCRegisterController::class, 'updateRecord']);
    $router->delete('voc/{id}', [VOCRegisterController::class, 'deleteRecord']);
    $router->post('voc/upload-file', [VOCRegisterController::class, 'uploadFile']);
    $router->post('voc/clear-unused-files', [VOCRegisterController::class, 'clearUnusedFiles']);

    $router->get('kpi-ty-le-ke-hoach', [KPIController::class, 'kpiTyLeKeHoach']);
    $router->get('kpi-ton-kho-nvl', [KPIController::class, 'kpiTonKhoNVL']);
    $router->get('kpi-ty-le-ng-pqc', [KPIController::class, 'kpiTyLeNGPQC']);
    $router->get('kpi-ty-le-van-hanh-thiet-bi', [KPIController::class, 'kpiTyLeVanHanh']);
    $router->get('kpi-ty-le-ke-hoach-in', [KPIController::class, 'kpiTyLeKeHoachIn']);
    $router->get('kpi-ty-le-loi-may', [KPIController::class, 'kpiTyLeLoiMay']);
    $router->get('kpi-ty-le-ng-oqc', [KPIController::class, 'kpiTyLeNGOQC']);
    $router->get('kpi-ton-kho-tp', [KPIController::class, 'kpiTonKhoTP']);

    $router->get('departments/list', [DepartmentController::class, 'index']);
    $router->post('departments/create', [DepartmentController::class, 'create']);
    $router->post('departments/delete', [DepartmentController::class, 'delete']);
    $router->patch('departments/update', [DepartmentController::class, 'update']);

    $router->get('suppliers/list', [SupplierController::class, 'index']);
    $router->post('suppliers/create', [SupplierController::class, 'create']);
    $router->post('suppliers/delete', [SupplierController::class, 'delete']);
    $router->patch('suppliers/update', [SupplierController::class, 'update']);

    $router->get('profile', [CustomAdminController::class, 'profile']);
});

Route::group([
    'prefix'        => "/api",
    'middleware'    => [],
    'as'            => '',
], function (Router $router) {
    $router->post('import-material', [ApiUIController::class, 'importMaterial']);
    $router->post('import', [ApiUIController::class, 'import']);
    $router->post('import-new-fg-locator', [ApiUIController::class, 'importNewFGLocator']);
    $router->get('intem', [ApiUIController::class, 'getTem']);
    $router->post('create-table-fields', [ApiUIController::class, 'insertTableFields']);
    $router->post('import-user-line-machine', [ApiUIController::class, 'importUserLineMachine']);
    $router->post('import-iqc-test-criterias', [ApiUIController::class, 'importIQCTestCriteria']);
    $router->post('update-customer-wahoure-fg-export', [ApiUIController::class, 'updateCustomerWarehouseFGExport']);
    $router->post('update-so-kg-dau-material', [ApiUIController::class, 'updateSoKGDauMaterial']);
    $router->post('searchMaterial', [ApiUIController::class, 'searchMasterDataMaterial']);
    $router->post('deleteOldMaterials', [ApiUIController::class, 'deleteMaterialHasNoLocation']); //Cập nhật tất cả vị trí kho có %C01% sang C01.001
    $router->post('sua-material', [ApiUIController::class, 'suaChuaLoiLam']);
    $router->get('update-info-from-plan', [ApiUIController::class, 'updateInfoFromPlan']);
    $router->get('update_admin_user_delivery_note', [ApiUIController::class, 'update_admin_user_delivery_note']);
    $router->post('update-new-machine-id', [ApiUIController::class, 'updateNewMachineId']);
    $router->get('update-ngaysx-info-cong-doan', [ApiUIController::class, 'updateNgaysxInfoCongDoan']);
    $router->get('update-dinhmuc-info-cong-doan', [ApiUIController::class, 'updateDinhMucInfoCongDoan']);
    $router->get('update-old-info-cong-doan', [ApiUIController::class, 'updateInfoCongDoanPriority']);
    $router->get('reset-info-cong-doan', [ApiUIController::class, 'resetInfoCongDoan']);
    $router->get('end-old-info-cong-doan', [ApiUIController::class, 'endOldInfoCongDoan']);
    $router->get('wtf', [ApiUIController::class, 'wtf']);
    $router->get('calculateUsageTime', [MESUsageRateController::class, 'calculateUsageTime']);
    $router->get('calculateMaintenanceMachine', [MESUsageRateController::class, 'calculateMaintenanceMachine']);
    $router->get('calculatePQCProcessing', [MESUsageRateController::class, 'calculatePQCProcessing']);
    $router->get('calculateKhuonBe', [MESUsageRateController::class, 'calculateKhuonBe']);
    $router->get('getTableSystemUsageRate', [MESUsageRateController::class, 'getTableSystemUsageRate']);
    $router->get('cronjob', [MESUsageRateController::class, 'cronjob']);
    $router->get('retriveData', [MESUsageRateController::class, 'retriveData']);
    $router->get('deleteDuplicate', [ApiUIController::class, 'deleteDuplicateWarehouseFGLog']);
    $router->post('capNhatTonKhoTPExcel', [ApiUIController::class, 'capNhatTonKhoTPExcel']);
    $router->get('reorderInfoCongDoan', [ApiController::class, 'reorderInfoCongDoan']);
    $router->get('kpi-cronjob', [KPIController::class, 'cronjob']);
    $router->get('update-thoi-gian-bat-dau', [ApiUIController::class, 'updateThoiGianBatDau']);
    $router->post('delete-duplicate-role-users', [ApiUIController::class, 'deleteDuplicateRoleUsers']);
    $router->post('update-type-lsx-pallet', [ApiUIController::class, 'updateTypeLSXPallet']);
    $router->post('update-status-lsx-pallet', [ApiUIController::class, 'updateStatusLSXPallet']);
    $router->post('restore-lost-material', [ApiUIController::class, 'restoreLostMaterial']);
    $router->post('updateLSXPalletIdWarehouseLog', [ApiUIController::class, 'updateLSXPalletIdWarehouseLog']);
    $router->post('exportAllFGBeforeDate', [ApiUIController::class, 'exportAllFGBeforeDate']);
    $router->get('getDuplicateWarehouseFGLog', [ApiUIController::class, 'getDuplicateWarehouseFGLog']);
    $router->get('clearRequestLogs', [ApiUIController::class, 'clearRequestLogs']);
    $router->get('deleteDuplicatePlan', [ApiUIController::class, 'deleteDuplicatePlan']);
    $router->get('deleteSoftDeletedOrder', [ApiUIController::class, 'deleteSoftDeletedOrder']);
});

//Chat
Route::group([
    'prefix'        => "/api",
    'middleware'    => "auth:sanctum",
    'as'            => '',
], function (Router $router) {
    $router->get('chats', [ChatController::class, 'index']);
    // Tạo chat mới (private hoặc group)
    $router->post('chats', [ChatController::class, 'store']);
    // Cập nhật chat (đổi tên/avatar nhóm)
    $router->patch('chats/{chat_id}', [ChatController::class, 'update']);
    // Xoá chat
    $router->delete('chats/{chat_id}', [ChatController::class, 'delete']);
    // Rời khỏi nhóm
    $router->post('chats/{chat_id}/leave', [ChatController::class, 'leave']);
    // Thêm thành viên
    $router->post('chats/{chat_id}/members', [ChatController::class, 'addMember']);
    // Bớt thành viên
    $router->delete('chats/{chat_id}/members/{user}', [ChatController::class, 'removeMember']);
    // Thay đổi trạng thái thông báo cho người dùng
    $router->post('chats/{chat_id}/muted/{user_id}', [ChatController::class, 'mutedChat']);
    // Lấy lịch sử tin nhắn (cursor-based)
    $router->get('chats/{chat_id}/messages', [ChatController::class, 'messages']);
    // Gửi tin nhắn (text/image/file/reply…)
    $router->post('chats/{chat_id}/messages', [ChatController::class, 'sendMessage']);
    //Xoá tin nhắn
    $router->delete('chats/{chat_id}/messages/{message_id}', [ChatController::class, 'deleteMessage']);
    //Cập nhật tin nhắn
    $router->patch('chats/{chat_id}/messages/{message_id}', [ChatController::class, 'updateMessage']);
    //Thu hồi tin nhắn
    $router->post('chats/{chat_id}/messages/{message_id}/recall', [ChatController::class, 'recallMessage']);
    // Mark-as-read (read receipt)
    $router->post('chats/{chat_id}/read', [ChatController::class, 'markAsRead']);

    $router->get('/download/{location}/{file_name}', [ChatController::class, 'downloadFile'] );
    $router->get('/files/{chat_id}', [ChatController::class, 'files'] );

    $router->get('/notifications', [ChatController::class, 'getNotifications']);
    $router->post('/notifications/{id}/read', [ChatController::class, 'readNotifications']);
    $router->post('/notifications/read-multiple', [ChatController::class, 'readMultipleNotifications']);
});
