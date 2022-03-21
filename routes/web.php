<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API;


Route::get('/', function () {
    return view('welcome');
});



/*
| API'S INVENTORY_SYSTEM
| Franco Cumplido 
| ECO S.A
| 2021
*/



Route::group(['middleware' => 'cors', 'prefix' => 'api'], function(){

//Esta API es llamada desde el desde el core de JavaSript del sistema de inventario (servdor 154) /opt/odoo14/odoo/addons/web/static/src/js/core/ajax.js
//Se llama "ajax_status" por que cada vez que se ejecuta una llamada Ajax o cada vez que se devuelve el status del usuario se ejecuta esta API  
	// [ACTUALIZADO] Ahora se ejecuta cada 60 seg desde el servidor 154 con CURL.
    Route::get('/ajax_status', [API::class, 'ajax_status']);

//Esta API pasa el F8 al servidor 154 como ordenes de recogida("picking")
//Se llama "ajax_200" por que la invocación la API se genera con JQUERY y AJAX desde el layout del sistema Imatronix 
    Route::get('/ajax_200/{codigo_nota_de_venta}', [API::class, 'ajax_200']);

//Esta API se encarga de ejecutar la carga de productos al servidor 154.
//Separa los productos por bodega (ECOSA O Truper) Y por planta (1er piso - 2do piso)
    Route::get('/carga_inicial', [API::class, 'carga_inicial']);

//Esta API se encarga de gestionar el recibimietos de los embarques y dejarlos en la recuencia de entrada → secuencia de "operacion clave"
//La invocacion ocurre desde el layout del sistema Imatronix 
    Route::get('/recibimiento_embarque/{orden_de_compra}', [API::class, 'recibimiento_embarque']);

//Esta API ejecuta la actualizacion de cantidades digitadas erroneamente. Su invocacion se produce en el layout del FRONT-END del sistema de Imatronix
//Al momento de hacer click boton "UP" en el listado de ventas del sistema Imatrinix se actualiza tambien las secuencia o ordenes del sistema "Inventory_System" 
    Route::get('/modificar_nota_de_venta/{nota_de_venta}', [API::class, 'modificar_nota_de_venta']);

//API que se encarga del ingreso de productos(POR EL MOMENTO VERIFICA SOLO SI LOS PRODUCTOS SON TRUPER)
    Route::get('/ingreso_productos/{data}', [API::class, 'ingreso_productos']);







});


















/* ANEXOS */

//RUTA EXCEL PRODUCTOS
Route::GET('/api/productos',[API::class,'productos']);

//testeos varios
Route::get('/productos_contabilidad/{fecha}', [API::class, 'productos_modulo_contabilidad']);