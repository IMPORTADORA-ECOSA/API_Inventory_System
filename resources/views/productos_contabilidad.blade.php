

<!DOCTYPE html>
<html>
<head>
<title>Precio Producto</title>
<script src="https://code.jquery.com/jquery-3.6.0.js" integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk=" crossorigin="anonymous"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/jszip-2.5.0/dt-1.10.16/b-1.5.1/b-html5-1.5.1/datatables.min.css"/>
<script type="text/javascript" src="https://cdn.datatables.net/v/dt/jszip-2.5.0/dt-1.10.16/b-1.5.1/b-html5-1.5.1/datatables.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css" rel="stylesheet"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>
</head>
<body>


<h1>Productos Vendidos</h1>



<div class="container" style="position: absolute; left: 50%; margin-left: -100px;">
    <p style="display: flex; flex-wrap: wrap;  justify-content: center;  align-items: center;">MES<p/>
    <select style="width: 120px;" id="select_mes">
	    <option value="01">Enero</option>
	    <option value="02">Febrero</option>
	    <option value="03">Marzo</option>
	    <option value="04">Abril</option>
	    <option value="05">Mayo</option>
	    <option value="06">Junio</option>
	    <option value="07">Julio</option>
	    <option value="08">Agosto</option>
	    <option value="09">Septiembre</option>
	    <option value="10">Octubre</option>
	    <option value="11">Noviembre</option>
	    <option value="12">Diciembre</option>
    </select>

    <p style="display: flex; flex-wrap: wrap;  justify-content: center;  align-items: center;">AÑO</p>
    <select  style="width: 120px;" id="select_ano">
	    <option value="2020">2020</option>
	    <option value="2021">2021</option>
	    <option value="2022">2022</option>
    </select>

    <div class="container" style="display: flex; flex-wrap: wrap;  justify-content: center;  align-items: center; margin-top: 20px;">
        <button id="buscar" class="btn btn-primary">Buscar</button>	
    </div>
    
</div>



<div class="container" style="margin-top: 250px;">
<table id="example" class="table table-striped table-bordered container2" style="width:100%;">
    <thead>
        <tr>
            <th style="text-align: center;">Código</th>
            <th style="text-align: center;">Descripcion</th>
            <th style="text-align: center;">Precio unitario</th>
            <th style="text-align: center;">Costo $ extranjero</th>
            <th style="text-align: center;">Cantidad vendida</th>
            <th style="text-align: center;">Total $ vendido</th>
            <th style="text-align: center;">Fecha factura</th>
        </tr>
    </thead>
    <tbody>
        @foreach($productos as $key => $value)
        <tr>            
            <td style="text-align: center;">{{$value->codigo}}</td>
            <td style="text-align: center;">{{$value->descripcion}}</td>
            <td style="text-align: center;">{{"$".$value->precio_producto}}</td>
            <td style="text-align: center;">{{$value->costo_moneda_extranjera}}</td>
            <td id="cantidad" style="text-align: center;">{{intval($value->cantidad_vendida)}}</td>
            <td style="text-align: center;">{{"$".$value->total_pesos}}</td>
            <td style="text-align: center;">{{gettype($value->cantidad_vendida)}}</td>         
        </tr>
        @endforeach
    </tbody>
</table>
</div>

</body>




<script>
$(document).ready(function() {
    $('#select_mes').select2();
    $('#select_ano').select2();

    $("#buscar").click(function(){
        var mes = $('#select_mes').val();
        var ano = $('#select_ano').val();
        window.location.href = 'http://201.239.17.218:8000/productos_contabilidad/'+ano+"-"+mes+"-";
    });

            
    $('#example').DataTable({
        "columnDefs": [
            { "type": "num", targets: 4 }
        ],
        "order": [],
        dom: 'Blfrtip',
        buttons: [
            'csv', 'excel',
        ],
        language: {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
        },
    });
});

</script>


<style>

canvas {
  display: block;
  max-width: 1200px;
  margin: 60px auto;
}


    /*  
    Side Navigation Menu V2, RWD
    ===================
    Author: https://github.com/pablorgarcia
 */

@charset "UTF-8";
@import url(https://fonts.googleapis.com/css?family=Open+Sans:300,400,700);

body {
  font-family: 'Open Sans', sans-serif;
  font-weight: 300;
  line-height: 1.42em;
  color:#A7A1AE;
  background-color:#ffffff;
}

h1 {
  font-size:3em; 
  font-weight: 300;
  line-height:1em;
  text-align: center;
  color:    #70b536;
}

h2 {
  font-size:1em; 
  font-weight: 300;
  text-align: center;
  display: block;
  line-height:1em;
  padding-bottom: 2em;
  color:    #70b536;
}

h2 a {
  font-weight: 700;
  text-transform: uppercase;
  color: #FB667A;
  text-decoration: none;
}

.blue { color: #185875; }
.yellow { color: black }

.container2 th h1 {
      font-weight: bold;
      font-size: 2em;
      text-align: center;
      color: #185875;
}

.container2 td {
      font-weight: normal;
      font-size: 1em;
       -webkit-box-shadow: 0 2px 2px -2px #0E1119;
       -moz-box-shadow: 0 2px 2px -2px #0E1119;
        box-shadow: 0 2px 2px -2px #0E1119;
}

.container2 {
      text-align: left;
      overflow: hidden;
      width: 90%;
      height: 80%;
      margin: 0 auto;
      display: table;
      padding: 0 0 8em 0;
}

.container2 td, .container th {
      padding-bottom: 2%;
      padding-top: 2%;
    padding-left:2%; 

}

/* Background-color of the odd rows */
.container2 tr:nth-child(odd) {
      background-color: #black;
}

/* Background-color of the even rows */
.container2 tr:nth-child(even) {
      background-color: #black;
      
}

.container2 th {
      background-color: #3939c6;
      color: white;
}

.container2 td:first-child { color: #FB667A; }

.container2 tr:hover {
   background-color: ;
           -webkit-box-shadow: 0 6px 6px -6px #0E1119;
           -moz-box-shadow: 0 6px 6px -6px #0E1119;
            box-shadow: 0 6px 6px -6px #0000;
}

.container2 td:hover {
  background-color:     #70b536;
  color: #403E10;
  font-weight: bold;
  
  box-shadow: #7F7C21 -1px 1px, #7F7C21 -2px 2px, #7F7C21 -3px 3px, #7F7C21 -4px 4px, #7F7C21 -5px 5px, #7F7C21 -6px 6px;
  transform: translate3d(6px, -6px, 0);
  
  transition-delay: 0s;
      transition-duration: 0.4s;
      transition-property: all;
  transition-timing-function: line;
}

@media (max-width: 800px) {
.container2 td:nth-child(4),
.container2 th:nth-child(4) { display: none; }
}
    


</style>









</html>
