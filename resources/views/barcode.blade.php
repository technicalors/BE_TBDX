<html>
<!-- CSS only -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">


<table id='table' class='table text-center'>
    <thead>
        <th>#</th>
        <th>Tên vật liệu</th>
       
        <th>Barcode</th>
    </thead>

    <tbody id='body'>

    </tbody>
</table>

<script src='https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js'></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js"
    integrity="sha512-aVKKRRi/Q/YV+4mjoKBsE4x3H+BkegoM/em46NNlCqNTmUYADjBbeNefNxYV7giUp0VxICtqdrbqU7iVaeZNXA=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    console.log('123');
    let products = @json($products);


    function renderRow(product, index) {
        return `
        <tr>
            <td>${index}</td>
            <td>${product.name}</td>
        
            <td>
                <svg
                    id='barcode_${product.id}'>
                </svg>
            </td>
        </tr>
        `
    }

    let mark = {
        "TPXB0079-02": true,
        "TPX-0079-02": true,
    };

    function render() {
        let html = '';
        for (let i = 0; i < products.length; i++) {
            let product = products[i];

            html += renderRow(product, i + 1);
        }
        $('#body').html(html);

        for (let i = 0; i < products.length; i++) {
            let product = products[i];

            JsBarcode(`#barcode_${product.id}`, product.id);
        }
    }
    $(function() {
        render()
    });
</script>


</html>
