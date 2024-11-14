<style>
    .btn-file {
        position: relative;
        overflow: hidden;
    }
    .btn-file:hover {
        cursor: pointer;
    }
    .btn-file input[type=file] {
        position: absolute;
        top: 0;
        right: 0;
        min-width: 100%;
        min-height: 100%;
        font-size: 100px;
        text-align: right;
        filter: alpha(opacity=0);
        opacity: 0;
        outline: none;
        background: white;
        cursor: inherit;
        display: block;
    }
</style>
<form id='form_import' method='POST' action="{{ $action }}" enctype='multipart/form-data' style='margin-top:5px'>
    @csrf
    <span class="btn btn-primary btn-file">
        {{$title}}
        <input accept=".xlsx, .xls, .csv" type='file' name='files' class='form-control' style='margin-right:5px' onchange="this.form.submit();">
    </span>
</form>
