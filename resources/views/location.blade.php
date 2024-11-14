<html>
<!-- CSS only -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">


<table id='table' class='table text-center'>
    <thead>
        <th>#</th>
        <th>Vị trí</th>
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
    let cells = @json($cells);


    function renderRow(cell, index) {
        return `
        <tr>
            <td>${index}</td>
            <td>${cell.name}</td>
            <td>
                <svg
              
        
                id='barcode_${index}'>
                </svg>
            </td>
        </tr>
        `
    }

    function rename(name) {

        let res = '';
        for (let i = 0; i < name.length; i++) {
            if (name[i] == 'T' || name[i] == 'S' && i < name.length - 1) {
                res = res + name[i] + '-';
            } else {
                res += name[i]
            }
        }
        return res;
    }

    function render() {
        let html = '';
        for (let i = 0; i < cells.length; i++) {
            let cell = cells[i];
            // cell.name = `${cell.sheft.name}-${cell.row[0]}-${cell.row[1]}${cell.row[2]}-${cell.col}`;
            cell.name = `${cell.sheft.name}-${cell.row}-${cell.col}`;
            cell.name = rename(cell.name)
            html += renderRow(cell, i + 1);
        }
        $('#body').html(html);


        for (let i = 0; i < cells.length; i++) {
            let cell = cells[i];
            cell.name = `${cell.sheft.name}-${cell.row}-${cell.col}`;
            cell.name = rename(cell.name)
            JsBarcode(`#barcode_${i+1}`, cell.name);

        }
      
        // let A = 'ABCDEFGHIJKLMNO';
        // let index = 0;
        // let html = '';
        // for (let i = 0; i < A.length; i++) {
        //     for (let j = 1; j <= 3; j++) {
        //         for (let k = 1; k <= 14; k++) {
        //             index++;
        //             let f = '0';
        //             if (k > 9) f = '1';
        //             let z = k % 10;
        //             let name = `${A[i]}0${j}-${f}${z}`;
        //             html += renderRow({
        //                 "name": name
        //             }, index)
        //         }
        //     }
        // }
        // $('#body').html(html);

        // index = 0;
        // for (let i = 0; i < A.length; i++) {
        //     for (let j = 1; j <= 3; j++) {
        //         for (let k = 1; k <= 14; k++) {
        //             index++;
        //             let f = '0';
        //             if (k > 9) f = '1';
        //             let z = k % 10;
        //             let name = `${A[i]}0${j}-${f}${z}`;
        //             let cell = {
        //                 name
        //             }
        //             JsBarcode(`#barcode_${index}`, cell.name);
        //         }
        //     }
        // }

    }
    $(function() {
        render()
    });
</script>


</html>
