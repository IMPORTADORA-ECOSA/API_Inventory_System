<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Excel;
use App\Imports\ExcelImport;
use Carbon\Carbon;


class API extends Controller{
    

    public function ajax_status(){
        
        /*
            CON QUERY BUILDER SE HARÁ PARA LA TERCERA REFACTORIZACIÓN
            $results = DB::connection('inventory_system')->table('product_template')->get();
            dd($results[1]);
        */


        //header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Allow: GET, POST, OPTIONS, PUT, DELETE");
        //$method = $_SERVER['REQUEST_METHOD'];
        
        
        
        
        
        //Servidor 192.168.0.154 "Inventory System"
        $host_InventorySystem = "192.168.0.154";
        $puerto_Inventory_System= "5432";
        $dbname_Inventory_System = "InventorySystem";
        $dbsuer_InventorySystem = "postgres";
        $userpass_InventorySystem = "";
        $con_InventorySystem = pg_connect("host=$host_InventorySystem port=$puerto_Inventory_System dbname=$dbname_Inventory_System user=$dbsuer_InventorySystem password=$userpass_InventorySystem");
        
        
        
        
        //Servidor 192.168.0.200 "Imatronix"
        $host_200 = "192.168.0.200";
        $dbname_200 = "imatronix_ecosa";
        $dbuser_200 = "postgres";
        $userpass_200 = "";
        $con_200 = pg_connect("host=$host_200 dbname=$dbname_200 user=$dbuser_200 password=$userpass_200");
        
        

        
        //Fecha actual
        $hoy = getdate();
        $fecha_actual ="";
        if($hoy["mon"] > 9 AND $hoy["mon"] <= 13  ){
          $fecha_actual = ($hoy["year"]."-".$hoy["mon"]."-".$hoy["mday"]." ".$hoy["hours"].":".$hoy["minutes"].":".$hoy["seconds"].".".substr($hoy["0"],0,6));
        }
        else{
          $fecha_actual = ($hoy["year"]."-"."0".$hoy["mon"]."-".$hoy["mday"]." ".$hoy["hours"].":".$hoy["minutes"].":".$hoy["seconds"].".".substr($hoy["0"],0,6));
        }
  

        //dd($fecha_actual);
        //refactorizacion de la obtencion de fecha y hora.
        $time = (Carbon::now()->toDateTimeString());

        
        
        
        
        
        //Array de stock picking seleccionando solo los codigos de las notas de venta. 
        $SELECT_STOCK_PICKING = pg_query($con_InventorySystem, "SELECT SUBSTRING(name,22,10) AS name, state FROM stock_picking WHERE name LIKE '%PICK/NOTA-VENTA/%';");
            while ($row = pg_fetch_assoc($SELECT_STOCK_PICKING)){
                    $array_stock_picking[] = $row;
            }
    
        
        
        
        //Discriminacion por estado de el proceso de "picking" → realizado//por hacer
        $array_unique_stock_picking = array_unique($array_stock_picking, SORT_REGULAR);
        $array_listo    = array();
        $array_no_listo = array();
        
        
        foreach ($array_unique_stock_picking as $key => $value){
        
            $name = $value["name"];
            $posicion_inicial = strpos($name, '[');
            $posicion_final   = strpos($name, ']')+1;
            $name_substring   = "";
                          
            if($array_unique_stock_picking[$key]["state"] == 'done'){
        
                $name_substring   = substr($name, $posicion_inicial,$posicion_final);
                array_push($array_listo, $name_substring);
                $array_unique_listo = array_unique($array_listo);
            }
        
            elseif($array_unique_stock_picking[$key]["state"] == 'assigned'){
        
        
                $name_substring   = substr($name, $posicion_inicial,$posicion_final);
                array_push($array_no_listo, $name_substring);
                $array_unique_no_listo = array_unique($array_no_listo);
            }
        }
        //Fin discriminacion por estado
        
        

    
        
    
        //Gestion sobrecarga de datos

        //Aca se limita el numero de iteraciones que tiene que se tienen que ejecutar.
        $nota_de_venta = $name_substring; 
        if(isset($array_unique_listo) && isset($array_unique_no_listo)){
            //"interset" da error cuando no hay ningun stock-picking
            $intersect  = array_intersect_assoc($array_unique_listo, $array_unique_no_listo);         
            foreach ($array_unique_listo as $key => $value){
        
                $reference = "PICKING-FINAL/NOTA-VENTA/".$value;
                $SELECT = pg_query($con_InventorySystem,"SELECT id FROM stock_picking WHERE name LIKE '%{$reference}%';");
                while($row = pg_fetch_assoc($SELECT)){
                    $array_select [] = $row;
                }
                if(!empty($array_select)){
                    unset($array_unique_listo[$key]);
                    unset($array_select[$key]);
                }
                else{
                    continue;
                    }               
            }//cierre foreach 
        }//cierre if isset de los arrays listos.

        //Fin gestion sobrecarga de datos





     
        
        

                                                                          ##   SCRIPT AUTOMATICO → PICKING-FINAL   ## 
        
        //CODIGO ORIGINAL =  isset($array_unique_listo) OR isset($array_unique_no_listo)  
        if(!empty($array_unique_listo) and !empty($array_unique_no_listo)){


                
            if (!empty($intersect)){
                    var_dump("EL PEDIDO NO SE PUEDE CREAR PUES QUEDAN ORDENES DE PICKING POR EJECUTARDE LA NOTA DE VENTA: →" .$nota_de_venta);
            }
            
            elseif(empty($intersect)){
                        
                #INSERT STOCK PICKING   
                foreach ($array_unique_listo as $key => $i){


                    if(array_search($i, $array_unique_no_listo) == FALSE){

                    var_dump("ESTAN TODOS LOS PEDIDOS LISTOS PARA COMENZAR EL INSERT DEL PICKING-FINAL DE LA NOTA DE VENTA:  → ".$i);
                    $valuex = $i;
                    $name_picking = "PICKING-FINAL/NOTA-VENTA/".$valuex;
                    $nota_de_venta = $valuex;

                    //Selección campo 'origin' en la tabla stock picking → campo referente al folio de la factura.
                    $posicion_primer_corchete = strpos($i,'[') + 1;
                    $posicion_ultimo_corchete = strpos($i,']') + 1;
                    $pre_codigo_nota_de_venta = substr($i,$posicion_primer_corchete, $posicion_ultimo_corchete);
                    $codigo_nota_de_venta = substr($pre_codigo_nota_de_venta, 0, -1);
                    
    
                    
                    $arg_codigo = $codigo_nota_de_venta;
                    $select_id_nota_de_venta = DB::connection('imatronix_ecosa')->table('nota_de_venta')->select('_id')->where('codigo', $arg_codigo)->get();
                    $arg_id = $select_id_nota_de_venta[0]->_id;

                    $pre_orden = DB::connection('imatronix_ecosa')->table('factura')->select('numero')->where('nota_de_venta', $arg_id)->get();

                    $origen = "";
                    if ( !isset($pre_orden[0]->numero) ){
                        $origen = NULL; // Esta parte tiene que refactorizarse para evitar que se inserte el valor null.
                    }
                    else{
                        $origen = $pre_orden[0]->numero;
                    }
                    //Fin selección campo 'origin' en la tabla stock picking → campo referente al folio de la factura.

        
                    $SELECT_ULTIMO_ID_STOCK_PICKING = pg_query($con_InventorySystem, "SELECT id,name FROM stock_picking ORDER BY id DESC LIMIT 1;");
                    while($row = pg_fetch_assoc($SELECT_ULTIMO_ID_STOCK_PICKING)){
                        $array_ultimo_id_stock_picking[] = $row;
                    }
        
                    $ultimo_id_stock_picking = $array_ultimo_id_stock_picking[0]["id"] + 1;
        
        
                    $SELECT_STOCK_PICKING_LIMIT = pg_query($con_InventorySystem, "SELECT name FROM stock_picking WHERE name = '$name_picking';");
                    while($row = pg_fetch_assoc($SELECT_STOCK_PICKING_LIMIT)){
                        $select_stock_picking_limit [] = $row;
                    }
        

                        if(empty($select_stock_picking_limit)){



                            $t = DB::connection('inventory_system')->table('stock_picking')->select('id')->limit(1)->orderBy('id','DESC')->get();
                            $ultimo_id_stock_picking = intval($t[0]->id) + 1;
                            //dd($ultimo_id_stock_picking);


                            //fin restablecimiento

        
                            $INSERT_STOCK_PICKING = pg_query($con_InventorySystem, "INSERT INTO stock_picking (id, name, origin, note, backorder_id, move_type, state, group_id, priority, scheduled_date, date_deadline, has_deadline_issue, date, date_done, location_id, location_dest_id, picking_type_id, partner_id, company_id, user_id, owner_id, printed, is_locked, immediate_transfer, create_uid, create_date, write_uid, write_date, batch_id, message_main_attachment_id) 
                            VALUES($ultimo_id_stock_picking,'$name_picking','$origen',NULL,NULL,'direct','assigned',NULL,0,'$fecha_actual',NULL,'f','$fecha_actual',NULL,19, 22,31,1,1,2,NULL,NULL,'t','f',2,'$fecha_actual',2,'$fecha_actual',NULL,NULL);");
        
                         }
                        else{
                            echo"ERROR EN EL INSERT STOCK PICKING 'PICKING-FINAL' LINE:226";
                            //continue;
                        }          
                    }
                    else{
                        continue;
                    }     
                #FIN INSERT STOCK PICKING   
        
        
      
        
        
                //Seleccion de valores para posterior INSERT de "stock_move"
                $nombre_referencia_normal = "/PICK/NOTA-VENTA/".$nota_de_venta;
                $productos_stock_move = DB::connection('inventory_system')->table('stock_move_line')
                    ->join('product_template', 'stock_move_line.product_id' , '=', 'product_template.id')
                    ->select('product_template.name AS name', 
                             'stock_move_line.product_id AS product_id', 
                             'stock_move_line.qty_done AS qty_done', 
                             'product_template.description_picking AS bodega', 
                             'product_template.uom_id AS uom_id')
                    ->where('stock_move_line.reference', 'LIKE', '%'.$nombre_referencia_normal.'%')
                    ->get();





   
                foreach ($productos_stock_move as $key => $producto){
                
                    $bodega                = $producto->bodega;
                    $nombre_final_producto = $producto->name;
                    $id_final_producto     = $producto->product_id;   
                    $cantidad_final        = $producto->qty_done;
                    $unidad_de_medida      = $producto->uom_id;
        
                    $ubicacion_numero_inicio    =  "";
                    $ubicacion_numero_destino   =  "";
                    $warehouse_id = "";
                    $inventory_id = "";
                    $rule_id = "";
        
        
        
        
        
                    if($bodega == "1ER PISO ECOSA"){
                        $ubicacion_numero_inicio    = 19;
                        $ubicacion_numero_destino   = 22;
                        $warehouse_id               = 2;
                        $inventory_id               = 1;
                        $rule_id = 8;
                    }
                    elseif($bodega == "2DO PISO ECOSA"){
                        $ubicacion_numero_inicio   = 19;
                        $ubicacion_numero_destino  = 22;
                        $warehouse_id              = 3;
                        $inventory_id              = 2;
                        $rule_id = 8;   
                    }
                    elseif($bodega == "1ER PISO TRUPER"){
                        $ubicacion_numero_inicio    = 19;
                        $ubicacion_numero_destino   = 22;
                        $warehouse_id               = 4;
                        $inventory_id               = 3;
                        $rule_id = 8;
                    }
                    elseif($bodega == "2DO PISO TRUPER"){
                        $ubicacion_numero_inicio     = 19;
                        $ubicacion_numero_destino    = 22;
                        $warehouse_id                = 5;
                        $inventory_id                = 4;
                        $rule_id = 8;
                    }
                    elseif($bodega == "1ER PISO PPR ERA"){
                        $ubicacion_numero_inicio    = 19;
                        $ubicacion_numero_destino   = 22;
                        $warehouse_id               = 4;
                        $inventory_id               = 3;
                        $rule_id = 8;
        
                    }
                    elseif($bodega == "2DO PISO PPR ERA"){
                        $ubicacion_numero_inicio    = 19;
                        $ubicacion_numero_destino   = 22;
                        $warehouse_id               = 5;
                        $inventory_id               = 4;
                        $rule_id = 8;
                    }
        


                    // esto lo puedo cambiar por el correlativo.
                    $id_producto_id = $id_final_producto.'111';
                    $id_producto = intval($id_final_producto);
        
        
        
        
//////////////////##FIX MOMENTANIO CON PRODUCTOS QUE NO TIENEN BODEGA////////////////////////////////////////////////////////////SECCION QUE REQUIERE ARREGLO. 
                    if($ubicacion_numero_inicio == ""){
                        $ubicacion_numero_inicio = 19;       
                    }
        
                    if($ubicacion_numero_destino == ""){
                        $ubicacion_numero_destino = 22;        
                    }
        
                    if( $warehouse_id == ""){
                        $warehouse_id = 4; 
                     }
        
                    if($inventory_id  == ""){
                        $inventory_id  = 3; 
                    }        
                    if($rule_id == ""){
                        $rule_id = 8;        
                    }
                    ##FIN FEL FIX        
//////////////////////////////////////////////////////////////////////////////////////////////////////BLOQUE QUE REQUIERE REVISION 
        
        
        
                    #INSERT tabla "stock_quant"
                    $SELECT_STOCK_QUANT = pg_query($con_InventorySystem,"SELECT id FROM stock_quant WHERE id = '$id_producto_id'");
                    while($row = pg_fetch_assoc($SELECT_STOCK_QUANT)){
                        $array_stock_quant_fix [] = $row;
                    }
        
                    if(!empty($array_stock_quant_fix)){
                        //Esta seccion se deja en blanco por si se requiere trabajo en este bloque.
                    }
                    else{
                       // $INSERT_STOCK = pg_query($con_InventorySystem, "INSERT INTO stock_quant(id, product_id, company_id, location_id, lot_id, package_id, owner_id, quantity, reserved_quantity, in_date, create_uid, create_date, write_uid, write_date, removal_date)VALUES('$id_producto_id', '$id_producto', 1, '$ubicacion_numero_inicio', NULL, NULL, 1, '$cantidad_final', 0, '$fecha_actual', 2, '$fecha_actual', 2, '$fecha_actual', NULL);");

                        //$INSERT_STOCK = pg_query($con_InventorySystem, "INSERT INTO stock_quant(product_id, company_id, location_id, lot_id, package_id, owner_id, quantity, reserved_quantity, in_date, create_uid, create_date, write_uid, write_date, removal_date)VALUES('$id_producto', 1, '$ubicacion_numero_inicio', NULL, NULL, 1, '$cantidad_final', 0, '$fecha_actual', 2, '$fecha_actual', 2, '$fecha_actual', NULL);");
                    }
                    #Fin INSERT "stock_quant"
        
        
        
        
        
        
        
        
        
        
        ############################## SECCION QUE REQUIERE REVISION! || PERO SIGUE FUNCIONANDO ASI QUE NO HAY QUE MOVER NADA CSM! 
        
        
        
                    #INSERT tabla "stock_move"
                    $referencia = $name_picking;
                    $SELECT_STOCK_MOVE = pg_query($con_InventorySystem,"SELECT id,name FROM stock_move WHERE reference LIKE '%{$referencia}%' AND name LIKE '%{$nombre_final_producto}%' AND product_id = '$id_final_producto' ");
                    while($row = pg_fetch_assoc($SELECT_STOCK_MOVE)){
                        $array_stock_move_fix [] = $row;
                    }
        
        
        
                    if(!empty($array_stock_move_fix)){
                        //Seccion en blanco por si se requiere trabajo en este bloque
                    }
                    else{
        
    


                    $INSERT_STOCK_MOVE = pg_query($con_InventorySystem, "INSERT INTO stock_move(name, sequence, priority, create_date, date, date_deadline, company_id, product_id, description_picking, product_qty, product_uom_qty, product_uom, location_id, location_dest_id, partner_id, picking_id, note, state, price_unit, origin, procure_method, scrapped, group_id, rule_id, propagate_cancel, delay_alert_date, picking_type_id, inventory_id, origin_returned_move_id, restrict_partner_id, warehouse_id, additional, reference, package_level_id, next_serial, next_serial_count, orderpoint_id, create_uid, write_uid, write_date) 
                    VALUES ('$nombre_final_producto',16,0,'$fecha_actual','$fecha_actual',NULL,1,'$id_final_producto', 'SECCION EMBALAJE', 0, '$cantidad_final', '$unidad_de_medida', '$ubicacion_numero_inicio', '$ubicacion_numero_destino', NULL, '$ultimo_id_stock_picking', NULL, 'confirmed', NULL, NULL, 'make_to_stock', 'f', NULL, '$rule_id', 't', NULL, 31, '$inventory_id', NULL, NULL, '$warehouse_id', 'f', '$name_picking', NULL, NULL, NULL, NULL, 2, 2, '$fecha_actual');");
        
                    }



                    //restablecimiento de los ids
                    $prev = DB::connection('inventory_system')->table('stock_picking')->select('id')->orderBy('id', 'desc')->limit(1)->get();
                    $ULTIMO_ID_AUTOINCREMENT = $prev[0]->id + 1;
                    $text = "ALTER SEQUENCE stock_picking_id_seq RESTART WITH ".strval($ULTIMO_ID_AUTOINCREMENT).";";
                    DB::connection('inventory_system')->update(DB::connection('inventory_system')->raw($text));


                    #Fin INSERT tabla "stock_move"
        


                    $repl =  array("[","]");
                    $argumento = str_replace($repl,"",$nota_de_venta);
                    //$UPDATE_MOVIMIENTOS = pg_query($con_InventorySystem,"UPDATE movimientos SET picking_final = 'realizado' WHERE nota_de_venta = '$argumento';");
                    //$insert_movimientos = pg_query($con_InventorySystem,"INSERT INTO movimientos(nota_de_venta, envio_email_bultos, facturada, picking_final, bultos) VALUES('$argumento','no','no','realizado','sin realizar');");   
        
        
        





                }//cierre foreach stock-move productos 
              } //cierre foreach array productos listos    
            }//cierre elseif de verificacion de definicion de intersect
        }//cierre if verificacion variables definidas array listo y no listo
        
        
        
        

                                                                          ##   FIN SCRIPT AUTOMATICO → PICKING-FINAL   ## 
        
        
        
        






























    
 
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// 
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        
        
        
        
                                                                                
                                                                          ##    SCRIPT AUTOMATICO →  PAQUETE FINAL    ##


        
        
        $SELECT_STOCK_PICKING_FINAL_LISTOS = pg_query($con_InventorySystem,"SELECT id,REPLACE(REPLACE(SUBSTRING(name,26,10),'[',''),']','') AS name FROM stock_picking WHERE name LIKE '%PICKING-FINAL/NOTA-VENTA/%' AND state = 'done' ORDER BY id ASC");
            while($row = pg_fetch_assoc($SELECT_STOCK_PICKING_FINAL_LISTOS)){
                $array_picking_final_listos [] = $row;
            }

        
        if(!empty($array_picking_final_listos)){
            
            #Inicio de eliminacion de paquetes ya creados → Secuencia "paquetes".
            foreach ($array_picking_final_listos as $key => $value) {
            
                $valor_a_eliminar  = $value["name"];    
                $nombre_pack_final =  "BEC1/PALLET/[".strval($valor_a_eliminar)."]";  
            
            
                $SELECT_PACK_FINAL = pg_query($con_InventorySystem, "SELECT SUBSTRING(name,17,10) FROM stock_picking WHERE name = '$nombre_pack_final';");
                while($row = pg_fetch_assoc($SELECT_PACK_FINAL)){
                    $array_pack_final [] = $row;
                }
            
            
                if(!empty($array_pack_final)){
                  unset($array_picking_final_listos[$key]);
                  unset($array_pack_final[$key]);
                }          
              else{
                    continue;
              } 
            } 
            #Fin eliminacion de paquetes ya creados


            //primer ciclo
            foreach ($array_picking_final_listos as $key => $value){

                $id_stock_picking_  = $value["id"];
                $name_stock_picking = $value["name"];


                #RECOPILACION DE DATOS PARA EL INSERT
                $SELECT_PICKING_FINAL_LISTOS = pg_query($con_InventorySystem,"SELECT stock_picking.id AS stock_pickingid, 
                    REPLACE(REPLACE(SUBSTRING(stock_picking.name,26,10),'[',''),']','') AS codigo, 
                    stock_move_line.package_level_id AS package_level_id, 
                    stock_move_line.result_package_id AS package_id, 
                    stock_move_line.product_uom_id AS product_uom, 
                    stock_move_line.qty_done AS cantidad_realizada, 
                    product_template.name AS name_product, 
                    product_template.id AS id_product    
                    FROM stock_picking 
                    JOIN stock_package_level ON stock_picking.id               = stock_package_level.picking_id
                    JOIN stock_move_line  ON stock_package_level.id = stock_move_line.package_level_id
                    JOIN product_template    ON stock_move_line.product_id     = product_template.id
                    WHERE stock_picking.id = '$id_stock_picking_' 
                    AND stock_picking.state = 'done'
                    ORDER BY stock_package_level.id ASC;");
            
                while ($row = pg_fetch_assoc($SELECT_PICKING_FINAL_LISTOS)) {
                    $array_picking_final_listo [] = $row;
                }
                


               if(isset($array_picking_final_listo)) {

                   
            
                #Gestion numeros de paquete creado → Se crea un Array que parta desde el index 1, se elimina la poscion 0 y se agrega el sucesor del ultimo index
                foreach($array_picking_final_listo as $key => $value){        
                    $array_key_package [] = $key;
                    $package_id_array  [] = $value["package_id"];
                }
            
                $max_key_package = MAX($array_key_package);
                unset($array_key_package[0]);
                array_push($array_key_package,$max_key_package+1);
                #Fin gestion orden de paquetes o bultos


                foreach($array_picking_final_listo as $keyy => $value){
            
                    $titulo_paquete_final = "BEC1/PALLET/[".$value["codigo"]."]";
                    $name_product         = $value["name_product"];
                    $id_product           = $value["id_product"];
                    $cantidad_realizada   = $value["cantidad_realizada"];
                    $product_uom          = $value["product_uom"];
                    $stock_pickingid      = $value["stock_pickingid"];
                    $package_level_id     = $value["package_level_id"];
                    $package_id           = $value["package_id"];


                    
                    //numero factura
                    $arg_codigo = $value["codigo"];
                    $select_id_nota_de_venta = DB::connection('imatronix_ecosa')->table('nota_de_venta')->select('_id')->where('codigo', $arg_codigo)->get();
                    $arg_id = $select_id_nota_de_venta[0]->_id;
                    $pre_orden = DB::connection('imatronix_ecosa')->table('factura')->select('numero')->where('nota_de_venta', $arg_id)->get();
                    $origen = "";
                    if ( !isset($pre_orden[0]->numero) ){
                        $origen = 0; // Esta parte tiene que refactorizarse para evitar que se inserte el valor null.
                    }
                    else{
                        $origen = $pre_orden[0]->numero;
                    }
                   //fin numero factura
            
            

                    #INSERT tabla "stock picking"
                    $SELECT_LIMIT = pg_query($con_InventorySystem, "SELECT name FROM stock_picking WHERE name = '$titulo_paquete_final';");
                        while($row = pg_fetch_assoc($SELECT_LIMIT)){
                        $select_limit [] = $row;
                        }
            
                    if(empty($select_limit)){

                        //restablecimiento de los ids
                        $prev = DB::connection('inventory_system')->table('stock_picking')->select('id')->orderBy('id', 'desc')->limit(1)->get();
                        $ULTIMO_ID_AUTOINCREMENT = $prev[0]->id + 1;
                        $text = "ALTER SEQUENCE stock_picking_id_seq RESTART WITH ".strval($ULTIMO_ID_AUTOINCREMENT).";";
                        DB::connection('inventory_system')->update(DB::connection('inventory_system')->raw($text));
                        //fin restablecimiento


            
                        $INSERT_PACKAGE = pg_query($con_InventorySystem,"INSERT INTO stock_picking (message_main_attachment_id, name, origin, note, backorder_id, move_type, state, group_id, priority, scheduled_date, date_deadline, has_deadline_issue, date, date_done, location_id, location_dest_id, picking_type_id, partner_id, company_id, user_id, owner_id, printed, is_locked, immediate_transfer, create_uid, create_date, write_uid, write_date, batch_id)
                        VALUES(NULL, '$titulo_paquete_final' ,$origen,NULL,NULL, 'direct', 'assigned',NULL, 0 , '$fecha_actual',NULL , 'f' , '$fecha_actual' ,NULL,22 ,21 ,9 ,4 ,1 ,2 ,NULL,NULL , 't', 'f',2 , '$fecha_actual' ,2 , '$fecha_actual' ,NULL)");
                    }
                    else{
                        //continue;
                    }
                    #Fin INSERT tabla "stock picking"
            
            
            
            
            
            
            
                    #INSERT tabla "stock_package_level"
            
                    //Obtencion del ultimo id de stock picking con referencia "PACK-FINAL"
                    $ID_REFERENCIA = pg_query($con_InventorySystem,"SELECT id FROM stock_picking WHERE name = '$titulo_paquete_final';");
                    while ($row = pg_fetch_assoc($ID_REFERENCIA)) {
                        $array_referencia [] = $row;
                    }
                    $picking_package_id = $array_referencia[0]["id"];
            
                    
                    //Seleccion ultimo id tabla "stock_package_level"
                    $SELECT_LIMIT = pg_query($con_InventorySystem,"SELECT id FROM stock_package_level ORDER BY id DESC LIMIT 1");
                    while($row = pg_fetch_assoc($SELECT_LIMIT)){
                        $array_limit_id [] = $row;
                    }

                    $id_package_level = $array_limit_id[0]["id"]; 
                    //ultimo id para sumar 1!! 


                    //Este foreach provvocaba problemas en la gestion de paquetes REQUIERE REVISION     
                    foreach (array_unique($package_id_array) as $key => $value) {
                 
                        $id_final_package_level = $id_package_level + $key; 
                        $package_id_ = $value;
                        //$package_id_ = array_unique($package_id_array)[$key];

            
                        $LIMIT_PACKAGE_LEVEL = pg_query($con_InventorySystem,"SELECT id FROM stock_package_level WHERE  package_id = '$package_id_' AND picking_id = '$picking_package_id' ORDER BY id DESC; ");
                        while ($row = pg_fetch_assoc($LIMIT_PACKAGE_LEVEL)){
                            $array_limit_pack [] = $row;
                        }
            
                        if(empty($array_limit_pack)){

                             //restablecimiento de los ids
                             $prev = DB::connection('inventory_system')->table('stock_package_level')->select('id')->orderBy('id', 'desc')->limit(1)->get();
                             $ULTIMO_ID_AUTOINCREMENT = $prev[0]->id + 1;
                             $text = "ALTER SEQUENCE stock_package_level_id_seq RESTART WITH ".strval($ULTIMO_ID_AUTOINCREMENT).";";
                             DB::connection('inventory_system')->update(DB::connection('inventory_system')->raw($text));
                             //fin restablecimiento
            
                            $INSERT_STOCK_PAPACGE_LEVEL = pg_query($con_InventorySystem,"INSERT INTO stock_package_level(package_id, picking_id, location_dest_id, company_id, create_uid, create_date, write_uid, write_date)
                            VALUES('$package_id_', '$picking_package_id' ,21,1,2,'$fecha_actual',2, '$fecha_actual')");
            
                            $DELETE_PACKAGE = pg_query($con_InventorySystem,"DELETE FROM stock_package_level WHERE picking_id IS NULL;");
                        }
                        else{
                            continue;
                            //echo"NO SE EJECUTA INSERT PACKAGE FINAL STOCK MOVE !!";
                        }
                    } //Cierre for erach $array_key_package
                         
                    #FIN DISCRIMINACION INSER PAQUETES!  + INSERT STOCK PACKAGE LEVEL 
            
            
            
        


                    $id_picking = DB::connection('inventory_system')->table('stock_picking')->select('id')->where('name', $titulo_paquete_final)->get();
                    $id_pick = $id_picking[0]->id;

                    #INSERT tabla "stock_move" 
                    $SELECT_ID_PACKAGE_LEVEL = pg_query($con_InventorySystem,"SELECT id FROM stock_package_level WHERE location_dest_id = 21 AND package_id = '$package_id' AND picking_id = '$id_pick' ORDER BY id DESC ");
                    while($row = pg_fetch_assoc($SELECT_ID_PACKAGE_LEVEL)){
                        $array_id_package_level [] = $row;
                    }
            
                    $id_pack_ = $array_id_package_level[$keyy]["id"];
                    
            
                    $LIMIT_MOVE = pg_query($con_InventorySystem,"SELECT id,name FROM stock_move WHERE reference = '{$titulo_paquete_final}'  AND product_id = '{$id_product}' AND package_level_id = '{$id_pack_}' ");
                    while($row = pg_fetch_assoc($LIMIT_MOVE)){
                        $array_limit_move [] = $row;
                    }
            
                    if(empty($array_limit_move)){
            
                        $INSERT_PACKAGE = pg_query($con_InventorySystem, "INSERT INTO stock_move(name, sequence, priority, create_date, date, date_deadline, company_id, product_id, description_picking, product_qty, product_uom_qty, product_uom, location_id, location_dest_id, partner_id, picking_id, note, state, price_unit, origin, procure_method, scrapped, group_id, rule_id, propagate_cancel, delay_alert_date, picking_type_id, inventory_id, origin_returned_move_id, restrict_partner_id, warehouse_id, additional, reference, package_level_id, next_serial, next_serial_count, orderpoint_id, create_uid, write_uid, write_date) 
                        VALUES ('$name_product',10,0,'$fecha_actual','$fecha_actual',NULL,1,'$id_product', '$titulo_paquete_final', 0, '$cantidad_realizada', '$product_uom', 22, 21, NULL, '$picking_package_id', NULL, 'assigned', NULL, NULL, 'make_to_stock', 'f', NULL, NULL, 't', NULL, 9, NULL, NULL, NULL, NULL, 'f', '$titulo_paquete_final', '$id_pack_', NULL, NULL, NULL, 2, 2, '$fecha_actual');");
                    }
                    else{
                        echo"NO SE EJECUTA INSERT PACKAGE FINAL STOCK MOVE REVISAR PEROBLEMA";
                    }
                    #Fin INSERT "stock_move"
        
        
        
    
            } //Cierre foreach "$array_picking_final_listo"
           }    else{ var_dump(" ERROR CUANDO NO HYA PAQUETES → array_picking_final_listo no existe <br>");} //CIERRE IF DE TESTEO 
          }//Cierre if empty "$array_picking_final_listo"
        } //Cierre foreach recopilacion de datos paquetes "$array_picking_final_listos"
        
        
        
        
        /* ESTO DEBE IR AL FINAL! 
          //envio email para avisar de que debe ser facturada la nota de venta   MENSAJE QUE AVISA QUE LOS BULTOS ESTAN CREADOS!  
          $data = substr($name_stock_picking,25,10);
          shell_exec('python C:\xampp\htdocs\Logistica\app\Http\Controllers\envio_email_python.py "'.$data.'" ');
          //fin envio email
        */
        
        
        








































//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////        
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////        
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        
        
                                                                       ##  CREACION ORDEN DE DESPACHO  ##
        
        
        
        
        //$SELECT_NUMERO_NOTA_DE_VENTA = pg_query($con_InventorySystem,"SELECT SUBSTRING(name,17,25) AS codigo FROM stock_picking WHERE name LIKE '%PACK-FINAL/%' and state = 'assigned';");
          $SELECT_NUMERO_NOTA_DE_VENTA = pg_query($con_InventorySystem,"SELECT SUBSTRING(name,26,25) AS codigo FROM stock_picking WHERE name LIKE '%PICKING-FINAL/%' and state = 'done';");
        while($row = pg_fetch_assoc($SELECT_NUMERO_NOTA_DE_VENTA)){
            $array_numeros_nota_de_venta [] = $row;
        }

        
        
        if(!empty($array_numeros_nota_de_venta)){
        
                
            #Se eliminan los "stock picking" que tienen state = done // assigned para ejecutar pruebas.
            foreach ($array_numeros_nota_de_venta as $key => $value) {

                $var = strval($value["codigo"]);
                $var_1 = substr($var,1);
                $var_2 = substr($var_1,0,-1);
                //dd($var_2);

        
                $valor_eliminacion = "BEC1/ORDEN-ENTREGA/[".$var_2."]";
                $SELECT_PACK_FINAL_LIMIT = pg_query($con_InventorySystem, "SELECT id FROM stock_picking WHERE name LIKE '%{$valor_eliminacion}%';");
                while($row = pg_fetch_assoc($SELECT_PACK_FINAL_LIMIT)){
                    $array_pack_final_limit [] = $row;
                }
        
                if(!empty($array_pack_final_limit)){        
                    unset($array_numeros_nota_de_venta[$key]);
                    unset($array_pack_final_limit[$key]);
                } 
                else{
                    continue;
                }    
            }

        ##Fin bloque de eliminacion de vlores con state = "done"
        
        
        
            #Obtencion de notas de ventas facturadas → Servidor 200
            foreach ($array_numeros_nota_de_venta as $key => $value) {

                $codigo_nv = $value["codigo"];
                $var_1 = substr($codigo_nv,1);
                $var_2 = substr($var_1,0,-1);

                $SELECT_NOTA_DE_VENTA_FACTURADA = pg_query($con_200, "SELECT codigo, facturada FROM nota_de_venta WHERE codigo = '{$var_2}';");
                while($row = pg_fetch_assoc($SELECT_NOTA_DE_VENTA_FACTURADA)){ 
                    $array_facturada [] = $row;
                }
            }
            #Fin obtencion notas de venta facturadas
        
        
        
            if(!empty($array_facturada)){
                   
                foreach ($array_facturada as $key => $numero){
        
                    $facturada  = $numero["facturada"];
                    $codigo_ndv = $numero["codigo"];
                    $titulo_orden_de_entrega = "BEC1/ORDEN-ENTREGA/"."[".strval($numero["codigo"])."]";
                    $titulo_picking_final = "PICKING-FINAL/NOTA-VENTA/"."[".strval($numero["codigo"])."]";


                    //Obtención del pesaje final
                    $select_nota_stock_picking_final = DB::connection('inventory_system')->table('stock_picking')->select('note')->where('name',$titulo_picking_final)->get();

                    $string_nota = ($select_nota_stock_picking_final[0]->note);
                    $array_string_nota = explode("\n", $string_nota);

                    $array_suma_pesaje = [];
                    foreach ($array_string_nota as $key => $value) {

                        if(strlen($value) > 0){

                            $inicio     = strpos($value, '=');
                            $final      = strpos($value, ' KG');
                            $prevalor   = substr($value,intval($inicio)+1,intval($final));
                            $prevalor_2 = str_replace("KG", "", $prevalor);
                            $prevalor_3 = preg_replace("/\s+/", "", $prevalor_2);
                            $valor      = str_replace(",", ".", $prevalor_3);
                            if($valor == 'X'){
                                $valor = 0;
                            }
                            array_push($array_suma_pesaje, floatval($valor));
                        }
                    }
                    $pesaje_pedido = (array_sum($array_suma_pesaje));
                    $cantidad_bultos = (count($array_suma_pesaje));
                    //Fin obtencion pesaje final




                    $arg_codigo = $codigo_ndv;
                    $select_id_nota_de_venta = DB::connection('imatronix_ecosa')->table('nota_de_venta')->select('_id')->where('codigo', $arg_codigo)->get();
                    $arg_id = $select_id_nota_de_venta[0]->_id;
                    $pre_orden = DB::connection('imatronix_ecosa')->table('factura')->select('numero')->where('nota_de_venta', $arg_id)->get();
                    $origen = "";
                    if ( !isset($pre_orden[0]->numero) ){
                        $origen = NULL; // Esta parte tiene que refactorizarse para evitar que se inserte el valor null.
                    }
                    else{
                        $origen = $pre_orden[0]->numero;
                    }
                    //Fin selección campo 'origin' en la tabla stock picking → campo referente al folio de la factura.

                    

                    //INVERTIR IF AL TERMINAR EL ESTA SECCION DEL CODIGO. ""FACTURADA == NULL""  ""FACTURADA == 1"" COMO INTEGER  aca me da el valor.
                    if($facturada == 1){

        
        
                        #SECCION QUE TIENE QUE IR A LA API PYTHON 
                        //$arg = $codigo_ndv;
                        //shell_exec('python C:\xampp\htdocs\Logistica\app\Http\Controllers\envio_email_facturada.py "'.$arg.'" ');
                        //$UPDATE_EMAIL = pg_query($con_InventorySystem,"UPDATE movimientos SET facturada = 'si' WHERE nota_de_venta = '$arg';");
                        ##FIN SECCION API! 
        

        
                        ##INSERT tabla "stock_picking"
                        $SELECT_LIMIT_ORDENES_ENTREGA = pg_query($con_InventorySystem,"SELECT id FROM stock_picking WHERE name = '$titulo_orden_de_entrega';");
                        while($row = pg_fetch_assoc($SELECT_LIMIT_ORDENES_ENTREGA)){
                            $array_limit_ordenes_entrega [] = $row;
                        }


        
        
                        if(empty($array_limit_ordenes_entrega)){


                            echo("INSERT ORDEN DE DESPACHO EN PROCESO → NV N° ".$codigo_ndv);
                            $INSERT_ORDEN_ENTREGA = pg_query($con_InventorySystem,"INSERT INTO stock_picking (message_main_attachment_id, name, origin, note, backorder_id, move_type, state, group_id, priority, scheduled_date, date_deadline, has_deadline_issue, date, date_done, location_id, location_dest_id, picking_type_id, partner_id, company_id, user_id, owner_id, printed, is_locked, immediate_transfer, create_uid, create_date, write_uid, write_date, batch_id)
                            VALUES(NULL, '$titulo_orden_de_entrega' ,'$origen',NULL,NULL, 'direct', 'assigned',NULL, 0 , '$fecha_actual',NULL , 'f' , '$fecha_actual' ,NULL,21 ,5 ,7 ,3 ,1 ,2 ,NULL,NULL , 't', 'f',2 , '$fecha_actual' ,2 , '$fecha_actual' ,NULL)");
                        }
                        else{
                            continue;
                        }
                        #Fin INSERT tabla "stock_picking"
        
                
        

                        #Bloque de codigo donde se crea un paquete simbolico con la siguiente syntaxis "PACK-FINAL/" 
                        #Esto se hace por que el "PACK-FINAL/" no puede ser agregado en la seccion de paquetes de la secuencia "ORDEN-ENTREGA/" 
                        #A PARTIR DE ACA PARTE UNA DE LAS ACCIONES CLAVE DEL SISTEMA.

        
                        $ULTIMO_ID_ORDEN_DESPACHO = pg_query($con_InventorySystem,"SELECT id FROM stock_picking WHERE name = '$titulo_orden_de_entrega';");
                        while($row = pg_fetch_assoc($ULTIMO_ID_ORDEN_DESPACHO)){
                            $array_ultimo_id_despacho [] = $row;
                        }
                
        
                        $SELECT_ULTIMO_ID_QUANT_PACKAGE = pg_query($con_InventorySystem,"SELECT id FROM stock_quant_package ORDER BY id DESC LIMIT 1;");
                        while($row = pg_fetch_assoc($SELECT_ULTIMO_ID_QUANT_PACKAGE)){
                            $array_ultimo_id_quant_package [] = $row;
                        }
        
        
                        $SELECT_ULTIMO_ID_PACKAGE_LEVEL = pg_query($con_InventorySystem,"SELECT id FROM stock_package_level ORDER BY id DESC LIMIT 1;");
                        while($row = pg_fetch_assoc($SELECT_ULTIMO_ID_PACKAGE_LEVEL)){
                            $array_ultimo_id_package_level [] = $row;
                        }
        
        
                        $SELECT_ID_ORDEN_ENTREGA = pg_query($con_InventorySystem,"SELECT id FROM stock_picking WHERE name = '$titulo_orden_de_entrega';");
                        while($row = pg_fetch_assoc($SELECT_ID_ORDEN_ENTREGA)){
                            $array_ultimo_id_orden_entrega [] = $row;
                        }
        
        
        
        
        
                        //VALORES PARTA EJECUTAR LOS INSERT! 
                        $codigo_ndv_paquete = str_replace("-","",$codigo_ndv);
                        $id_orden_despacho      = $array_ultimo_id_despacho[0]["id"];
                        $package_id_despacho    = "";
                        $nombre_paquete_final   = "BEC1/PALLET/[".$codigo_ndv."]"; 
                        $id_stock_quant_package = intval($array_ultimo_id_quant_package[0]["id"]) + 1;
                        $id_package_level       = intval($array_ultimo_id_package_level[0]["id"]) + 1; 
                        $id_orden_entrega       = intval($array_ultimo_id_orden_entrega[0]["id"]);        
        
        
        
                        #INSERT tabla "stock_quant_package"
                        $INSERT_STOCK_QUANT_PACKAGE = pg_query($con_InventorySystem,"INSERT INTO stock_quant_package(id, name, packaging_id, location_id, company_id, create_uid, create_date, write_uid, write_date) 
                        VALUES('$id_stock_quant_package','$nombre_paquete_final',NULL,22,1,2,'$fecha_actual',2,'$fecha_actual')");

                        //Restablecimiento de id de la tabla "stock_move"
                        $prev = DB::connection('inventory_system')->table('stock_quant_package')->select('id')->orderBy('id', 'desc')->limit(1)->get();
                        $ULTIMO_ID_AUTOINCREMENT = $prev[0]->id + 1;
                        $text = "ALTER SEQUENCE stock_quant_package_id_seq RESTART WITH ".strval($ULTIMO_ID_AUTOINCREMENT).";";
                        DB::connection('inventory_system')->update(DB::connection('inventory_system')->raw($text));
                        //Fin restablecimiento de la tabla "stock_move"
        
        
                        #INSERT tabla "stock_package_level"
                        $INSERT_STOCK_PACKAGE_LEVEL = pg_query($con_InventorySystem,"INSERT INTO stock_package_level( id, package_id, picking_id, location_dest_id, company_id, create_uid, create_date, write_uid , write_date) 
                        VALUES('$id_package_level','$id_stock_quant_package','$id_orden_entrega',22,1,2,'$fecha_actual',2,'$fecha_actual')");
        

                        //$nota = $nombre_paquete_final." Cantidad bultos= ".strval($cantidad_bultos_final)."\n"." ".str_replace(",","\n ",$nombre_bultos)."\n"."PESAJE BULTOS = ".strval($pesaje_pedido);
                        $nota = $nombre_paquete_final." Cantidad bultos= ".strval($cantidad_bultos)."\n".$string_nota."\n"."PESAJE BULTOS = ".strval($pesaje_pedido);
                        $UPDATE_ORDEN_DE_ENTREGA = pg_query($con_InventorySystem,"UPDATE stock_picking SET note = '$nota' WHERE name = '$titulo_orden_de_entrega';");
        


                        $id_stock_picking = DB::connection('inventory_system')->table('stock_picking')->select('id')->where('name',$titulo_orden_de_entrega)->get();
                        $referencia = "BEC1/PALLET/[".$codigo_ndv."]";
                        $productos_orden_despacho = DB::connection('inventory_system')->table('stock_move')->select('*')->where('reference',$referencia)->get();



                        foreach($productos_orden_despacho as $key => $value){

                            $nombre_package = DB::connection('inventory_system')->table('stock_quant')->join('stock_quant_package','stock_quant.package_id','=','stock_quant_package.id')->select('stock_quant_package.name')->where('stock_quant.product_id',$value->product_id)->get();

                            
                            $nombre_producto = $value->name;
                            $id_producto = $value->product_id;
                            $producto_uom_qty = $value->product_uom_qty;
                            $picking_id = $id_stock_picking[0]->id;
                            $description_picking = $titulo_orden_de_entrega."//".$nombre_package[0]->name;
                            $product_uom = $value->product_uom;
                            $location_id = $value->location_id;
                            $inventory_id = $value->inventory_id;
                            $warehouse_id = $value->warehouse_id;
                            $reference = "BEC1/ORDEN-ENTREGA/[".$codigo_ndv."]";
                            $package_level_id = $id_package_level;







                            //insert stock_move del paquete final.   OJO CON EL CAMPO "note".
                            DB::connection('inventory_system')->table('stock_move')->insert([
                                'name' => $nombre_producto,
                                'sequence' => 15,
                                'priority' => "0",
                                'create_date' => $fecha_actual,
                                'date' => $fecha_actual,
                                'date_deadline' => null,
                                'company_id' => 1,
                                'product_id' => $id_producto,
                                'description_picking' => $description_picking,
                                'product_qty' => null,
                                'product_uom_qty' => $producto_uom_qty,
                                'product_uom' => $product_uom,
                                'location_id' => 21,
                                'location_dest_id' => 5,
                                'partner_id' => null,
                                'picking_id' => $picking_id,
                                'note' => null,
                                'state' => "assigned",
                                'price_unit' => null,
                                'origin' => null,
                                'procure_method' => "make_to_stock", 
                                'scrapped' => 'f',
                                'group_id' => null,
                                'rule_id' => 10,
                                'propagate_cancel' => "true",
                                'delay_alert_date' => null,
                                'picking_type_id' => 7,
                                'inventory_id' => $inventory_id,
                                'origin_returned_move_id' => null,                          
                                'restrict_partner_id' => null,
                                'warehouse_id' => $warehouse_id,
                                'additional' => "false",
                                'reference' => $reference,
                                'package_level_id' => $package_level_id,
                                'next_serial' => null,
                                'next_serial_count' => null,
                                'orderpoint_id' => null,
                                'create_uid' => 2,
                                'write_uid' => 2,
                                'write_date' => $fecha_actual
                            ]);





                            $move_id_query = DB::connection('inventory_system')->table('stock_move')->select('id')->where('picking_id',$picking_id)->where('product_id', $id_producto)->get();
                            $move_id = $move_id_query[0]->id;

                            $product_location_query = DB::connection('inventory_system')->table('stock_quant')->select('location_id')->where('product_id', $id_producto)->orderBy('id', 'asc')->limit(1)->get();
                            $product_location = $product_location_query[0]->location_id;                         

                            //insert stock_move del paquete final.   OJO CON EL CAMPO "note".
                            DB::connection('inventory_system')->table('stock_move_line')->insert([  
                            'picking_id' => $picking_id,
                            'move_id' => $move_id, 
                            'company_id' => 1, 
                            'product_id' => $id_producto,
                            'product_uom_id' => $product_uom, 
                            'product_qty' => 0, 
                            'product_uom_qty' => $producto_uom_qty, 
                            'qty_done' => $producto_uom_qty,
                            'package_id' => null,
                            'package_level_id' => $package_level_id,  
                            'lot_id' => null, 
                            'lot_name' => null, 
                            'result_package_id' => $id_stock_quant_package, 
                            'date' => $fecha_actual,        
                            'owner_id' => 1, 
                            'location_id' => $product_location, 
                            'location_dest_id' => 5, 
                            'state' => 'assigned', 
                            'reference' => $reference,              
                            'description_picking' => null, 
                            'create_uid' => 2, 
                            'create_date' => $fecha_actual,         
                            'write_uid' => 2, 
                            'write_date' => $fecha_actual,         
                            'expiration_date' => null       
                            ]);



                        }
 


        
        

                    }//cierre if  facturada
                    else{
                        //continue;
                        echo"<br><br> LA NOTA DE VENTA N° ".$codigo_ndv." NO ESTÁ FACTURADA <br><br>";
                    }     
                }//cierre foreach array_facturada
            }//cierre if !empty array_facturada
        }//cierre if limitante ordenes de entrega
        


















        return "AJAX STATUS EJECUTADO: STATUS 200  ".$fecha_actual;
    }//cierre funcion ajax_stats


































    public function carga_inicial(){


        /*
            CODIGO MAESTRO PARA LA CARGA INICIAL DEL SERVIDOR 154 "Inventory_System" 
            ULTIMA MODIFICACIÓN 02 DICIEMBRE 2021
            ECO S.A
            FRANCO CUMPLIDO
        */


        #Conexion a servidores 200 y 154

        //Servidor 154
        $host_InventorySystem = "192.168.0.154";
        $puerto_Inventory_System= "5432";
        $dbname_Inventory_System = "InventorySystem";
        $dbsuer_InventorySystem = "postgres";
        $userpass_InventorySystem = "";
        $con_InventorySystem = pg_connect("host=$host_InventorySystem port=$puerto_Inventory_System dbname=$dbname_Inventory_System user=$dbsuer_InventorySystem password=$userpass_InventorySystem");


        //Servidor 200
        $host_200 = "192.168.0.200";
        //$port= "5432";
        $dbname_200 = "imatronix_ecosa";
        $dbuser_200 = "postgres";
        $userpass_200 = "";
        $con_200 = pg_connect("host=$host_200 dbname=$dbname_200 user=$dbuser_200 password=$userpass_200");

        #Fin bloque conexión servidores 200 - 154





        #Fecha actual
        $hoy = getdate();
        $fecha_actual ="";
        if($hoy["mon"] > 9 AND $hoy["mon"] <= 13  ){
            $fecha_actual = ($hoy["year"]."-".$hoy["mon"]."-".$hoy["mday"]." ".$hoy["hours"].":".$hoy["minutes"].":".$hoy["seconds"].".".substr($hoy["0"],0,6));
        }
        else{
            $fecha_actual = ($hoy["year"]."-"."0".$hoy["mon"]."-".$hoy["mday"]." ".$hoy["hours"].":".$hoy["minutes"].":".$hoy["seconds"].".".substr($hoy["0"],0,6));
        }




        #Funciones maestras para los INSERT
        ##FUNCION MAERSTRA CON TODOS LOS DATOS SOLICITADOS.
        function INSERT($id_producto, $descripcion_producto, $precio, $unidad_de_medida, $codigo_de_origen, $fecha, $ubicacion_comentario, $codigo_barras,$stock_disponible_final, $inventory_id, $warehouse_id, $costo_producto,$ubicacion_numero ){

            if($stock_disponible_final == 0 OR $stock_disponible_final == "" OR $stock_disponible_final == NULL OR $stock_disponible_final == " "){
                $stock_disponible_final = 1;
            }
            
            //servidor 154
            $host_InventorySystem = "192.168.0.154";
            $puerto_Inventory_System= "5432";
            $dbname_Inventory_System = "InventorySystem";
            $dbsuer_InventorySystem = "postgres";
            $userpass_InventorySystem = "";
            $con_InventorySystem = pg_connect("host=$host_InventorySystem port=$puerto_Inventory_System dbname=$dbname_Inventory_System user=$dbsuer_InventorySystem password=$userpass_InventorySystem");
    
    
            #Restablecimiento del autoincrement de la tabla "stock_move"
            $UPDATE_AUTOINCREMENT = pg_query($con_InventorySystem, "SELECT id FROM stock_move ORDER BY id DESC LIMIT 1;");
            while($row = pg_fetch_assoc($UPDATE_AUTOINCREMENT)){
                $ULTIMATE_ID_AUTOINCREMENT [] = $row;
            }
   
            if(isset($ULTIMATE_ID_AUTOINCREMENT)){
                foreach ($ULTIMATE_ID_AUTOINCREMENT as $key => $value) {
                    $ARRAY_ID_AUTOINCREMENT [] = $value;
                }
                
                $ULTIMO_ID_AUTOINCREMENT_PREVIO = $ARRAY_ID_AUTOINCREMENT[0]["id"];
                $ULTIMO_ID_AUTOINCREMENT = $ULTIMO_ID_AUTOINCREMENT_PREVIO + 1;
                $ACTUALIZAR_ID_AUTOINCREMENTO = pg_query($con_InventorySystem, "ALTER SEQUENCE stock_move_id_seq RESTART WITH $ULTIMO_ID_AUTOINCREMENT;");
            }
            #Fin restablecimiento. 


    
            $SELECT_FIX_1 = pg_query($con_InventorySystem,"SELECT * FROM product_template WHERE id = '{$id_producto}';");
            while($row = pg_fetch_assoc($SELECT_FIX_1)){
                $array_fix1 [] = $row;
            }
    
    
    
            if(!empty($array_fix1)){
                //echo"NO SE EJECUTA STOCK TEMPLATE";
                //continue;
            }
            else{
                //aca va el llamado a mi api!!
                $INSERT_PRODUCT_TEMPLATE = pg_query($con_InventorySystem, "INSERT INTO product_template (id, message_main_attachment_id, name, sequence, description, description_purchase, description_sale, type, categ_id, list_price, volume, weight, sale_ok, purchase_ok, uom_id, uom_po_id, company_id , active , color , default_code , can_image_1024_be_zoomed , has_configurable_attributes , create_uid ,create_date, write_uid, write_date, sale_delay, tracking, description_picking, description_pickingout, description_pickingin, use_expiration_date , expiration_time, use_time, removal_time , alert_time) 
                VALUES ('$id_producto', NULL, '$descripcion_producto' , 1 ,'$codigo_de_origen', NULL, NULL, 'product', 1 , '$precio', 0.00 , 0.00 , 't', 't', '$unidad_de_medida', 1, NULL, 't', NULL, NULL, 'f', 'f', 2 , '$fecha' , 2, '$fecha' ,0, 'none', '$ubicacion_comentario' , '$ubicacion_comentario' , '$ubicacion_comentario' , 'f' ,0 , 0 , 0 , 0);");
            }
    
    
    
    
            $SELECT_FIX_2 = pg_query($con_InventorySystem,"SELECT * FROM product_product WHERE id = '$id_producto';");
            while($row = pg_fetch_assoc($SELECT_FIX_2)){
                $array_fix2 [] = $row;
            }
    
            if(!empty($array_fix2)){
                //echo"NO SE EJECUTA PRODUCT PRODUCT";
            }
            else{
                var_dump($descripcion_producto);
                $INSERT_PRODUCT_PRODUCT = pg_query($con_InventorySystem, "INSERT INTO product_product (id, message_main_attachment_id, default_code, active, product_tmpl_id, barcode, combination_indices, volume, weight, can_image_variant_1024_be_zoomed, create_uid, create_date, write_uid, write_date) VALUES ('$id_producto', NULL,NULL,'t','$id_producto', '$codigo_barras',NULL,NULL,NULL,'f',2,'$fecha',2,'$fecha');");
            }
    
    
    
            $SELECT_FIX_3 = pg_query($con_InventorySystem,"SELECT * FROM stock_quant WHERE id = '$id_producto';");
            while($row = pg_fetch_assoc($SELECT_FIX_3)){
                $array_fix3 [] = $row;
            }
    
            if(!empty($array_fix3)){    
                //echo"NO SE EJECUTA STOCK QUANT";
            }
            else{
                if($ubicacion_numero == 0){
                    $ubicacion_comentario = "1ER PISO PPR ERA";
                    $ubicacion_numero     = 30;
                    $warehouse_id         = 4;
                    $inventory_id         = 3;
                }



                $INSERT_STOCK_QUANT = pg_query($con_InventorySystem, "INSERT INTO stock_quant(product_id, company_id, location_id, lot_id, package_id, owner_id, quantity, reserved_quantity, in_date, create_uid, create_date, write_uid , write_date, removal_date) VALUES('$id_producto',1,'$ubicacion_numero',NULL,NULL,1,'$stock_disponible_final',0,'$fecha',2,'$fecha',2,'$fecha',NULL);");
            }
    
    
    
            $INSERT_STOCK_MOVE = pg_query($con_InventorySystem,"INSERT INTO stock_move (name, sequence, priority, create_date, date, date_deadline, company_id, product_id, description_picking, product_qty, product_uom_qty, product_uom, location_id, location_dest_id, partner_id, picking_id, note, state, price_unit, origin, procure_method, scrapped, group_id, rule_id, propagate_cancel, delay_alert_date, picking_type_id, inventory_id, origin_returned_move_id, restrict_partner_id, warehouse_id, additional, reference, package_level_id, next_serial, next_serial_count, orderpoint_id, create_uid, write_uid, write_date) 
            VALUES('Cantidad de producto actualizada',10,0,'$fecha','$fecha',NULL,1,'$id_producto','$ubicacion_comentario','$stock_disponible_final','$stock_disponible_final','$unidad_de_medida',2,'$ubicacion_numero',NULL,NULL,NULL,'assigned',100.00,NULL,'make_to_stock','f',NULL, NULL, 't', NULL, NULL, '$inventory_id', NULL,NULL,'$warehouse_id','f','Cantidad de producto actualizada',NULL,NULL,NULL,NULL,2,2,'$fecha');");
    
    

            $SELECT_FIX_4 = pg_query($con_InventorySystem,"SELECT id FROM ir_property WHERE id = '$id_producto';");
            while($row = pg_fetch_assoc($SELECT_FIX_4)){
                $array_fix4 [] = $row;
            }
    
            if(!empty($array_fix4)){
                //echo"NO SE EJECUTA STOCK QUANT";
            }
            else{
                $INSERT_STANDARD_PRICE = pg_query($con_InventorySystem, "INSERT INTO ir_property (id ,name ,res_id, company_id , fields_id , value_float , value_integer , value_text , value_binary, value_reference, value_datetime, type, create_uid, create_date, write_uid, write_date) 
                VALUES('$id_producto', 'standard_price',CONCAT('product.product,','$id_producto'),1, 2714, '$costo_producto', NULL,NULL,NULL,NULL,NULL, 'float', 2, '$fecha',2,'$fecha');");
            }
    
    }//Fin funciones para ejecutar los INSERT






        function ubicacion_especifica($codigo_de_origen, $ubicacion_numero, $fecha_actual, $id_producto, $tabla_db, $marca_producto){

            
            //obtencion del codigo del producto
            $cod_prod = DB::connection('imatronix_ecosa')->table('producto')->select('codigo')->where('_id', $id_producto)->get();
            $codigo_producto = $cod_prod[0]->codigo;
            //fin obtencion codigo


            //restablecimiento de los ids
            $prev = DB::connection('inventory_system')->table('stock_location')->select('id')->orderBy('id', 'desc')->limit(1)->get();
            $ULTIMO_ID_AUTOINCREMENT = $prev[0]->id + 1;
            $text = "ALTER SEQUENCE stock_location_id_seq RESTART WITH ".strval($ULTIMO_ID_AUTOINCREMENT).";";
            DB::connection('inventory_system')->update(DB::connection('inventory_system')->raw($text));
            //fin restablecimiento


            // Solución a ingreso de productos PPR & ERA. 
            $ubicaciones = "";
            if($marca_producto == "PPR & ERA"){
                $select_id = DB::connection('imatronix_ecosa')->table('producto')->select('codigo')->where('_id', $id_producto)->get();
                $codigo_de_origen = str_replace('-','&',strval($select_id[0]->codigo));
                $ubicaciones = DB::connection('inventory_system')->table($tabla_db)->where('producto',$codigo_de_origen)->get();
            }
            else{
                $ubicaciones = DB::connection('inventory_system')->table($tabla_db)->where('producto',$codigo_de_origen)->get();
            }


             //acá tiene que ir la logica.


            if($ubicaciones->isEmpty()){

                $stock_bodega = "";
                $bodega= $ubicacion_numero - 1;
                if($ubicacion_numero == 30){
                    $stock_bodega = "TRP1/Stock/";
                }
                elseif($ubicacion_numero == 36){
                    $stock_bodega = "TRP2/Stock/";
                }
                //$complete_name = $stock_bodega.$ubicacion;
                $complete_name = $codigo_producto;


                //id stock_location
                $ultimo_id_stock_location_select = DB::connection('inventory_system')->table('stock_location')->select('id')->orderBy('id', 'desc')->limit(1)->get();
                $ultimo_id_stock_location = intval($ultimo_id_stock_location_select[0]->id) + 1;



                //insert stock_location
                DB::connection('inventory_system')->table('stock_location')->insert([
                    'name' => $codigo_producto,
                    'complete_name' => $complete_name,
                    'active' => 't',
                    'usage' => 'internal',
                    'location_id' => $ubicacion_numero,
                    'comment' => 'UBICACION DE RECOGIDA',
                    'posx' => 0,
                    'posy' => 0,
                    'posz' => 0,
                    'parent_path' => "1/".$bodega."/".$ubicacion_numero."/".$ultimo_id_stock_location."/",
                    'company_id' => 1,
                    'scrap_location' => 'f',
                    'return_location' => 'f',
                    'removal_strategy_id' => NULL,
                    'barcode' => NULL,
                    'create_uid' => 2,
                    'create_date' => $fecha_actual,
                    'write_uid' => 2,
                    'write_date' => $fecha_actual,
                    'is_zone' => 't',
                    'zone_location_id' => NULL,
                    'area_location_id' => NULL,
                    'location_kind' => 'zone'                            
                ]);

            

                DB::connection('inventory_system')->table('stock_location')->where('id', $ultimo_id_stock_location)->update(['zone_location_id' => $ultimo_id_stock_location]);
                DB::connection('inventory_system')->table('stock_quant')->where('product_id', $id_producto)->update(['location_id' => $ultimo_id_stock_location]);
            }



            foreach($ubicaciones as $key => $value){

                //parametros
                $seccion  = $value->seccion;
                $pasillo  = $value->pasillo;
                $fila     = $value->fila;
                $columna  = $value->columna;
                $nivel    = $value->nivel;
                $producto = $value->producto;

                
                if($tabla_db == 'segundo_piso_truper'){
                    $producto = str_replace('&', '-', $producto); 
                }




                $ubicacion = "SECCION=".$seccion." PASILLO=".$pasillo." FILA=".$fila." COLUMNA=".$columna." NIVEL=".$nivel; 



                $stock_bodega = "";
                $bodega= $ubicacion_numero - 1;
                if($ubicacion_numero == 30){
                    $stock_bodega = "TRP1/Stock/";
                }
                elseif($ubicacion_numero == 36){
                    $stock_bodega = "TRP2/Stock/";
                }
                //$complete_name = $stock_bodega.$ubicacion;
                $complete_name = $codigo_producto."/".$ubicacion;

                


 
                //id stock_location
                $ultimo_id_stock_location_select = DB::connection('inventory_system')->table('stock_location')->select('id')->orderBy('id', 'desc')->limit(1)->get();
                $ultimo_id_stock_location = intval($ultimo_id_stock_location_select[0]->id) + 1;



                //insert stock_location
                DB::connection('inventory_system')->table('stock_location')->insert([
                    'name' => $ubicacion,
                    'complete_name' => $complete_name,
                    'active' => 't',
                    'usage' => 'internal',
                    'location_id' => $ubicacion_numero,
                    'comment' => 'UBICACION DE RECOGIDA',
                    'posx' => 0,
                    'posy' => 0,
                    'posz' => 0,
                    'parent_path' => "1/".$bodega."/".$ubicacion_numero."/".$ultimo_id_stock_location."/",
                    'company_id' => 1,
                    'scrap_location' => 'f',
                    'return_location' => 'f',
                    'removal_strategy_id' => NULL,
                    'barcode' => NULL,
                    'create_uid' => 2,
                    'create_date' => $fecha_actual,
                    'write_uid' => 2,
                    'write_date' => $fecha_actual,
                    'is_zone' => 't',
                    'zone_location_id' => NULL,
                    'area_location_id' => NULL,
                    'location_kind' => 'zone'                            
                ]);

            

                DB::connection('inventory_system')->table('stock_location')->where('id', $ultimo_id_stock_location)->update(['zone_location_id' => $ultimo_id_stock_location]);
                DB::connection('inventory_system')->table('stock_quant')->where('product_id', $id_producto)->update(['location_id' => $ultimo_id_stock_location]);


                //podria poner un update de las cantidades movidas.
            
                    
            }

        }//fin funcion ubicacion especifica


        #Fin funciones maestras para los INSERT






        if (!$con_InventorySystem OR !$con_200) {
            die('ERROR EN LA CONEXIÓN A LAS BASES DE DATOS, COMUNICAR AL DEPARTAMENTO DE INFORMATICA.'.pg_last_error());
        }
        else{
            echo ("CONEXIÓN A BASE DE DATOS 192.168.0.176. EJECUTADA!  ---->   CONEXIÓN A BASE DE DATOS 192.168.0.200. EJECUTADA!"."<br>");




            //if(isset($_REQUEST["boton_actualizar_producto"])){

            #Seleccion de productos bodega ECOSA
            $datos_productos_200 = pg_query($con_200, "SELECT DISTINCT 
            producto._id AS _id, 
            producto.codigo AS codigo, 
            producto.descripcion AS nombre_producto, 
            producto.descripcion_de_origen, 
            producto.precio_base_pesos AS precio, 
            producto.unidad_de_medida, 
            producto.stock_fisico, 
            producto.stock_minimo, 
            producto.costo_moneda_extranjera, 
            proveedor.razon_social AS razon_social, 
            proveedor.marca, 
            proveedor.codigo AS codigo_proveedor,
            producto.codigo_de_origen 
            FROM producto 
            JOIN proveedor ON producto.codigo_proveedor = proveedor.codigo 
            WHERE razon_social NOT LIKE '%TRUPER%'
            AND proveedor.marca <> 'ERA' 
            AND razon_social NOT LIKE '%PPR%'
            AND razon_social NOT LIKE '%PALEOLOGOS%' 
            AND producto.historico IS NULL
            AND producto.eliminado IS NULL
            OR producto.historico = '0' 
            ORDER BY producto._id ASC;");

            while($row = pg_fetch_assoc($datos_productos_200)){
                $productos_200[]     = $row;
                $array_productos_200 = array_reverse($productos_200 ,true);
            }




            //EL ERROR VIENIA DESDE EL PRINCIPIO, EL PROFESOR TENIA RAZÓN

            #Seleccion de prodcutos bodega TRUPER
            $datos_productos_truper = pg_query($con_200, "SELECT 
            producto._id AS _id,
            producto.codigo, 
            producto.descripcion AS nombre_producto, 
            producto.descripcion_de_origen, 
            producto.precio_base_pesos AS precio, 
            producto.unidad_de_medida, 
            producto.stock_fisico, 
            producto.stock_minimo, 
            producto.costo_moneda_extranjera, 
            proveedor.codigo    AS codigo_proveedor, 
            proveedor.razon_social AS razon_social, 
            proveedor.marca     AS proveedor_marca,
            producto.eliminado  AS producto_eliminado,
            producto.historico  AS producto_historico,
            codigos_de_barra_truper.codigo_de_barras,
            producto.codigo_de_origen AS codigo_de_origen
            FROM producto 
            JOIN proveedor ON producto.codigo_proveedor = proveedor.codigo
            JOIN codigos_de_barra_truper 
            --ON producto.codigo_de_origen = codigo_prod_truper
            ON CAST(RTRIM(LTRIM(codigo_de_origen)) AS varchar) = codigos_de_barra_truper.codigo_prod_truper   
            WHERE razon_social LIKE '%TRUPER%'
            ORDER BY producto._id ASC;");

            while ($row = pg_fetch_assoc($datos_productos_truper)) {
                $productos_truper1 []    = $row;
                 //$array_productos_truper = array_reverse($productos_truper, true); 
            }

            

//Este codigo tiene que morir pronto, por que estos productos corresponden a BODEGA ECOSA.
            $mangueras_tarugos_brochas_rodillos_truper = pg_query($con_200,"SELECT 
            producto._id AS _id,
            producto.codigo, 
            producto.descripcion AS nombre_producto, 
            producto.descripcion_de_origen, 
            producto.precio_base_pesos AS precio, 
            producto.unidad_de_medida, 
            producto.stock_fisico, 
            producto.stock_minimo, 
            producto.costo_moneda_extranjera,
            proveedor.codigo    AS codigo_proveedor, 
            proveedor.razon_social AS razon_social, 
            proveedor.marca     AS proveedor_marca,
            producto.eliminado  AS producto_eliminado,
            producto.historico  AS producto_historico,
            producto.codigo_de_origen AS codigo_de_barras,
            producto.codigo_de_origen AS codigo_de_origen
            FROM producto
            JOIN proveedor ON producto.codigo_proveedor = proveedor.codigo
            WHERE producto.codigo = '79-11-001' 
            OR producto.codigo = '79-11-004'
            OR producto.codigo = '79-11-040'
            OR producto.codigo = '79-11-038'
            OR producto.codigo = '79-11-042'
            OR producto.codigo = '53-08-022'
            OR producto.codigo = '53-08-025'
            OR producto.codigo = '53-08-28'
            OR producto.codigo = '53-08-034'
            OR producto.codigo = '53-08-043'
            OR producto.codigo = '53-08-046'
            OR producto.codigo = '53-08-049'
            OR producto.codigo = '53-08-042'
            OR producto.codigo = '53-08-058'
            OR producto.codigo = '53-08-132'
            OR producto.codigo = '53-08-134'
            OR producto.codigo = '53-08-136'
            OR producto.codigo = '53-08-138'
            OR producto.codigo = '53-08-140'
            OR producto.codigo = '90-08-005'
            OR producto.codigo = '90-08-006'
            OR producto.codigo = '90-08-008'
            OR producto.codigo = '90-08-010'
            OR producto.codigo = '90-08-012'
            OR producto.codigo = '39-04-010'
            OR producto.codigo = '39-04-012'
            OR producto.codigo = '39-04-014'
            OR producto.codigo = '39-04-016'
            OR producto.codigo = '39-04-018'
            OR producto.codigo = '39-04-020'
            OR producto.codigo = '39-04-022'
            OR producto.codigo = '39-04-024'
            OR producto.codigo = '39-04-026';");

            while($row = pg_fetch_assoc($mangueras_tarugos_brochas_rodillos_truper)){
                $mangueras_tarugos_brochas_rodillos_array [] = $row;
            }



            //$productos_truper = array_merge($productos_truper1, $mangueras_tarugos_brochas_rodillos_array);
            $productos_truper = $productos_truper1;
          





            #Seleccion productos PPR Y ERA
            $datos_productos_PPR_ERA = pg_query($con_200, "SELECT DISTINCT 
            producto._id AS _id,
            producto.codigo, 
            producto.descripcion AS nombre_producto, 
            producto.descripcion_de_origen, 
            producto.precio_base_pesos AS precio, 
            producto.unidad_de_medida, 
            producto.stock_fisico, 
            producto.stock_minimo, 
            producto.costo_moneda_extranjera,
            producto.codigo_de_origen AS codigo_de_origen, 
            proveedor.codigo    AS codigo_proveedor, 
            proveedor.razon_social AS razon_social, 
            proveedor.marca     AS proveedor_marca,
            producto.eliminado  AS producto_eliminado,
            producto.historico  AS producto_historico,
            proveedor.eliminado AS proveedor_eliminado,
            proveedor.historico AS proveedor_historico
            FROM producto 
            JOIN proveedor ON producto.codigo_proveedor = proveedor.codigo
            WHERE proveedor.marca = 'ERA' 
            OR razon_social LIKE '%PPR%'
            --OR razon_social LIKE '%PALEOLOGOS%'
            ORDER BY producto._id ASC;");

            while ($row = pg_fetch_assoc($datos_productos_PPR_ERA)) {
                $productos_ERA_Y_PPR []    = $row;
            }






         



            #Parametros de entrada "product_template"
            $id_producto          = "";
            $descripcion_producto = "";
            $precio               = "";
            $unidad_de_medida     = "";
            $fecha                = $fecha_actual;
            $ubicacion_comentario = "";
            $codigo_de_origen     = "";


            #Parametros de entrada "product_product"
            $codigo_barras = "";


            #Parametros de entrada "stock_move"
            $ultimo_id_stock_move = "";
            $stock_disponible_final = "";
            $ubicacion_numero = "";
            $inventory_id = "";
            $warehouse_id = "";


            #Parametros de entrada "standard_price"
            $costo_producto = "";

            #parametro ubicacion especifica 
            $tabla_db = "";
            $marca_producto = "";



            #Comienzo de INSERT productos bodega ECOSA
            foreach ($productos_200 as $key => $value){

                $precio             = $value["precio"];
                $stock_disponible   = $value["stock_fisico"];
                $id_producto        = $value["_id"];
                $codigo_proveedor   = $value["codigo_proveedor"];
                $codigo_de_origen   = $value["codigo_de_origen"];
        


                #Descripcion producto
                $reemplazos = array("'","-");
                $descripcion = $value["nombre_producto"];
                $descripcion_producto = str_replace($reemplazos, "", $descripcion);
                #fin descripcion producto




                    
                // Modificacion de parametros para asignar producto PPR Y ERA
                if($codigo_proveedor == '19' OR $codigo_proveedor == '25'){
                    $ubicacion_comentario = "2DO PISO TRUPER";
                    $ubicacion_numero     = 36;
                    $warehouse_id         = 5;
                    $inventory_id         = 4;
                    $tabla_db = "segundo_piso_truper";                   
                }

                #Gestion seleccion de ubicaciones Iventario y Bodega 
                elseif($codigo_proveedor == '01' OR $codigo_proveedor == '02' OR $codigo_proveedor == '03' OR $codigo_proveedor == '04' OR $codigo_proveedor == '05' 
                OR $codigo_proveedor == '06' OR $codigo_proveedor == '07' OR $codigo_proveedor == '08' OR $codigo_proveedor == '09' OR $codigo_proveedor == '10' 
                OR $codigo_proveedor == '11' OR $codigo_proveedor == '13' OR $codigo_proveedor == '14' OR $codigo_proveedor == '24' OR $codigo_proveedor == '28' 
                OR $codigo_proveedor == '29' OR $codigo_proveedor == '49' OR $codigo_proveedor == '64' OR $codigo_proveedor == '81'  ){

                    $ubicacion_comentario = "2DO PISO ECOSA";
                    $ubicacion_numero = 24;
                    $warehouse_id     = 3;
                    $inventory_id     = 2;
                }
                else{
                    $ubicacion_comentario = "1ER PISO ECOSA";
                    $ubicacion_numero   = 18;
                    $warehouse_id       = 2;
                    $inventory_id       = 1;
                  }
                #Fin gestion seleccion de ubicaciones Inventario y Bodega





                #Gestion de unidades de medida
                $unidad_de_medida = "";
                switch ($value["unidad_de_medida"]) {
                    case 630:
                        $unidad_de_medida = 1;
                    break; 
                    case 631:
                        $unidad_de_medida = 3;
                    break;
                    case 632:
                        $unidad_de_medida = 74;
                    break;
                    case 633:
                        $unidad_de_medida = 73;
                    break;
                    case 634:
                        $unidad_de_medida = 65;
                    break;
                    case 635:
                        $unidad_de_medida = 72;
                    break;
                    case 636:
                        $unidad_de_medida = 71;
                    break;
                    case 637:
                        $unidad_de_medida = 70;
                    break;
                    case 638:
                        $unidad_de_medida = 2;
                    break;
                    case 639:
                        $unidad_de_medida = 30;
                    break;
                    case 640:
                        $unidad_de_medida = 8;
                    break;
                    case 641:
                        $unidad_de_medida = 31;
                    break;
                    case 642:
                        $unidad_de_medida = 32;
                    break;
                    case 643:
                        $unidad_de_medida = 62;
                    break;
                    case 644:
                        $unidad_de_medida = 34;
                    break;
                    case 645:
                        $unidad_de_medida = 35;
                    break;
                    case 646:
                        $unidad_de_medida = 36;
                    break;
                    case 647:
                        $unidad_de_medida = 37;
                    break;
                    case 648:
                        $unidad_de_medida = 38;
                    break;
                    case 649:
                        $unidad_de_medida = 39;
                    break;
                    case 650:
                        $unidad_de_medida = 40;
                    break;
                    case 651:
                        $unidad_de_medida = 41;
                    break;
                    case 652:
                        $unidad_de_medida = 42;
                    break;
                    case 653:
                        $unidad_de_medida = 43;
                    break;
                    case 654:
                        $unidad_de_medida = 43;
                    break;
                    case 655:
                        $unidad_de_medida = 44;
                    break;
                    case 656:
                        $unidad_de_medida = 45;
                    break;
                    case 657:
                        $unidad_de_medida = 46;
                    break;
                    case 658:
                        $unidad_de_medida = 1;
                    break;
                    case 659:
                        $unidad_de_medida = 1;
                    break;
                    case 660:
                        $unidad_de_medida = 47;
                    break;
                    case 661:
                        $unidad_de_medida = 1;
                    break;
                    case 662:
                        $unidad_de_medida = 75;
                    break;
                    case 663:
                        $unidad_de_medida = 76;
                    break;
                    case 664:
                        $unidad_de_medida = 1;
                    break;
                    case 665:
                        $unidad_de_medida = 63;
                    break;
                    case 666:
                        $unidad_de_medida = 64;
                    break;
                    case 667:
                        $unidad_de_medida = 77;
                    break;
                    case 668:
                        $unidad_de_medida = 51;
                    break; 
                    case 669:
                        $unidad_de_medida = 50;
                    break;
                    case 670:
                        $unidad_de_medida = 1;
                    break;
                    case 671:
                        $unidad_de_medida = 49;
                    break;
                    case 672:
                        $unidad_de_medida = 48;
                    break;
                    case 673:
                        $unidad_de_medida = 1;
                    break;
                    case 674:
                        $unidad_de_medida = 54;
                    break;
                    case 675:
                        $unidad_de_medida = 53;
                    break;
                    case 676:
                        $unidad_de_medida = 55;
                    break;
                    case 677:
                        $unidad_de_medida = 1;
                    break;
                    case 678:
                        $unidad_de_medida = 1;
                    break;
                    case 679:
                        $unidad_de_medida = 56;
                    break;
                    case 680:
                        $unidad_de_medida = 76;
                    break;
                    case 681:
                        $unidad_de_medida = 57;
                    break;
                    case 682:
                        $unidad_de_medida = 78;
                    break;
                    case 683:
                        $unidad_de_medida = 59;
                    break;
                    case 684:
                        $unidad_de_medida = 1;
                    break;
                    case 685:
                        $unidad_de_medida = 60;
                    break;
                    case 686:
                        $unidad_de_medida = 61;
                    break;
                        case NULL:
                        $unidad_de_medida = 1;
                    break;
                        case 687: 
                        $unidad_de_medida = 33;
                    break;
                        case 688:
                        $unidad_de_medida = 79;        
                }
                #Fin gestion unidades de medida



                #Gestion costo en peso Chileno
                $costo = intval($value["costo_moneda_extranjera"]);
                $costo_producto = "";
                if($costo == NULL){
                    $costo_producto = 0;
                }
                elseif($costo <> NULL){
                    $costo_producto = $costo * 800;
                }
                else{
                    $costo_producto = ROUND($costo * 800);
                }
                #Fin gestion costo en peso Chileno 




                #Generacion codigos de barras EAN-13
                $codigo_bruto = $value["codigo"];
                $valores_reemplazo = array("'","-");
                $codigo_limpio = str_replace($valores_reemplazo, "", $codigo_bruto);
                $explode_codigo = explode(" ", $codigo_limpio);

                $numero_inicial_0 = 9;
                $numero_inicial_1 = 9;
                $numero_inicial_2 = 0;
                $numero_inicial_3 = 0;
                $numero_inicial_4 = 0;
                $number_0 =  $explode_codigo[0][0];
                $number_1 =  $explode_codigo[0][1];
                $number_2 =  $explode_codigo[0][2];
                $number_3 =  $explode_codigo[0][3];
                $number_4 =  $explode_codigo[0][4];
                $number_5 =  $explode_codigo[0][5];
                $number_6 =  $explode_codigo[0][6];



                $array_numeros_pares   =  array("0" => $numero_inicial_0, "1" => $numero_inicial_2, "2" => $numero_inicial_4, "3" => $number_1, "4" => $number_3, "5" => $number_5);
                $array_numeros_impares =  array("0" => $numero_inicial_1, "1" => $numero_inicial_3, "2" => $number_0, "3" => $number_2, "4" => $number_4, "5" => $number_6);

                $resultado_suma_impares = array_sum($array_numeros_impares);
                $resultado_suma_pares   = array_sum($array_numeros_pares);

                $resultado_suma_impares_x3 = ($resultado_suma_impares * 3);
                $resultado_pre_final       = ($resultado_suma_impares_x3 + $resultado_suma_pares);  //sesultado 91


                $valor_test = round($resultado_pre_final, 2);


                $redondeo_pre = ceil($resultado_pre_final/10)*10;;
                $redondeo     = intval($redondeo_pre);


                $numero_verificador = $redondeo - $resultado_pre_final;
                $codigo_barras = ($numero_inicial_0.$numero_inicial_1.$numero_inicial_2.$numero_inicial_3.$numero_inicial_4.$number_0.$number_1.$number_2.$number_3.$number_4.$number_5.$number_6.$numero_verificador);
                #Fin generacion codigos de barras EAN-13





                #fix precio
                if($precio == NULL){
                    $precio = 1;
                }
                #fin fix precio





                #Gestion stock final del producto
                $stock_disponible_final = $stock_disponible;
                if(!is_int($stock_disponible_final)){
                    $stock_disponible_final = round($stock_disponible).".00";
                }
                elseif ($stock_disponible_final == NULL) {
                    $stock_disponible_final = 0.00;
                }
                else{
                    $stock_disponible_final = $stock_disponible.".00";
                } 
                #Fin gestion stock final del producto


                INSERT($id_producto, $descripcion_producto, $precio, $unidad_de_medida, $codigo_de_origen, $fecha, $ubicacion_comentario, $codigo_barras,$stock_disponible_final, $inventory_id, $warehouse_id, $costo_producto, $ubicacion_numero);

                if($tabla_db == "segundo_piso_truper"){
                    if($id_producto == '86791'){
                        //dd("ACA ESTA EL ERROR!!!");
                        //EXCELENTE, ACA MARTCA EL ERROR. ENTONCES ME DOY CUENTA QUE EFECTIVAMENTE LLEGA ACÁ EL ID
                    } 
                    $marca_producto = "PPR & ERA";

                    ubicacion_especifica($codigo_de_origen, $ubicacion_numero, $fecha_actual, $id_producto, $tabla_db, $marca_producto);
                }
                
            }//cierre del foreach productos ECOSA 











            #Comienzo INSERT productos TRUPER
            foreach ($productos_truper as $key => $value){

                $producto_eliminado     = $value["producto_eliminado"];
                $producto_historico     = $value["producto_historico"];
                $id_producto            = $value["_id"];
                $precio                 = $value["precio"];
                $stock_disponible       = $value["stock_fisico"];
                $codigo_de_origen       = $value["codigo_de_origen"];
               
                


                //NOMBRE O DESCRIPCION DEL PRODUCTO TRUPER
                $reemplazos = array("'","-");
                $descripcion_truper = $value["nombre_producto"];
                $descripcion_producto = str_replace($reemplazos, "", $descripcion_truper);
                if($descripcion_producto[0] == " "){
                    $descripcion_producto = substr($descripcion_producto, 1);
                }

                








                if( is_null($producto_eliminado) AND is_null($producto_historico) ){



                    #Gestion ubicacion producto
                    $codigo_producto_value = $value["codigo"];
                    $codigo_producto_substr = substr($codigo_producto_value, 0,5);

               

                    if($codigo_producto_substr == '18-04' OR $codigo_producto_substr == '19-06' OR $codigo_producto_substr == '17-41' 
                    OR $codigo_producto_value == '17-23-001' OR $codigo_producto_value == '17-23-003' OR $codigo_producto_value == '17-23-005' OR $codigo_producto_value == '17-23-007' 
                    OR $codigo_producto_value == '17-23-020' OR $codigo_producto_value == '17-23-040' OR $codigo_producto_value == '17-23-009' OR $codigo_producto_value == '17-23-011' 
                    OR $codigo_producto_value == '17-23-013' OR $codigo_producto_value == '17-23-015' OR $codigo_producto_value == '17-23-004' OR $codigo_producto_value == '17-23-032' 
                    OR $codigo_producto_value == '17-23-030' OR $codigo_producto_value == '17-23-042' OR $codigo_producto_value == '17-23-044' OR $codigo_producto_value == '17-23-046' 
                    OR $codigo_producto_value == '17-06-058' OR $codigo_producto_value == '17-06-332' OR $codigo_producto_value == '17-06-352' OR $codigo_producto_value == '17-06-360' 
                    OR $codigo_producto_value == '17-06-050' OR $codigo_producto_value == '17-06-052' OR $codigo_producto_value == '17-06-056' OR $codigo_producto_value == '17-06-376' 
                    OR $codigo_producto_value == '17-06-054' OR $codigo_producto_value == '17-06-053' OR $codigo_producto_value == '17-06-308' OR $codigo_producto_value == '17-06-346' 
                    OR $codigo_producto_value == '17-06-330' ){


                        $ubicacion_comentario = "2DO PISO TRUPER";
                        $ubicacion_numero     = 36;
                        $warehouse_id         = 5;
                        $inventory_id         = 4;
                        $tabla_db = "segundo_piso_truper";

                    }
                    elseif($codigo_producto_value == '79-11-001' OR $codigo_producto_value == '79-11-004' OR $codigo_producto_value == '79-11-040' OR $codigo_producto_value == '79-11-038' 
                    OR $codigo_producto_value == '79-11-042' OR $codigo_producto_value == '53-08-022' OR $codigo_producto_value == '53-08-025' OR $codigo_producto_value == '53-08-28' 
                    OR $codigo_producto_value == '53-08-034' OR $codigo_producto_value == '53-08-043' OR $codigo_producto_value == '53-08-046' OR $codigo_producto_value == '53-08-049' 
                    OR $codigo_producto_value == '53-08-042' OR $codigo_producto_value == '53-08-058' OR $codigo_producto_value == '53-08-132' OR $codigo_producto_value == '53-08-134' 
                    OR $codigo_producto_value == '53-08-136' OR $codigo_producto_value == '53-08-138' OR $codigo_producto_value == '53-08-140' OR $codigo_producto_value == '90-08-005' 
                    OR $codigo_producto_value == '90-08-006' OR $codigo_producto_value == '90-08-008' OR $codigo_producto_value == '90-08-010' OR $codigo_producto_value == '90-08-012' 
                    OR $codigo_producto_value == '39-04-010' OR $codigo_producto_value == '39-04-012' OR $codigo_producto_value == '39-04-014' OR $codigo_producto_value == '39-04-016' 
                    OR $codigo_producto_value == '39-04-018' OR $codigo_producto_value == '39-04-020' OR $codigo_producto_value == '39-04-022' OR $codigo_producto_value == '39-04-024' 
                    OR $codigo_producto_value == '39-04-026'){

                        //$descripcion_producto = 
                        $descripcion_producto = $descripcion_producto." TRUPER";
                        $ubicacion_comentario = "1ER PISO TRUPER";
                        $ubicacion_numero     = 30;
                        $warehouse_id         = 4;
                        $inventory_id         = 3;
                        $tabla_db = "primer_piso_truper";

                    }
                    else{

                        $ubicacion_comentario = "1ER PISO TRUPER";
                        $ubicacion_numero     = 30;
                        $warehouse_id         = 4;
                        $inventory_id         = 3;
                        $tabla_db = "primer_piso_truper"; 
                    }
                    #Fin gestion ubicacion producto





                    #Gestion unidades de medida
                    $unidad_de_medida = "";
                    switch ($value["unidad_de_medida"]) {
                        case 630:
                            $unidad_de_medida = 1;
                        break;
                        case 631:
                            $unidad_de_medida = 3;
                        break;
                        case 632:
                            $unidad_de_medida = 74;
                        break;
                        case 633:
                            $unidad_de_medida = 73;
                        break;
                        case 634:
                            $unidad_de_medida = 65;
                        break;
                        case 635:
                            $unidad_de_medida = 72;
                        break;
                        case 636:
                            $unidad_de_medida = 71;
                        break;
                        case 637:
                            $unidad_de_medida = 70;
                        break;
                        case 638:
                            $unidad_de_medida = 2;
                        break;
                        case 639:
                            $unidad_de_medida = 30;
                        break;
                        case 640:
                            $unidad_de_medida = 8;
                        break;
                        case 641:
                            $unidad_de_medida = 31;
                        break;
                        case 642:
                            $unidad_de_medida = 32;
                        break;
                        case 643:
                            $unidad_de_medida = 62;
                        break;
                        case 644:
                            $unidad_de_medida = 34;
                        break;
                        case 645:
                            $unidad_de_medida = 35;
                        break;
                        case 646:
                            $unidad_de_medida = 36;
                        break;
                        case 647:
                            $unidad_de_medida = 37;
                        break;
                        case 648:
                            $unidad_de_medida = 38;
                        break;
                        case 649:
                            $unidad_de_medida = 39;
                        break;
                        case 650:
                            $unidad_de_medida = 40;
                        break;
                        case 651:
                            $unidad_de_medida = 41;
                        break;
                        case 652:
                            $unidad_de_medida = 42;
                        break;
                        case 653:
                            $unidad_de_medida = 43;
                        break;
                        case 654:
                            $unidad_de_medida = 43;
                        break;
                        case 655:
                            $unidad_de_medida = 44;
                        break;
                        case 656:
                            $unidad_de_medida = 45;
                        break;
                        case 657:
                            $unidad_de_medida = 46;
                        break;
                        case 658:
                            $unidad_de_medida = 1;
                        break;
                        case 659:
                            $unidad_de_medida = 1;
                        break;
                        case 660:
                            $unidad_de_medida = 47;
                        break;
                        case 661:
                            $unidad_de_medida = 1;
                        break;
                        case 662:
                            $unidad_de_medida = 75;
                        break;
                        case 663:
                            $unidad_de_medida = 76;
                        break;
                        case 664:
                            $unidad_de_medida = 1;
                        break;
                        case 665:
                            $unidad_de_medida = 63;
                        break;
                        case 666:
                            $unidad_de_medida = 64;
                        break;
                        case 667:
                            $unidad_de_medida = 77;
                        break;
                        case 668:
                            $unidad_de_medida = 51;
                        break; 
                        case 669:
                            $unidad_de_medida = 50;
                        break;
                        case 670:
                            $unidad_de_medida = 1;
                        break;
                        case 671:
                            $unidad_de_medida = 49;
                        break;
                        case 672:
                            $unidad_de_medida = 48;
                        break;
                        case 673:
                            $unidad_de_medida = 1;
                        break;
                        case 674:
                            $unidad_de_medida = 54;
                        break;
                        case 675:
                            $unidad_de_medida = 53;
                        break;
                        case 676:
                            $unidad_de_medida = 55;
                        break;
                        case 677:
                            $unidad_de_medida = 1;
                        break;
                        case 678:
                            $unidad_de_medida = 1;
                        break;
                        case 679:
                            $unidad_de_medida = 56;
                        break;
                        case 680:
                            $unidad_de_medida = 76;
                        break;
                        case 681:
                            $unidad_de_medida = 57;
                        break;
                        case 682:
                            $unidad_de_medida = 78;
                        break;
                        case 683:
                            $unidad_de_medida = 59;
                        break;
                        case 684:
                            $unidad_de_medida = 1;
                        break;
                        case 685:
                            $unidad_de_medida = 60;
                        break;
                        case 686:
                            $unidad_de_medida = 61;
                        break;
                            case NULL:
                            $unidad_de_medida = 1;
                        break;
                            case 687: 
                            $unidad_de_medida = 33;
                        break;
                        case 688:
                            $unidad_de_medida = 79;            
                    }
                    #Fin gestion unidades de medida 





                    #Gestion costo en peso Chileno
                    $costo = intval($value["costo_moneda_extranjera"]);
                    $costo_producto = "";
                    if($costo == NULL){
                        $costo_producto = 0;
                    }
                    elseif($costo <> NULL){
                        $costo_producto = $costo * 800;
                    }
                    else{
                        $costo_producto = ROUND($costo * 800);
                    }
                    #Gestion costo en peso Chileno 





                    #Getion codigos de barras TRUPER
                    //$codigo_barras = $value["codigo_de_barras"];
                    //if($codigo_barras == '7501206635339'){
                    //    continue;
                    //}
                    #Fin gestion codigos de barras TRUPER




                    #fix precio
                    if($precio == NULL){
                        $precio = 1;
                    }
                    #fin fix precio




                    #Gestion cantidad final stock del producto
                    $stock_disponible_final = $stock_disponible;
                    if(!is_int($stock_disponible_final)){
                        $stock_disponible_final = round($stock_disponible).".00";
                    }
                    elseif ($stock_disponible_final == NULL) {
                        $stock_disponible_final = 0.00;
                    }
                    else{
                        $stock_disponible_final = $stock_disponible.".00";
                    } 
                    #Gestion cantidad final stock del producto


                    INSERT($id_producto, $descripcion_producto, $precio, $unidad_de_medida, $codigo_de_origen, $fecha, $ubicacion_comentario, $codigo_barras,$stock_disponible_final, $inventory_id, $warehouse_id, $costo_producto, $ubicacion_numero);
                    

                    //API CODIGOS DE BARRA DUN14 Y DUN16
                    Http::get('http://192.168.0.154:105/codigos_de_barra_truper/'.$codigo_de_origen);
                    
                    $marca_producto = "TRUPER Y DERIVADOS";
                    ubicacion_especifica($codigo_de_origen, $ubicacion_numero, $fecha_actual, $id_producto, $tabla_db, $marca_producto);


  }//cierre condicion producto eliminado
}//cierre foreach truper









            foreach ($productos_ERA_Y_PPR as $key => $value){


                $id_producto        = $value["_id"];
                $precio             = $value["precio"];
                $stock_disponible   = $value["stock_fisico"];
                $codigo_de_origen   = $value["codigo_de_origen"];


                //descripcion producto ppr y era
                $reemplazos  = array("'","-");
                $descripcion = $value["nombre_producto"];
                $descripcion_producto  = str_replace($reemplazos, "", $descripcion);




                $producto_eliminado  = $value["producto_eliminado"];
                $producto_historico  = $value["producto_historico"];




                if( is_null($producto_eliminado) AND is_null($producto_historico) ){
 

                    #Gestion ubicacion del producto
                    $codigo_producto_value = $value["codigo"];
                    $codigo_producto_substr = substr($codigo_producto_value, 0,5);

                    if($codigo_producto_substr == '18-04' OR $codigo_producto_substr == '19-06' OR $codigo_producto_substr == '17-41' OR $codigo_producto_substr == '17-06' OR $codigo_producto_value == '17-23-001' OR $codigo_producto_value == '17-23-003' OR $codigo_producto_value == '17-23-005' OR $codigo_producto_value == '17-23-007' OR $codigo_producto_value == '17-23-020' OR $codigo_producto_value == '17-23-040' OR $codigo_producto_value == '17-23-009' OR $codigo_producto_value == '17-23-011' OR $codigo_producto_value == '17-23-013' OR $codigo_producto_value == '17-23-015' OR $codigo_producto_value == '17-23-004' OR $codigo_producto_value == '17-23-032' OR $codigo_producto_value == '17-23-030' OR $codigo_producto_value == '17-23-042' OR $codigo_producto_value == '17-23-044' OR $codigo_producto_value == '17-23-046'  ){


                        $ubicacion_comentario = "2DO PISO PPR ERA";
                        $ubicacion_numero     = 36;
                        $warehouse_id         = 5;
                        $inventory_id         = 4;
                        $tabla_db = "segundo_piso_truper";

                    }
                    else{
                        $ubicacion_comentario = "1ER PISO PPR ERA";
                        $ubicacion_numero     = 30;
                        $warehouse_id         = 4;
                        $inventory_id         = 3;
                    }
                    #Fin gestion ubicacion del producto






                #Gestion unidades de medida
                $unidad_de_medida = "";
                switch ($value["unidad_de_medida"]) {
                    case 630:
                        $unidad_de_medida = 1;
                    break;
                    case 631:
                        $unidad_de_medida = 3;
                    break;
                    case 632:
                        $unidad_de_medida = 74;
                    break;
                    case 633:
                        $unidad_de_medida = 73;
                    break;
                    case 634:
                        $unidad_de_medida = 65;
                    break;
                    case 635:
                        $unidad_de_medida = 72;
                    break;
                    case 636:
                        $unidad_de_medida = 71;
                    break;
                    case 637:
                        $unidad_de_medida = 70;
                    break;
                    case 638:
                        $unidad_de_medida = 2;
                    break;
                    case 639:
                        $unidad_de_medida = 30;
                    break;
                    case 640:
                        $unidad_de_medida = 8;
                    break;
                    case 641:
                        $unidad_de_medida = 31;
                    break;
                    case 642:
                        $unidad_de_medida = 32;
                    break;
                    case 643:
                        $unidad_de_medida = 62;
                    break;
                    case 644:
                        $unidad_de_medida = 34;
                    break;
                    case 645:
                        $unidad_de_medida = 35;
                    break;
                    case 646:
                        $unidad_de_medida = 36;
                    break;
                    case 647:
                        $unidad_de_medida = 37;
                    break;
                    case 648:
                        $unidad_de_medida = 38;
                    break;
                    case 649:
                        $unidad_de_medida = 39;
                    break;
                    case 650:
                        $unidad_de_medida = 40;
                    break;
                    case 651:
                        $unidad_de_medida = 41;
                    break;
                    case 652:
                        $unidad_de_medida = 42;
                    break;
                    case 653:
                        $unidad_de_medida = 43;
                    break;
                    case 654:
                        $unidad_de_medida = 43;
                    break;
                    case 655:
                        $unidad_de_medida = 44;
                    break;
                    case 656:
                        $unidad_de_medida = 45;
                    break;
                    case 657:
                        $unidad_de_medida = 46;
                    break;
                    case 658:
                        $unidad_de_medida = 1;
                    break;
                    case 659:
                        $unidad_de_medida = 1;
                    break;
                    case 660:
                        $unidad_de_medida = 47;
                    break;
                    case 661:
                        $unidad_de_medida = 1;
                    break;
                    case 662:
                        $unidad_de_medida = 75;
                    break;
                    case 663:
                        $unidad_de_medida = 76;
                    break;
                    case 664:
                        $unidad_de_medida = 1;
                    break;
                    case 665:
                        $unidad_de_medida = 63;
                    break;
                    case 666:
                        $unidad_de_medida = 64;
                    break;
                    case 667:
                        $unidad_de_medida = 77;
                    break;
                    case 668:
                        $unidad_de_medida = 51;
                    break; 
                    case 669:
                        $unidad_de_medida = 50;
                    break;
                    case 670:
                        $unidad_de_medida = 1;
                    break;
                    case 671:
                        $unidad_de_medida = 49;
                    break;
                    case 672:
                        $unidad_de_medida = 48;
                    break;
                    case 673:
                        $unidad_de_medida = 1;
                    break;
                    case 674:
                        $unidad_de_medida = 54;
                    break;
                    case 675:
                        $unidad_de_medida = 53;
                    break;
                    case 676:
                        $unidad_de_medida = 55;
                    break;
                    case 677:
                        $unidad_de_medida = 1;
                    break;
                    case 678:
                        $unidad_de_medida = 1;
                    break;
                    case 679:
                        $unidad_de_medida = 56;
                    break;
                    case 680:
                        $unidad_de_medida = 76;
                    break;
                    case 681:
                        $unidad_de_medida = 57;
                    break;
                    case 682:
                        $unidad_de_medida = 78;
                    break;
                    case 683:
                        $unidad_de_medida = 59;
                    break;
                    case 684:
                        $unidad_de_medida = 1;
                    break;
                    case 685:
                        $unidad_de_medida = 60;
                    break;
                    case 686:
                        $unidad_de_medida = 61;
                    break;
                        case NULL:
                        $unidad_de_medida = 1;
                    break;
                        case 687: 
                        $unidad_de_medida = 33;
                    break;
                        case 688:
                        $unidad_de_medida = 79;    
                }
                #Fin gestion unidades de medida




                #Gestion de precio en peso Chileno
                $costo = intval($value["costo_moneda_extranjera"]);
                $costo_producto = "";
                if($costo == NULL){
                $costo_producto = 0;
                }
                elseif($costo <> NULL){
                $costo_producto = $costo * 800;
                }
                else{
                $costo_producto = ROUND($costo * 800);
                }
                #Fin gestion de precio en peso Chileno



                #fix precio
                if($precio == NULL){
                $precio = 1;
                }
                #fin fix precio



                #Gestion cantidad de stock del producto
                $stock_disponible_final = $stock_disponible;
                if(!is_int($stock_disponible_final)){
                    $stock_disponible_final = round($stock_disponible).".00";
                }
                elseif ($stock_disponible_final == NULL) {
                    $stock_disponible_final = 0.00;
                }
                else{
                    $stock_disponible_final = $stock_disponible.".00";
                } 
                #Fin gestion cantidad de stock del producto 



                #Gestion calculo codigos de barras en EAN-13
                $codigo_bruto = $value["codigo"];
                //$codigo_bruto = "17-11-092";
                $valores_reemplazo = array("'","-");
                $codigo_limpio = str_replace($valores_reemplazo, "", $codigo_bruto);
                $explode_codigo = explode(" ", $codigo_limpio);

                $numero_inicial_0 = 9;
                $numero_inicial_1 = 9;
                $numero_inicial_2 = 0;
                $numero_inicial_3 = 0;
                $numero_inicial_4 = 0;
                $number_0 =  $explode_codigo[0][0];
                $number_1 =  $explode_codigo[0][1];
                $number_2 =  $explode_codigo[0][2];
                $number_3 =  $explode_codigo[0][3];
                $number_4 =  $explode_codigo[0][4];
                $number_5 =  $explode_codigo[0][5];
                $number_6 =  $explode_codigo[0][6];



                $array_numeros_pares   =  array("0" => $numero_inicial_0, "1" => $numero_inicial_2, "2" => $numero_inicial_4, "3" => $number_1, "4" => $number_3, "5" => $number_5);
                $array_numeros_impares =  array("0" => $numero_inicial_1, "1" => $numero_inicial_3, "2" => $number_0, "3" => $number_2, "4" => $number_4, "5" => $number_6);

                $resultado_suma_impares = array_sum($array_numeros_impares);
                $resultado_suma_pares   = array_sum($array_numeros_pares);

                $resultado_suma_impares_x3 = ($resultado_suma_impares * 3);
                $resultado_pre_final       = ($resultado_suma_impares_x3 + $resultado_suma_pares);  //sesultado 91


                $valor_test = round($resultado_pre_final, 2);


                $redondeo_pre = ceil($resultado_pre_final/10)*10;;
                $redondeo     = intval($redondeo_pre);

                $numero_verificador = $redondeo - $resultado_pre_final;
                $codigo_barras = ($numero_inicial_0.$numero_inicial_1.$numero_inicial_2.$numero_inicial_3.$numero_inicial_4.$number_0.$number_1.$number_2.$number_3.$number_4.$number_5.$number_6.$numero_verificador);

                #Fin gestion calculo codigos de barras en EAN-13




                INSERT($id_producto, $descripcion_producto, $precio, $unidad_de_medida, $codigo_de_origen, $fecha, $ubicacion_comentario, $codigo_barras,$stock_disponible_final, $inventory_id, $warehouse_id, $costo_producto, $ubicacion_numero);


                $marca_producto = "PPR & ERA";
                ubicacion_especifica($codigo_de_origen, $ubicacion_numero, $fecha_actual, $id_producto, $tabla_db, $marca_producto);
                
               


                }//cierre if( is_null($producto_eliminado) AND is_null($producto_historico) )
            }//cierre for each ppr y era
            //}//cierre isset boton_actualizar
            $UPDATE_STOCK_INVENTORY = pg_query($con_InventorySystem, "UPDATE stock_inventory SET state = 'draft';");
        }//cierre else conection (si no habia ningun error)






        #Restablecimiento del autoincrement de la tabla "stock_move"
        $UPDATE_AUTOINCREMENT = pg_query($con_InventorySystem, "SELECT id FROM stock_move ORDER BY id DESC LIMIT 1;");
        while($row = pg_fetch_assoc($UPDATE_AUTOINCREMENT)){
            $ULTIMATE_ID_AUTOINCREMENT [] = $row;
        }
        
        foreach ($ULTIMATE_ID_AUTOINCREMENT as $key => $value) {
            $ARRAY_ID_AUTOINCREMENT [] = $value;
        }
        
        $ULTIMO_ID_AUTOINCREMENT_PREVIO = $ARRAY_ID_AUTOINCREMENT[0]["id"];
        $ULTIMO_ID_AUTOINCREMENT = $ULTIMO_ID_AUTOINCREMENT_PREVIO + 1;
        $ACTUALIZAR_ID_AUTOINCREMENTO = pg_query($con_InventorySystem, "ALTER SEQUENCE stock_move_id_seq RESTART WITH $ULTIMO_ID_AUTOINCREMENT;");
        #Fin restablecimiento. 


        
        return "CARGA INICIAL EJECUTADA  ".$fecha_actual;
    }//cierre funcion carga_inicial



























    public function ajax_200($codigo_nota_de_venta){

        /* 
            ECO S.A
            Franco Cumplido
            Esta API se encarga de mandar el F8 al sistema "Inventory_System" como ordenes de recogida("picking order")
            Es llamada desde el FRONT-END del sistema de Imatronix, ubicacion =  /opt/imatronix/ecosa/GTS/lib/ktk/web/lib/phpLayersMenu/templates/gts-menu.htm (layouts)
            Ultima modificación 30 de Agosto 2021

        */


   
     
        //header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Allow: GET, POST, OPTIONS, PUT, DELETE");
        //$method = $_SERVER['REQUEST_METHOD'];
        
        

           
                                                                   ##                            CONEXIONES A LAS BASES DE DATOS                                        ##
          
        
        
        
        
        //VARIABLE CONEXION  
        $codigo_nota_venta = $codigo_nota_de_venta;
        
        
        #Servidor 154
        $host_InventorySystem = "192.168.0.154";
        $puerto_Inventory_System= "5432";
        $dbname_Inventory_System = "InventorySystem";
        $dbsuer_InventorySystem = "postgres";
        $userpass_InventorySystem = "";
        $con_InventorySystem = pg_connect("host=$host_InventorySystem port=$puerto_Inventory_System dbname=$dbname_Inventory_System user=$dbsuer_InventorySystem password=$userpass_InventorySystem");
        
        
        
        #Servidor 200
        $host_200 = "192.168.0.200";
        $dbname_200 = "imatronix_ecosa";
        $dbuser_200 = "postgres";
        $userpass_200 = "";
        $con_200 = pg_connect("host=$host_200 dbname=$dbname_200 user=$dbuser_200 password=$userpass_200");
        
        
        
        
        
         
        
        #Fuciones para ejecutar los INSERT al servidor 154
        function FN_INSERT_STOCK_PICKING($ubicacion_inicio,$ubicacion_destino,$warehouse_id,$inventory_id,$picking_type,$nombre_stock_picking,$nombre_final_producto,$id_final_producto,$cantidad_final,$fecha){
        
            #Servidor 154
            $host_odoo     = "192.168.0.154";
            $port_odoo     = "5432";
            $dbname_odoo   = "InventorySystem";
            $dbuser_odoo   = "postgres";
            $userpass_odoo = "";
            $con_odoo = pg_connect("host=$host_odoo port=$port_odoo dbname=$dbname_odoo user=$dbuser_odoo password=$userpass_odoo");


            //Restablecimiento de id de la tabla "stock_move"
            $prev = DB::connection('inventory_system')->table('stock_picking')->select('id')->orderBy('id', 'desc')->limit(1)->get();
            if($prev->isEmpty()){
                $prev=0;
                $ULTIMO_ID_AUTOINCREMENT = $prev + 1;
            }
            else{
                $ULTIMO_ID_AUTOINCREMENT = $prev[0]->id + 1;
            }


            //$ULTIMO_ID_AUTOINCREMENT = $prev[0]->id + 1;
            $text = "ALTER SEQUENCE stock_picking_id_seq RESTART WITH ".strval($ULTIMO_ID_AUTOINCREMENT).";";
            DB::connection('inventory_system')->update(DB::connection('inventory_system')->raw($text));
            //Fin restablecimiento de la tabla "stock_move"


            //Selección campo 'origin' en la tabla stock picking → campo referente al folio de la factura.
            $posicion_primer_corchete = strpos($nombre_stock_picking,'[') + 1;
            $posicion_ultimo_corchete = strpos($nombre_stock_picking,']') - 1;
            $pre_codigo_nota_de_venta = substr($nombre_stock_picking,$posicion_primer_corchete, $posicion_ultimo_corchete);
            $codigo_nota_de_venta = substr($pre_codigo_nota_de_venta, 0, -1);
            
            $arg_codigo = $codigo_nota_de_venta;
            $select_id_nota_de_venta = DB::connection('imatronix_ecosa')->table('nota_de_venta')->select('_id')->where('codigo', $arg_codigo)->get();
            $arg_id = $select_id_nota_de_venta[0]->_id;

            $pre_orden = DB::connection('imatronix_ecosa')->table('factura')->select('numero')->where('nota_de_venta', $arg_id)->get();

            $origen = "";
            if ( !isset($pre_orden[0]->numero) ){
                $origen = NULL; // Esta parte tiene que refactorizarse para evitar que se inserte el valor null.
            }
            else{
                $origen = $pre_orden[0]->numero;
            }
            //Fin selección campo 'origin' en la tabla stock picking → campo referente al folio de la factura.



/*
            //Fix para restablecer contador "id autoincrement" en caso de que falle
            $SELECT_STOCK_PCKING_id = DB::connection('inventory_system')->table('stock_picking')->select('id')->orderByDesc('id')->limit(1)->get();
            
            if(isset($SELECT_STOCK_PCKING_id[0]->id)){
                $ultimo_contador = $SELECT_STOCK_PCKING_id[0]->id;
                $contador = $ultimo_contador + 1;
            }
            else{      
                $contador = 1;
            }
            $actualizando_contador = pg_query($con_odoo,"ALTER SEQUENCE serial_id_sequence RESTART WITH $contador;");
            //Fin del fix

*/

            $SELECT_STOCK_PICKING = pg_query($con_odoo,"SELECT * FROM stock_picking WHERE name LIKE '%{$nombre_stock_picking}%'");
            while($row = pg_fetch_assoc($SELECT_STOCK_PICKING)){
                $array_stock_picking [] = $row;
            }
        

            if(!empty($array_stock_picking)){
                //echo"NO SE EJECUTA STOCK PICKING";
            }
            else{       
                $INSERT_STOCK_PICKING = pg_query($con_odoo, "INSERT INTO stock_picking (message_main_attachment_id, name, origin, note, backorder_id, move_type, state, group_id, priority, scheduled_date, date_deadline, has_deadline_issue, date, date_done, location_id, location_dest_id, picking_type_id, partner_id, company_id, user_id, owner_id, printed, is_locked, immediate_transfer, create_uid, create_date, write_uid, write_date, batch_id) VALUES(NULL,'$nombre_stock_picking','$origen',NULL,NULL,'direct','assigned',NULL,0,'$fecha',NULL,'f','$fecha', NULL,'$ubicacion_inicio', '$ubicacion_destino', '$picking_type', 1,1,2,NULL,NULL,'t','f',2,'$fecha',2,'$fecha',NULL);");
            }
        
            return "LA FUNCIÓN DE INSERT SE EJECUTÓ PERFECTAMENTE → STOCK PICKING <BR>";

        }
        
        
        
        function FN_INSERT_STOCK_MOVE($ubicacion_inicio,$ubicacion_destino,$warehouse_id,$inventory_id,$picking_type,$nombre_stock_picking,$nombre_final_producto,$id_final_producto,$cantidad_final,$fecha,$unidad_de_medida){
        
            #Servidor 154
            $host_odoo     = "192.168.0.154";
            $port_odoo     = "5432";
            $dbname_odoo   = "InventorySystem";
            $dbuser_odoo   = "postgres";
            $userpass_odoo = "";
            $con_odoo = pg_connect("host=$host_odoo port=$port_odoo dbname=$dbname_odoo user=$dbuser_odoo password=$userpass_odoo");
        
        
            $nombre_final_producto = str_replace("'","",$nombre_final_producto);
            $SELECT_STOCK_MOVE = pg_query($con_odoo,"SELECT * FROM stock_move WHERE reference LIKE '%{$nombre_stock_picking}%' AND name LIKE '%{$nombre_final_producto}%' AND product_id = '{$id_final_producto}' ");
            while($row = pg_fetch_assoc($SELECT_STOCK_MOVE)){
                $array_stock_move [] = $row;
            }
        

            $referencia = $nombre_stock_picking;
            $SELECT_ULTIMO_ID_STOCK_MOVE = pg_query($con_odoo, "SELECT id, name FROM stock_picking WHERE name LIKE '%{$referencia}%' ORDER BY id DESC LIMIT 1;");
            while($row = pg_fetch_assoc($SELECT_ULTIMO_ID_STOCK_MOVE)){
                $array_ultimo_id_stock_move [] = $row;
            }
        
            $stock_picking_id = $array_ultimo_id_stock_move[0]["id"];
        
        
            if(!empty($array_stock_move)){
                //echo"NO SE EJECUTA STOCK MOVE";
            }
            else{
                $INSERT_STOCK_MOVE = pg_query($con_odoo, "INSERT INTO stock_move (name, sequence, priority, create_date, date, date_deadline, company_id, product_id, description_picking, product_qty, product_uom_qty, product_uom, location_id, location_dest_id, partner_id, picking_id, note, state, price_unit, origin, procure_method, scrapped, group_id, rule_id, propagate_cancel, delay_alert_date, picking_type_id, inventory_id, origin_returned_move_id, restrict_partner_id, warehouse_id, additional, reference, package_level_id, next_serial, next_serial_count, orderpoint_id, create_uid, write_uid, write_date) 
                VALUES ('$nombre_final_producto',10,0,'$fecha','$fecha',NULL,1,$id_final_producto,'ESTANTERIA X3 1ER NIVEL  BEC1 COD 02',$cantidad_final,'$cantidad_final','$unidad_de_medida','$ubicacion_inicio','$ubicacion_destino',NULL,'$stock_picking_id',NULL,'confirmed',NULL,NULL,'make_to_stock','f',NULL,16,'t',NULL,'$picking_type','$inventory_id',NULL,NULL,'$warehouse_id','f','$nombre_stock_picking',NULL,NULL,NULL,NULL,2,2,'$fecha');");
            }
        
            return "LA FUNCIÓN DE INSERT SE EJECUTÓ PERFECTAMENTE → STOCK MOVE <br>";
        }
        #Fin funciones para ejecutar los INSERT al servidor 154 
        
        
        
        
        
        
        #Gestion nombre de la orden de recogida (picking)
        $SELECT_NUMERO_ORDEN = pg_query($con_200,"SELECT _id FROM nota_de_venta WHERE codigo = '$codigo_nota_venta';");
        while ($row = pg_fetch_assoc($SELECT_NUMERO_ORDEN)) {
            $array_numero_orden[] = $row;
        }   
        
        
        $id_nota_ventaa = $array_numero_orden[0]["_id"];
        $numero_orden = $codigo_nota_venta; 
        $orden = "NOTA-VENTA/[".$numero_orden."]";
        #Fin gestion nombre de la orden de recogida (picking)
        
        
        
        
        
        #Fecha actual
        $hoy = getdate();
        $fecha_actual ="";
        if($hoy["mon"] > 9 AND $hoy["mon"] <= 13  ){
          $fecha_actual = ($hoy["year"]."-".$hoy["mon"]."-".$hoy["mday"]." ".$hoy["hours"].":".$hoy["minutes"].":".$hoy["seconds"].".".substr($hoy["0"],0,6));
        }
        else{
          $fecha_actual = ($hoy["year"]."-"."0".$hoy["mon"]."-".$hoy["mday"]." ".$hoy["hours"].":".$hoy["minutes"].":".$hoy["seconds"].".".substr($hoy["0"],0,6));
        }

        
        
        


        
        $SELECT_PRODUCTOS = pg_query($con_200, "SELECT DISTINCT detalle_venta.producto, detalle_venta.cantidad AS cantidad, producto.descripcion AS nombre_producto, producto.codigo, producto.codigo_producto, producto.codigo_proveedor, producto._id AS id_producto 
            FROM detalle_venta 
            JOIN producto ON detalle_venta.producto = producto._id  
            WHERE detalle_venta.nota_de_venta_id = '$id_nota_ventaa'
            ORDER BY producto._id DESC;");
        
        while($row = pg_fetch_assoc($SELECT_PRODUCTOS)){
            $ARRAY_PRODUCTOS [] = $row;
        }
        
        

        
        #Variables para ejecutar los INSERT 

        //stock_picking 
        $ultimo_pick          = "";
        $ubicacion_inicio     = "";
        $ubicacion_destino    = "";
        $warehouse_id         = "";
        $inventory_id         = "";
        $picking_type         = "";
        $nombre_stock_picking = "";
        
        //stock_move
        $nombre_final_producto ="";
        $id_final_producto     ="";
        $cantidad_final        ="";
        $fecha                 ="";
        $unidad_de_medida      ="";

        #Finvariables para ejecutar los INSERT
        
        
        
        #Array's para separar las ordenes de recogida (picking) por bodega
        $array_productos_segundo_piso_ecosa  = array();
        $array_productos_primer_piso_ecosa   = array();
        $array_productos_segundo_piso_truper = array();
        $array_productos_primer_piso_truper  = array();
        #Fin Array's para separar las ordenes de recogida (picking) por bodega
        
        
        foreach ($ARRAY_PRODUCTOS as $key => $producto) {
        
            $id_prod     = $producto["id_producto"];
                
            $SELECT_PRODUCT = pg_query($con_InventorySystem,"SELECT product_template.description_pickingout, product_template.name, product_template.id , product_template.description_picking AS bodega, uom_id AS unidad_de_medida FROM product_template 
            WHERE product_template.id = '{$id_prod}'
            ORDER BY product_template.description_pickingout DESC;");
        
            while($row = pg_fetch_assoc($SELECT_PRODUCT)){
                $ARRAY_PRODUCT [] = $row;
            }       
        }
        
      
        
        foreach ($ARRAY_PRODUCT as $key => $value) {
        
            $id_final_producto       = $ARRAY_PRODUCTOS[$key]["id_producto"];
            $nombre_final_producto   = $ARRAY_PRODUCTOS[$key]["nombre_producto"];
            $cantidad_final          = $ARRAY_PRODUCTOS[$key]["cantidad"];
            $bodega                  = $value["bodega"];
            $unidad_de_medida        = $value["unidad_de_medida"];
            $ubicacion               = $value["description_pickingout"];


            $codigo_producto = DB::connection('imatronix_ecosa')->table('producto')->select('codigo')->where('_id', '=', $id_final_producto)->get();
            $cod_prod = $codigo_producto[0]->codigo;

        
            if($bodega == "1ER PISO ECOSA"){
        
                array_push($array_productos_primer_piso_ecosa,['id_producto_final' => $id_final_producto,'nombre_final_producto' => $nombre_final_producto,'cantidad_final' => $cantidad_final,'unidad_de_medida' => $unidad_de_medida, 'ubicacion' => $ubicacion, 'codigo' => $cod_prod]);
            }
            if($bodega == "2DO PISO ECOSA"){
        
                array_push($array_productos_segundo_piso_ecosa,['id_producto_final' => $id_final_producto,'nombre_final_producto' => $nombre_final_producto,'cantidad_final' => $cantidad_final,'unidad_de_medida' => $unidad_de_medida, 'ubicacion' => $ubicacion, 'codigo' => $cod_prod] );
            }
            if($bodega == "1ER PISO TRUPER" OR $bodega == "1ER PISO PPR ERA"){
        
                array_push($array_productos_primer_piso_truper,['id_producto_final' => $id_final_producto,'nombre_final_producto' => $nombre_final_producto,'cantidad_final' => $cantidad_final,'unidad_de_medida' => $unidad_de_medida, 'ubicacion' => $ubicacion, 'codigo' => $cod_prod]);      
            }
            if($bodega == "2DO PISO TRUPER" OR $bodega == "2DO PISO PPR ERA"){
        
                array_push($array_productos_segundo_piso_truper,['id_producto_final' => $id_final_producto,'nombre_final_producto' => $nombre_final_producto,'cantidad_final' => $cantidad_final,'unidad_de_medida' => $unidad_de_medida, 'ubicacion' => $ubicacion, 'codigo' => $cod_prod]);
            }
        }
        


        #BLOQUE ORDEN DE PRODUCTOS   

        //ECOSA
        $location_segundo_primer_ecosa = array();
        foreach ($array_productos_primer_piso_ecosa as $key => $row){
            //$location_segundo_primer_ecosa[$key] = $row['ubicacion'];
            $location_segundo_primer_ecosa[$key] = $row['codigo'];
        }
        array_multisort($location_segundo_primer_ecosa, SORT_DESC, $array_productos_primer_piso_ecosa);


        $location_segundo_piso_ecosa = array();
        foreach ($array_productos_segundo_piso_ecosa as $key => $row){
            //$location_segundo_piso_ecosa[$key] = $row['ubicacion'];
            $location_segundo_piso_ecosa[$key] = $row['codigo'];
        }
        array_multisort($location_segundo_piso_ecosa, SORT_DESC, $array_productos_segundo_piso_ecosa);



        //TRUPER
        $location_primer_piso_truper = array();
        foreach ($array_productos_primer_piso_truper as $key => $row){
            //$location_primer_piso_truper[$key] = $row['ubicacion'];
            $location_primer_piso_truper[$key] = $row['codigo'];
        }
        array_multisort($location_primer_piso_truper, SORT_DESC, $array_productos_primer_piso_truper);


        $location_segundo_piso_truper = array();
        foreach ($array_productos_segundo_piso_truper as $key => $row){
            //$location_segundo_piso_truper[$key] = $row['ubicacion'];
            $location_segundo_piso_truper[$key] = $row['codigo'];
        }
        array_multisort($location_segundo_piso_truper, SORT_DESC, $array_productos_segundo_piso_truper);
         
        #FIN BLOQUE ORDEN DE PRODUCTOS        


        

        foreach ($array_productos_primer_piso_ecosa as $key => $value) {
        
            $id_final_producto     = $value["id_producto_final"];
            $nombre_final_producto = $value["nombre_final_producto"];
            $cantidad_final        = $value["cantidad_final"];
            $unidad_de_medida      = $value["unidad_de_medida"];
            $fecha                 = $fecha_actual;

        
            //stock_picking 
            $ubicacion_inicio     = 18;
            $ubicacion_destino    = 19;
            $warehouse_id         = 2;
            $inventory_id         = 1;
            $picking_type         = 8;
            $nombre_stock_picking = "BEC1/PICK/".$orden;
        
            $FN_INSERT_STOCK_PICKING = FN_INSERT_STOCK_PICKING($ubicacion_inicio,$ubicacion_destino,$warehouse_id,$inventory_id,$picking_type,$nombre_stock_picking,$nombre_final_producto,$id_final_producto,$cantidad_final,$fecha);
        


            $ubicacion_inicio_stock_move_select  = DB::connection('inventory_system')->table('stock_quant')->select('location_id')->Where('product_id', '=', $id_final_producto)->get();
            $ubicacion_inicio_stock_move = $ubicacion_inicio_stock_move_select[0]->location_id;
            

            $FN_INSERT_STOCK_MOVE = FN_INSERT_STOCK_MOVE($ubicacion_inicio_stock_move,$ubicacion_destino,$warehouse_id,$inventory_id,$picking_type,$nombre_stock_picking,$nombre_final_producto,$id_final_producto,$cantidad_final,$fecha,$unidad_de_medida);
        
        }
        
        
        
   

        
        foreach ($array_productos_segundo_piso_ecosa as $key => $value) {
             
            $id_final_producto     = $value["id_producto_final"];
            $nombre_final_producto = $value["nombre_final_producto"];
            $cantidad_final        = $value["cantidad_final"];
            $unidad_de_medida      = $value["unidad_de_medida"];
            $fecha                 = $fecha_actual;
            $ubicacion             = strtoupper($value["ubicacion"]);


            //stock_picking
            $ubicacion_inicio     = 24;
            $ubicacion_destino    = 19;
            $warehouse_id         = 3;
            $inventory_id         = 2;
            $picking_type         = 13;
            $nombre_stock_picking = "BEC2/PICK/".$orden;
        
            $FN_INSERT_STOCK_PICKING = FN_INSERT_STOCK_PICKING($ubicacion_inicio,$ubicacion_destino,$warehouse_id,$inventory_id,$picking_type,$nombre_stock_picking,$nombre_final_producto,$id_final_producto,$cantidad_final,$fecha);

            $FN_INSERT_STOCK_MOVE = FN_INSERT_STOCK_MOVE($ubicacion_inicio,$ubicacion_destino,$warehouse_id,$inventory_id,$picking_type,$nombre_stock_picking,$nombre_final_producto,$id_final_producto,$cantidad_final,$fecha,$unidad_de_medida);
        
        }
        
        
        


        
        foreach ($array_productos_primer_piso_truper as $key => $value) {
        
            $id_final_producto     = $value["id_producto_final"];
            $nombre_final_producto = $value["nombre_final_producto"];
            $cantidad_final        = $value["cantidad_final"];
            $unidad_de_medida      = $value["unidad_de_medida"];
            $fecha                 = $fecha_actual;
        
            //stock picking
            $ubicacion_inicio     = 30;
            $ubicacion_destino    = 19;
            $warehouse_id         = 4;
            $inventory_id         = 3;
            $picking_type         = 18;
            $nombre_stock_picking = "TRP1/PICK/".$orden;





        
            $FN_INSERT_STOCK_PICKING = FN_INSERT_STOCK_PICKING($ubicacion_inicio,$ubicacion_destino,$warehouse_id,$inventory_id,$picking_type,$nombre_stock_picking,$nombre_final_producto,$id_final_producto,$cantidad_final,$fecha);
        
            $FN_INSERT_STOCK_MOVE = FN_INSERT_STOCK_MOVE($ubicacion_inicio,$ubicacion_destino,$warehouse_id,$inventory_id,$picking_type,$nombre_stock_picking,$nombre_final_producto,$id_final_producto,$cantidad_final,$fecha,$unidad_de_medida);
        }
        
        
        
        
        
        
        foreach ($array_productos_segundo_piso_truper as $key => $value) {
        
            $id_final_producto     = $value["id_producto_final"];
            $nombre_final_producto = $value["nombre_final_producto"];
            $cantidad_final        = $value["cantidad_final"];
            $unidad_de_medida      = $value["unidad_de_medida"];
            $fecha                 = $fecha_actual;
        
            //stock picking
            $ubicacion_inicio     = 36;
            $ubicacion_destino    = 19;
            $warehouse_id         = 5;
            $inventory_id         = 4;
            $picking_type         = 23;
            $nombre_stock_picking = "TRP2/PICK/".$orden;
        
            $FN_INSERT_STOCK_PICKING = FN_INSERT_STOCK_PICKING($ubicacion_inicio,$ubicacion_destino,$warehouse_id,$inventory_id,$picking_type,$nombre_stock_picking,$nombre_final_producto,$id_final_producto,$cantidad_final,$fecha);
        
            $FN_INSERT_STOCK_MOVE = FN_INSERT_STOCK_MOVE($ubicacion_inicio,$ubicacion_destino,$warehouse_id,$inventory_id,$picking_type,$nombre_stock_picking,$nombre_final_producto,$id_final_producto,$cantidad_final,$fecha,$unidad_de_medida);
                    
        }
        
        
        

        #Restablecimiento del autoincrement de la tabla "stock_move"
        $UPDATE_AUTOINCREMENT = pg_query($con_InventorySystem, "SELECT id FROM stock_move ORDER BY id DESC LIMIT 1;");
            while($row = pg_fetch_assoc($UPDATE_AUTOINCREMENT)){
                $ULTIMATE_ID_AUTOINCREMENT [] = $row;
            }
        
        foreach ($ULTIMATE_ID_AUTOINCREMENT as $key => $value) {
            $ARRAY_ID_AUTOINCREMENT [] = $value;
        }
        
        $ULTIMO_ID_AUTOINCREMENT_PREVIO = $ARRAY_ID_AUTOINCREMENT[0]["id"];
        $ULTIMO_ID_AUTOINCREMENT = $ULTIMO_ID_AUTOINCREMENT_PREVIO + 1;
        $ACTUALIZAR_ID_AUTOINCREMENTO = pg_query($con_InventorySystem, "ALTER SEQUENCE stock_move_id_seq RESTART WITH $ULTIMO_ID_AUTOINCREMENT;");
        


        return"AJAX 200 EJECUTADO  ".$fecha_actual;
    }





















/*
Esra seccion hay que modificarla!!!! [EN PROCESO]
Estába seguro que algún día llegaría hasta acá
*/



    public function recibimiento_embarque($orden_de_compra){

        //header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Allow: GET, POST, OPTIONS, PUT, DELETE");
        //$method = $_SERVER['REQUEST_METHOD'];

  
        #Conexion a las bases de datos

        #Servidor 154
        $host_InventorySystem = "192.168.0.154";
        $puerto_Inventory_System = "5432";
        $dbname_Inventory_System = "InventorySystem";
        $dbsuer_InventorySystem = "postgres";
        $userpass_InventorySystem = "";
        $con_InventorySystem = pg_connect("host=$host_InventorySystem port=$puerto_Inventory_System dbname=$dbname_Inventory_System user=$dbsuer_InventorySystem password=$userpass_InventorySystem");



        #Servidor 200
        $host_200 = "192.168.0.200";
        $dbname_200 = "imatronix_ecosa";
        $dbuser_200 = "postgres";
        $userpass_200 = "";
        $con_200 = pg_connect("host=$host_200 dbname=$dbname_200 user=$dbuser_200 password=$userpass_200");
        
        #Fin conexion a las bases de datos 



        #Fecha actual
        $hoy = getdate();
        $fecha_actual ="";
        if($hoy["mon"] > 9 AND $hoy["mon"] <= 13  ){
            $fecha_actual = ($hoy["year"]."-".$hoy["mon"]."-".$hoy["mday"]." ".$hoy["hours"].":".$hoy["minutes"].":".$hoy["seconds"].".".substr($hoy["0"],0,6));
        }
        else{
            $fecha_actual = ($hoy["year"]."-"."0".$hoy["mon"]."-".$hoy["mday"]." ".$hoy["hours"].":".$hoy["minutes"].":".$hoy["seconds"].".".substr($hoy["0"],0,6));
        }



        function INSERT_STOCK_MOVE ($nombre_final_producto, $fecha, $id_final_producto, $cantidad_final, $unidad_de_medida, $nombre_stock_picking, $ubicacion_inicio, $picking_type_id, $ubicacion_destino, $inventory_id, $warehouse_id, $ultimo_id_stock_picking){

            #Servidor 154
            $host_InventorySystem = "192.168.0.154";
            $puerto_Inventory_System= "5432";
            $dbname_Inventory_System = "InventorySystem";
            $dbsuer_InventorySystem = "postgres";
            $userpass_InventorySystem = "";
            $con_InventorySystem = pg_connect("host=$host_InventorySystem port=$puerto_Inventory_System dbname=$dbname_Inventory_System user=$dbsuer_InventorySystem password=$userpass_InventorySystem");


            $SELECT_STOCK_MOVE = pg_query($con_InventorySystem,"SELECT * FROM stock_move WHERE reference LIKE '%{$nombre_stock_picking}%' AND name LIKE '%{$nombre_final_producto}%' AND product_id = '{$id_final_producto}' ");
            while($row = pg_fetch_assoc($SELECT_STOCK_MOVE)){
                $array_stock_move [] = $row;
            }


            if(!empty($array_stock_move)){
                //echo"NO SE EJECUTA STOCK MOVE";
            }
            else{
                $INSERT_STOCK_MOVE = pg_query($con_InventorySystem, "INSERT INTO stock_move(name, sequence, priority, create_date, date, date_deadline, company_id, product_id, description_picking, product_qty, product_uom_qty, product_uom, location_id, location_dest_id, partner_id, picking_id, note, state, price_unit, origin, procure_method, scrapped, group_id, rule_id, propagate_cancel, delay_alert_date, picking_type_id, inventory_id, origin_returned_move_id, restrict_partner_id, warehouse_id, additional, reference, package_level_id, next_serial, next_serial_count, orderpoint_id, create_uid, write_uid, write_date) 
                VALUES ('$nombre_final_producto',10,0,'$fecha','$fecha',NULL,1,$id_final_producto, 'ESTANTERIA X3 1ER NIVEL  BEC1 COD 02', 0, '$cantidad_final', '$unidad_de_medida', '$ubicacion_inicio', '$ubicacion_destino', NULL, '$ultimo_id_stock_picking', NULL, 'confirmed', NULL, NULL, 'make_to_stock', 'f', NULL, NULL, 't', NULL, 8, '$inventory_id', NULL, NULL, '$warehouse_id', 'f', '$nombre_stock_picking', NULL, NULL, NULL, NULL, 2, 2, '$fecha');");
            }
        }




        $id_orden_de_compra = $orden_de_compra;



        $SELECT_ORDENES_DE_COMPRA_APROBADAS = pg_query($con_200,"SELECT orden_de_compra._id, orden_de_compra.numero, orden_de_compra.fecha_de_llegada, detalle_orden_de_compra.producto, detalle_orden_de_compra.cantidad_pedida, detalle_orden_de_compra.unidad 
        FROM orden_de_compra 
        JOIN detalle_orden_de_compra ON orden_de_compra._id = detalle_orden_de_compra.orden_de_compra_id
        WHERE orden_de_compra._id= '$id_orden_de_compra'
        ORDER BY orden_de_compra._id DESC;");

        while($row = pg_fetch_assoc($SELECT_ORDENES_DE_COMPRA_APROBADAS)){
            $array_ordenes_de_compra [] = $row;
        }


        #INSERT tabla "stock_picking"
        $numero_orden_de_compra = $array_ordenes_de_compra[0]["numero"];
        $numero_orden = "ORDEN/NUM/".$numero_orden_de_compra;


        #Seleccion del ultimo id de "stock_picking"
        $SELECT_ULTIMO_ID_STOCK_PICKING = pg_query($con_InventorySystem, "SELECT id,name FROM stock_picking ORDER BY id DESC LIMIT 1;");
        while($row = pg_fetch_assoc($SELECT_ULTIMO_ID_STOCK_PICKING)){
            $array_ultimo_id_stock_picking[] = $row;
        }
        $ultimo_id_stock_picking = $array_ultimo_id_stock_picking[0]["id"] + 1;
        #Fin seleccion del ultimo id "stock_picking"



        $SELECT_STOCK_PICKING_LIMIT = pg_query($con_InventorySystem, "SELECT name FROM stock_picking WHERE name LIKE '%{$numero_orden}%';");
        while($row = pg_fetch_assoc($SELECT_STOCK_PICKING_LIMIT)){
            $select_stock_picking_limit [] = $row;
        }

        if(empty($select_stock_picking_limit)){
            $INSERT_STOCK_PICKING = pg_query($con_InventorySystem, "INSERT INTO stock_picking (id, message_main_attachment_id, name, origin, note, backorder_id, move_type, state, group_id, priority, scheduled_date, date_deadline, has_deadline_issue, date, date_done, location_id, location_dest_id, picking_type_id, partner_id, company_id, user_id, owner_id, printed, is_locked, immediate_transfer, create_uid, create_date, write_uid, write_date, batch_id) 
            VALUES('$ultimo_id_stock_picking',NULL,'$numero_orden',NULL,NULL,NULL,'direct','assigned',NULL,0,'$fecha_actual',NULL,'f','$fecha_actual',NULL,18, 1,42,1,1,2,NULL,NULL,'t','f',2,'$fecha_actual',2,'$fecha_actual',NULL);");
        }
        else{
            //no hay accion
        }
        #Fin INSERT tabla "stock_picking"









        foreach ($array_ordenes_de_compra as $key => $value) {

            $producto  = $array_ordenes_de_compra[$key]["producto"];

            $SELECT_PRODUCT = pg_query($con_InventorySystem,"SELECT id, name AS nombre_producto, description_picking AS bodega, uom_id AS unidad_de_medida, description_pickingout  FROM product_template  WHERE product_template.id = '{$producto}';");
            while($row = pg_fetch_assoc($SELECT_PRODUCT)){
                $array_productos [] = $row;
            }

        }



        #Array's para poder separar los productos segun la bodega que corresponda
        $array_productos_segundo_piso_ecosa  = array();
        $array_productos_primer_piso_ecosa   = array();
        $array_productos_segundo_piso_truper = array();
        $array_productos_primer_piso_truper  = array();
        #Fin array's discriminacion por bodega



        foreach ($array_productos as $key => $value) {


            var_dump($value);
            var_dump("<br>");
        
            $id               = $array_ordenes_de_compra[$key]["_id"];
            $numero           = $array_ordenes_de_compra[$key]["numero"];
            $fecha_de_llegada = $array_ordenes_de_compra[$key]["fecha_de_llegada"];
            $cantidad_pedida  = $array_ordenes_de_compra[$key]["cantidad_pedida"];
            $nombre_producto  = $value["nombre_producto"];
            $bodega           = $value["bodega"];
            $unidad_de_medida = $value["unidad_de_medida"];
            $ubicacion        = $value["description_pickingout"];



            $SELECT_ID_PICKING = pg_query($con_InventorySystem,"SELECT id FROM stock_picking WHERE name = '{$numero_orden}'");
            while($row = pg_fetch_assoc($SELECT_ID_PICKING)){
                $array_id [] = $row;
            }


            $ultimo_id_stock_picking = $array_id[0]["id"];
            $nombre_final_producto = $nombre_producto; 
            $fecha                 = $fecha_actual;  
            $id_final_producto     = $value["id"];  
            $cantidad_final        = $cantidad_pedida; 
            $unidad_de_medida      = $unidad_de_medida; 
            $nombre_stock_picking  = $numero_orden;
            $ubicacion_inicio      = 4;
            $picking_type_id       = 42;
            $ubicacion_destino     = "";
            $inventory_id          = "";
            $warehouse_id          = "";




            if($bodega == "1ER PISO ECOSA"){
        
                array_push($array_productos_primer_piso_ecosa,['id_producto_final' => $id_final_producto,'nombre_final_producto' => $nombre_final_producto,'cantidad_final' => $cantidad_final,'unidad_de_medida' => $unidad_de_medida, 'ubicacion' => $ubicacion]);
            }
            if($bodega == "2DO PISO ECOSA"){
        
                array_push($array_productos_segundo_piso_ecosa,['id_producto_final' => $id_final_producto,'nombre_final_producto' => $nombre_final_producto,'cantidad_final' => $cantidad_final,'unidad_de_medida' => $unidad_de_medida, 'ubicacion' => $ubicacion] );
            }
            if($bodega == "1ER PISO TRUPER" OR $bodega == "1ER PISO PPR ERA"){
        
                array_push($array_productos_primer_piso_truper,['id_producto_final' => $id_final_producto,'nombre_final_producto' => $nombre_final_producto,'cantidad_final' => $cantidad_final,'unidad_de_medida' => $unidad_de_medida, 'ubicacion' => $ubicacion]);      
            }
            if($bodega == "2DO PISO TRUPER" OR $bodega == "2DO PISO PPR ERA"){
        
                array_push($array_productos_segundo_piso_truper,['id_producto_final' => $id_final_producto,'nombre_final_producto' => $nombre_final_producto,'cantidad_final' => $cantidad_final,'unidad_de_medida' => $unidad_de_medida, 'ubicacion' => $ubicacion]);
            }
        }//Cierre foreach $array_producto





        //ECOSA
        $location_segundo_primer_ecosa = array();
        foreach ($array_productos_primer_piso_ecosa as $key => $row){
            $location_segundo_primer_ecosa[$key] = $row['ubicacion'];
        }
        array_multisort($location_segundo_primer_ecosa, SORT_ASC, $array_productos_primer_piso_ecosa);


        $location_segundo_piso_ecosa = array();
        foreach ($array_productos_segundo_piso_ecosa as $key => $row){
            $location_segundo_piso_ecosa[$key] = $row['ubicacion'];
        }
        array_multisort($location_segundo_piso_ecosa, SORT_ASC, $array_productos_segundo_piso_ecosa);



        //TRUPER
        $location_primer_piso_truper = array();
        foreach ($array_productos_primer_piso_truper as $key => $row){
            $location_primer_piso_truper[$key] = $row['ubicacion'];
        }
        array_multisort($location_primer_piso_truper, SORT_ASC, $array_productos_primer_piso_truper);


        $location_segundo_piso_truper = array();
        foreach ($array_productos_segundo_piso_truper as $key => $row){
            $location_segundo_piso_truper[$key] = $row['ubicacion'];
        }
        array_multisort($location_segundo_piso_truper, SORT_ASC, $array_productos_segundo_piso_truper);
         
        #FIN BLOQUE ORDEN DE PRODUCTOS     






        foreach ($array_productos_primer_piso_ecosa as $key => $value) {

            $id_final_producto     = $value["id_producto_final"];
            $nombre_final_producto = $value["nombre_final_producto"];
            $cantidad_final        = $value["cantidad_final"];
            $unidad_de_medida      = $value["unidad_de_medida"];
            $fecha                 = $fecha_actual;

            $ubicacion_destino  = 18;
            $warehouse_id       = 2;
            $inventory_id       = 1;

            INSERT_STOCK_MOVE ($nombre_final_producto, $fecha, $id_final_producto, $cantidad_final, $unidad_de_medida, $nombre_stock_picking, $ubicacion_inicio, $picking_type_id, $ubicacion_destino, $inventory_id, $warehouse_id, $ultimo_id_stock_picking);
        }





        foreach ($array_productos_segundo_piso_ecosa as $key => $value) {

            $id_final_producto     = $value["id_producto_final"];
            $nombre_final_producto = $value["nombre_final_producto"];
            $cantidad_final        = $value["cantidad_final"];
            $unidad_de_medida      = $value["unidad_de_medida"];
            $fecha                 = $fecha_actual;

            $ubicacion_destino  = 24;
            $warehouse_id       = 3;
            $inventory_id       = 2;

            INSERT_STOCK_MOVE ($nombre_final_producto, $fecha, $id_final_producto, $cantidad_final, $unidad_de_medida, $nombre_stock_picking, $ubicacion_inicio, $picking_type_id, $ubicacion_destino, $inventory_id, $warehouse_id, $ultimo_id_stock_picking);

        }






        foreach ($array_productos_primer_piso_truper as $key => $value) {

            $id_final_producto     = $value["id_producto_final"];
            $nombre_final_producto = $value["nombre_final_producto"];
            $cantidad_final        = $value["cantidad_final"];
            $unidad_de_medida      = $value["unidad_de_medida"];
            $fecha                 = $fecha_actual;

            $ubicacion_destino  = 30;
            $warehouse_id       = 4;
            $inventory_id       = 3;

            INSERT_STOCK_MOVE ($nombre_final_producto, $fecha, $id_final_producto, $cantidad_final, $unidad_de_medida, $nombre_stock_picking, $ubicacion_inicio, $picking_type_id, $ubicacion_destino, $inventory_id, $warehouse_id, $ultimo_id_stock_picking);
        }






        foreach ($array_productos_segundo_piso_truper as $key => $value) {

            $id_final_producto     = $value["id_producto_final"];
            $nombre_final_producto = $value["nombre_final_producto"];
            $cantidad_final        = $value["cantidad_final"];
            $unidad_de_medida      = $value["unidad_de_medida"];
            $fecha                 = $fecha_actual;


            $ubicacion_destino  = 36;
            $warehouse_id       = 5;
            $inventory_id       = 4;

            INSERT_STOCK_MOVE ($nombre_final_producto, $fecha, $id_final_producto, $cantidad_final, $unidad_de_medida, $nombre_stock_picking, $ubicacion_inicio, $picking_type_id, $ubicacion_destino, $inventory_id, $warehouse_id, $ultimo_id_stock_picking);
                    
        }


        return"RECIBIMIENTO DE EMBARCACION EJECUTADO";
    }

































    public function modificar_nota_de_venta($nota_de_venta){


        //header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Allow: GET, POST, OPTIONS, PUT, DELETE");
        $method = $_SERVER['REQUEST_METHOD'];




        #Conexion bases de datos

        #Servidor 154
        $host_InventorySystem = "192.168.0.154";
        $puerto_Inventory_System= "5432";
        $dbname_Inventory_System = "InventorySystem";
        $dbsuer_InventorySystem = "postgres";
        $userpass_InventorySystem = "";
        $con_InventorySystem = pg_connect("host=$host_InventorySystem port=$puerto_Inventory_System dbname=$dbname_Inventory_System user=$dbsuer_InventorySystem password=$userpass_InventorySystem");




        #Servidor 200
        $host_200 = "192.168.0.200";
        $dbname_200 = "imatronix_ecosa";
        $dbuser_200 = "postgres";
        $userpass_200 = "";
        $con_200 = pg_connect("host=$host_200 dbname=$dbname_200 user=$dbuser_200 password=$userpass_200");

        #Fin conexion ases de datos 


        $nota_de_venta = $nota_de_venta;

        $SELECT_ID_NOTA_DE_VENTA = pg_query($con_200, "SELECT _id FROM nota_de_venta WHERE codigo = '$nota_de_venta' LIMIT 1;");
        while ($row = pg_fetch_assoc($SELECT_ID_NOTA_DE_VENTA)){
            $array_id_nota_de_venta [] = $row;
        }   

        $id_nota_de_venta = $array_id_nota_de_venta[0]["_id"];




        $SELECT_PODUCTOS_NOTA_DE_VENTA = pg_query($con_200,"SELECT producto.descripcion AS producto, detalle_venta.cantidad AS cantidad 
        FROM detalle_venta
        JOIN producto
        ON producto._id = detalle_venta.producto
        WHERE detalle_venta.nota_de_venta_id = '$id_nota_de_venta' 
        ORDER BY detalle_venta.producto DESC;");
        while($row = pg_fetch_assoc($SELECT_PODUCTOS_NOTA_DE_VENTA)){
            $array_detalle_venta [] = $row;
        }





        ##SELECCIÓN CANTIDADES A ACTUALIZAR!
        $array_selector_200 = array();
        foreach ($array_detalle_venta as $key => $value){
            $cantidad     = $value["cantidad"];
            $id_producto  = strval($value["producto"]);
            array_push($array_selector_200,$value["producto"]."&&".$value["cantidad"]);
        }




        $SELECT_STOCK_MOVE_1 = pg_query($con_InventorySystem,"SELECT product_template.name AS id_producto, stock_move.product_uom_qty AS cantidad 
            FROM stock_move 
            JOIN product_template ON stock_move.product_id = product_template.id
            WHERE stock_move.reference LIKE '%{$nota_de_venta}%' ORDER BY product_template.id DESC;");
            while($row = pg_fetch_assoc($SELECT_STOCK_MOVE_1)){
                $array_stock_move [] = $row;
        }





        ##ENVIO DE EMAIL
        $array_merge = $array_detalle_venta;
        foreach ($array_detalle_venta as $key => $value){

            $cantidad_modificada = ($value["cantidad"]);
            $array_cantidad = array("modificada" => $cantidad_modificada);
            $merge = (array_merge($array_merge[$key],$array_cantidad));
            $array [] = $merge;

        }


        $diferencia = "";
        foreach ($array as $key => $value){

            $cantidad_original = ($value["cantidad"]);
            $cantidad_modificada = ($value["modificada"]);

            if($cantidad_original > $cantidad_modificada){
                $diferencia = intval($cantidad_original) - intval($cantidad_modificada);
            }
            elseif($cantidad_original < $cantidad_modificada){
                $diferencia = intval($cantidad_modificada) - intval($cantidad_original);
            }
            elseif($cantidad_original == $cantidad_modificada){
                $diferencia = intval($cantidad_original) - intval($cantidad_modificada);
            }


            $diff = array("diferencia" => strval($diferencia));
            $merge2 = (array_merge($array[$key],$diff));
            $array_final[] = $merge2;

        }

        
        #Datos de envio para el correo electronico.
        $data= $nota_de_venta;
        $json = json_encode($array_final);
        $json_replace_space = str_replace(" ", "\u200B", $json);
        $json_replace_quotes = str_replace('\\"' , '', $json_replace_space);
        $json_replace_slash  = str_replace('/','\u2215',$json_replace_quotes);

    
        //Http::get('http://192.168.0.114:105/modificacion_nota_de_venta/'.$json_replace_slash.'/'.$data);
        Http::get('http://201.239.17.218:105/modificacion_nota_de_venta/'.$json_replace_slash."/".$data);

                  

        $array_selector_odoo = array();
        foreach ($array_stock_move as $key => $value) {

            $cantidad_odoo       = count($value);
            $id_producto_odoo    = $value["id_producto"];
            $all                 = strval( intval($value["cantidad"]) + 20   ); 
            array_push($array_selector_odoo, $value["id_producto"]."&&".$all);
        }


        $productos_a_trabajar = (array_diff($array_selector_200, $array_selector_odoo));

        foreach ($productos_a_trabajar as $key => $value) {

            $valor_posicion_max   = strpos($value, "&");
            $valor_id_final       = intval(substr($value, 0, $valor_posicion_max));
            $valor_cantidad_final = intval(substr($value, $valor_posicion_max+2,10));

            $UPDATE_STOCK_MOVE        = pg_query($con_InventorySystem,"UPDATE stock_move SET product_uom_qty = '$valor_cantidad_final' WHERE reference LIKE '%{$nota_de_venta}%' AND product_id = '{$valor_id_final}';");
            $UPDATE_STOCK_MOVE_LINE   = pg_query($con_InventorySystem, "UPDATE stock_move_line SET product_uom_qty = '{$valor_cantidad_final}' WHERE reference LIKE  '%{$nota_de_venta}%' AND product_id = '{$valor_id_final}';"); 
            $UPDATE_STOCK_MOVE_LINE_2 = pg_query($con_InventorySystem, "UPDATE stock_move_line SET qty_done = '{$valor_cantidad_final}' WHERE reference LIKE  '%{$nota_de_venta}%' AND product_id = '{$valor_id_final}';"); 

        }

        return"MODIFICACION DE LA NOTA DE VENTA EJECUTADA";
    }





//tengo que tener bien en cuenta copmo obtener los datos del producto, ojo... por que este formulario se manda antes de que se mande el formulario.
//En esta función vamos a pasar todos los parametros y sera nustra funcion para ingresar productos especificos.
public function ingreso_productos ($data){
 
    //header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    header("Allow: GET, POST, OPTIONS, PUT, DELETE");
    //$method = $_SERVER['REQUEST_METHOD'];
    

    //Parametros
    $id_producto = "";
    $descripcion_producto = "";
    $codigo_de_origen = "";  //cabe estacar que este campo no se toca por nada dle mundo
    $precio = "";
    $unidad_de_medida_original = "";
    $fecha = "";
    $ubicacion_comentario = "";    // campo "description_picking"

    //elementos faltantes 
    $stock_disponible_final = "";
    $ubicacion_numero = "";
    $inventory_id = "";
    $warehouse_id = "";
    $codigo_de_barras ="";
    $costo_producto = "";
    $codigo_producto_substr = "";


    //elementos para obtencion de datos
    $codigo_proveedor = "";
    $codigo_producto = "";





    $datos = json_decode($data); 
    foreach ($datos as $key => $value) {
        $nombre = $value->name;
        $valor  = $value->value;

        if($nombre == "form_1_Descripci_n[0]"){
            $descripcion_producto = $valor; 
        }
        elseif($nombre == "form_1_C_digo_de_Origen[0]"){
            $codigo_de_origen = $valor;
        }
        elseif($nombre == "form_1_Precio_Base__pesos_[0]"){
            $precio = $valor;    
        }
        elseif($nombre == "form_1_Unidad_de_Medida[0]"){
            $unidad_de_medida_original = $valor;
        }
        elseif($nombre == "form_1_Fecha__ltima_Recepci_n[0]"){
            $fecha = $valor;
        }
        elseif($nombre == "form_1_C_digo[0]"){
            $codigo_producto = $valor;
            $codigo_producto_substr = substr($codigo_producto, 0,5);
            $codigo_producto_value = $codigo_producto;
        }
        elseif($nombre == "form_1_Stock_F_sico[0]"){
            $stock_disponible_final = $valor;
            if($stock_disponible_final == "" OR $stock_disponible_final == ' ' OR $stock_disponible_final == '  ' OR $stock_disponible_final == NULL ){
                $stock_disponible_final = 1;   
            }
        }
        elseif($nombre == "form_1_Costo_Moneda_Nacional__pesos__ktkField[0]"){
            $costo_producto = $valor;
        }
        elseif($nombre  == "form_1_Precio_Base__pesos__ktkField[0]"){
            $precio = $valor;
            if($valor == ""){
                $precio = 1;
            }
        }
    }
    

    #Gestion unidades de medida
    $unidad_de_medida = "";
    switch ($unidad_de_medida_original) {
                        case 630:
                            $unidad_de_medida = 1;
                        break;
                        case 631:
                            $unidad_de_medida = 3;
                        break;
                        case 632:
                            $unidad_de_medida = 74;
                        break;
                        case 633:
                            $unidad_de_medida = 73;
                        break;
                        case 634:
                            $unidad_de_medida = 65;
                        break;
                        case 635:
                            $unidad_de_medida = 72;
                        break;
                        case 636:
                            $unidad_de_medida = 71;
                        break;
                        case 637:
                            $unidad_de_medida = 70;
                        break;
                        case 638:
                            $unidad_de_medida = 2;
                        break;
                        case 639:
                            $unidad_de_medida = 30;
                        break;
                        case 640:
                            $unidad_de_medida = 8;
                        break;
                        case 641:
                            $unidad_de_medida = 31;
                        break;
                        case 642:
                            $unidad_de_medida = 32;
                        break;
                        case 643:
                            $unidad_de_medida = 62;
                        break;
                        case 644:
                            $unidad_de_medida = 34;
                        break;
                        case 645:
                            $unidad_de_medida = 35;
                        break;
                        case 646:
                            $unidad_de_medida = 36;
                        break;
                        case 647:
                            $unidad_de_medida = 37;
                        break;
                        case 648:
                            $unidad_de_medida = 38;
                        break;
                        case 649:
                            $unidad_de_medida = 39;
                        break;
                        case 650:
                            $unidad_de_medida = 40;
                        break;
                        case 651:
                            $unidad_de_medida = 41;
                        break;
                        case 652:
                            $unidad_de_medida = 42;
                        break;
                        case 653:
                            $unidad_de_medida = 43;
                        break;
                        case 654:
                            $unidad_de_medida = 43;
                        break;
                        case 655:
                            $unidad_de_medida = 44;
                        break;
                        case 656:
                            $unidad_de_medida = 45;
                        break;
                        case 657:
                            $unidad_de_medida = 46;
                        break;
                        case 658:
                            $unidad_de_medida = 1;
                        break;
                        case 659:
                            $unidad_de_medida = 1;
                        break;
                        case 660:
                            $unidad_de_medida = 47;
                        break;
                        case 661:
                            $unidad_de_medida = 1;
                        break;
                        case 662:
                            $unidad_de_medida = 75;
                        break;
                        case 663:
                            $unidad_de_medida = 76;
                        break;
                        case 664:
                            $unidad_de_medida = 1;
                        break;
                        case 665:
                            $unidad_de_medida = 63;
                        break;
                        case 666:
                            $unidad_de_medida = 64;
                        break;
                        case 667:
                            $unidad_de_medida = 77;
                        break;
                        case 668:
                            $unidad_de_medida = 51;
                        break; 
                        case 669:
                            $unidad_de_medida = 50;
                        break;
                        case 670:
                            $unidad_de_medida = 1;
                        break;
                        case 671:
                            $unidad_de_medida = 49;
                        break;
                        case 672:
                            $unidad_de_medida = 48;
                        break;
                        case 673:
                            $unidad_de_medida = 1;
                        break;
                        case 674:
                            $unidad_de_medida = 54;
                        break;
                        case 675:
                            $unidad_de_medida = 53;
                        break;
                        case 676:
                            $unidad_de_medida = 55;
                        break;
                        case 677:
                            $unidad_de_medida = 1;
                        break;
                        case 678:
                            $unidad_de_medida = 1;
                        break;
                        case 679:
                            $unidad_de_medida = 56;
                        break;
                        case 680:
                            $unidad_de_medida = 76;
                        break;
                        case 681:
                            $unidad_de_medida = 57;
                        break;
                        case 682:
                            $unidad_de_medida = 78;
                        break;
                        case 683:
                            $unidad_de_medida = 59;
                        break;
                        case 684:
                            $unidad_de_medida = 1;
                        break;
                        case 685:
                            $unidad_de_medida = 60;
                        break;
                        case 686:
                            $unidad_de_medida = 61;
                        break;
                            case NULL:
                            $unidad_de_medida = 1;
                        break;
                            case 687: 
                            $unidad_de_medida = 33;
                        break;
                        case 688:
                            $unidad_de_medida = 79;            
    }
    #Fin gestion unidades de medida 


//Gestion unidades de medida (esta tiene que ir si o si).
    //$fix_im = DB::
    //$fix_unidades_de_medida = DB::connection('inventory_id')->table('uom_uom')->select('descripcion')->get();
//Fin funcion unidades_de_medida





//GESTIÓN UBICACIÓN
        if($codigo_producto_substr == '18-04' OR $codigo_producto_substr == '19-06' OR $codigo_producto_substr == '17-41' 
        OR $codigo_producto_value == '17-23-001' OR $codigo_producto_value == '17-23-003' OR $codigo_producto_value == '17-23-005' OR $codigo_producto_value == '17-23-007' 
        OR $codigo_producto_value == '17-23-020' OR $codigo_producto_value == '17-23-040' OR $codigo_producto_value == '17-23-009' OR $codigo_producto_value == '17-23-011' 
        OR $codigo_producto_value == '17-23-013' OR $codigo_producto_value == '17-23-015' OR $codigo_producto_value == '17-23-004' OR $codigo_producto_value == '17-23-032' 
        OR $codigo_producto_value == '17-23-030' OR $codigo_producto_value == '17-23-042' OR $codigo_producto_value == '17-23-044' OR $codigo_producto_value == '17-23-046' 
        OR $codigo_producto_value == '17-06-058' OR $codigo_producto_value == '17-06-332' OR $codigo_producto_value == '17-06-352' OR $codigo_producto_value == '17-06-360' 
        OR $codigo_producto_value == '17-06-050' OR $codigo_producto_value == '17-06-052' OR $codigo_producto_value == '17-06-056' OR $codigo_producto_value == '17-06-376' 
        OR $codigo_producto_value == '17-06-054' OR $codigo_producto_value == '17-06-053' OR $codigo_producto_value == '17-06-308' OR $codigo_producto_value == '17-06-346' 
        OR $codigo_producto_value == '17-06-330' ){


            $ubicacion_comentario = "2DO PISO TRUPER";
            $ubicacion_numero     = 36;
            $warehouse_id         = 5;
            $inventory_id         = 4;
            $tabla_db = "segundo_piso_truper";

        }
         elseif($codigo_producto_value == '79-11-001' OR $codigo_producto_value == '79-11-004' OR $codigo_producto_value == '79-11-040' OR $codigo_producto_value == '79-11-038' 
        OR $codigo_producto_value == '79-11-042' OR $codigo_producto_value == '53-08-022' OR $codigo_producto_value == '53-08-025' OR $codigo_producto_value == '53-08-28' 
        OR $codigo_producto_value == '53-08-034' OR $codigo_producto_value == '53-08-043' OR $codigo_producto_value == '53-08-046' OR $codigo_producto_value == '53-08-049' 
        OR $codigo_producto_value == '53-08-042' OR $codigo_producto_value == '53-08-058' OR $codigo_producto_value == '53-08-132' OR $codigo_producto_value == '53-08-134' 
        OR $codigo_producto_value == '53-08-136' OR $codigo_producto_value == '53-08-138' OR $codigo_producto_value == '53-08-140' OR $codigo_producto_value == '90-08-005' 
        OR $codigo_producto_value == '90-08-006' OR $codigo_producto_value == '90-08-008' OR $codigo_producto_value == '90-08-010' OR $codigo_producto_value == '90-08-012' 
        OR $codigo_producto_value == '39-04-010' OR $codigo_producto_value == '39-04-012' OR $codigo_producto_value == '39-04-014' OR $codigo_producto_value == '39-04-016' 
        OR $codigo_producto_value == '39-04-018' OR $codigo_producto_value == '39-04-020' OR $codigo_producto_value == '39-04-022' OR $codigo_producto_value == '39-04-024' 
        OR $codigo_producto_value == '39-04-026'){

            //$descripcion_producto = 
            $descripcion_producto = $descripcion_producto." TRUPER";
            $ubicacion_comentario = "1ER PISO TRUPER";
            $ubicacion_numero     = 30;
            $warehouse_id         = 4;
            $inventory_id         = 3;
            $tabla_db = "primer_piso_truper";

        }
        else{

            $ubicacion_comentario = "1ER PISO TRUPER";
            $ubicacion_numero     = 30;
            $warehouse_id         = 4;
            $inventory_id         = 3;
            $tabla_db = "primer_piso_truper"; 
        }
        #Fin gestion ubicacion producto 



    $id_prod = DB::connection('imatronix_ecosa')->table('producto')->select('_id')->orderBy('_id', 'desc')->limit(1)->get();
    $id_producto = $id_prod[0]->_id;
    $id_producto = $id_producto + 1;


    if(isset($id_producto)){

        //Restablecimiento de id de la tabla "stock_move"
        $prev = DB::connection('inventory_system')->table('stock_location')->select('id')->orderBy('id', 'desc')->limit(1)->get();
        $ULTIMO_ID_AUTOINCREMENT = $prev[0]->id + 1;
        $text = "ALTER SEQUENCE stock_location_id_seq RESTART WITH ".strval($ULTIMO_ID_AUTOINCREMENT).";";
        DB::connection('inventory_system')->update(DB::connection('inventory_system')->raw($text));
        //Fin restablecimiento de la tabla "stock_move"

        $fix_product_template = DB::connection('inventory_system')->table('product_template')->select('id')->where('id',$id_producto)->get();



        if(!isset($fix_product_template[0])){    

            //Insert "product_template" (aca falta cambiar los valores).
            DB::connection('inventory_system')->table('product_template')->insert([
                'id' => $id_producto,
                'message_main_attachment_id' => NULL,
                'name' => $descripcion_producto,
                'sequence' => NULL,
                'description' => $codigo_de_origen,
                'description_purchase' => NULL, 
                'description_sale' => NULL,
                'type' => 'product',
                'categ_id' => 1,
                'list_price' => $precio,
                'volume' => 0.00,
                'weight' => 0.00,
                'sale_ok' => 't',
                'purchase_ok' => 't',
                'uom_id' => $unidad_de_medida,
                'uom_po_id' => 1,
                'company_id' => NULL,
                'active' => 't',
                'color' => NULL,
                'default_code' => NULL,
                'can_image_1024_be_zoomed' => 'f',
                'has_configurable_attributes' => 'f',
                'create_uid' => 2,
                'create_date' => $fecha,
                'write_uid' => 2,
                'write_date' => $fecha,
                'sale_delay' => 0,
                'tracking' => 'none',
                'description_picking' => $ubicacion_comentario,
                'description_pickingout' => NULL,
                'description_pickingin' => NULL,
                'use_expiration_date' => 'f',
                'expiration_time' => 0,
                'use_time' => 0,
                'removal_time' => 0,
                'alert_time' => 0
            ]);
        } // Fin fix product template

        


        $fix_product_product = DB::connection('inventory_system')->table('product_product')->select('id')->where('id',$id_producto)->get();


        if(!isset($fix_product_product[0])){
            // Insert "product_product" (agregar campos).   
            DB::connection('inventory_system')->table('product_product')->insert([
                'id' => $id_producto,
                'message_main_attachment_id' => NULL,
                'default_code' => NULL,
                'active' => 't',
                'product_tmpl_id' => $id_producto,
                'barcode' => $codigo_de_barras,
                'combination_indices' => NULL,
                'volume' => NULL,
                'weight' => NULL,
                'can_image_variant_1024_be_zoomed' => 'f',
                'create_uid' => 2,
                'create_date' => $fecha,
                'write_uid' => 2,
                'write_date' => $fecha
            ]);   
       }//Fin fix product_product





    //Insert "stock_quant"
    $fix_stock_quant = DB::connection('inventory_system')->table('stock_quant')->select('id')->get();
    if(empty($fix_stock_quant)){

        DB::connection('inventory_system')->table('stock_quant')->insert([
            'id' => $id_producto,
            'product_id' => $id_producto,
            'company_id' => 1,
            'location_id' => $ubicacion_numero,
            'lot_id' => NULL,
            'package_id' => NULL,
            'owner_id' => 1,
            'quantity' => $stock_disponible_final,
            'reserved_quantity' => 0,
            'in_date' => $fecha,
            'create_uid' => 2,
            'create_date' => $fecha,
            'write_uid ' => 2,
            'write_date' => $fecha,
            'removal_date' => NULL
        ]);
    } //Fin insert "stock_quant"




    //Insert "stock_move"
    DB::connection('inventory_system')->table('stock_move')->insert([
        'name' => 'Producto nuevo agregado', 
        'sequence' => 10,
        'priority' => 0,
        'create_date' => $fecha,
        'date' => $fecha,
        'date_deadline' => NULL,
        'company_id' => 1,
        'product_id' => $id_producto,
        'description_picking' => $ubicacion_comentario,
        'product_qty' => $stock_disponible_final,
        'product_uom_qty' => $stock_disponible_final,
        'product_uom' => $unidad_de_medida,
        'location_id' => 2,
        'location_dest_id' => $ubicacion_numero,
        'partner_id' => NULL,
        'picking_id' => NULL,
        'note' => NULL,
        'state' => 'done',
        'price_unit' => 100.00,  //hay que revisar esta estupidez.
        'origin' => NULL,
        'procure_method' => 'make_to_stock',
        'scrapped' => 'f',
        'group_id' => NULL,
        'rule_id' => NULL,
        'propagate_cancel' => 't',
        'delay_alert_date' => NULL,
        'picking_type_id' => NULL,
        'inventory_id' => $inventory_id,
        'origin_returned_move_id' => NULL,
        'restrict_partner_id' => NULL,
        'warehouse_id' => $warehouse_id,
        'additional' => 'f',
        'reference' => 'Producto nuevo agregado',
        'package_level_id' => NULL,
        'next_serial' => NULL,
        'next_serial_count' => NULL,
        'orderpoint_id' => NULL,
        'create_uid' => 2,
        'write_uid' => 2,
        'write_date' => $fecha
    ]);

    //Fin insert "stock_move"





    //Instert "ir_propert"
    $fix_ir_property = DB::connection('inventory_system')->table('ir_property')->select('id')->where('id', $id_producto)->get();

    if(empty($fix_ir_property)){

        DB::connect('inventory_system')->table('ir_property')->insert([
            'id' => $id_producto, 
            'name' => 'standard_price', 
            'res_id' => 'product.product,'.strval($id_producto),
            'company_id' => 1, 
            'fields_id' => 2714, 
            'value_float' => $costo_producto, 
            'value_integer' => NULL, 
            'value_text' => NULL, 
            'value_binary' => NULL,
            'value_reference' => NULL,
            'value_datetime' => NULL,
            'type' => 'float',
            'create_uid' => 2,
            'create_date' => $fecha,
            'write_uid' => 2,
            'write_date' => $fecha
        ]);

    }//Fin insert "ir_property"



    //API CODIGOS DE BARRA DUN14 Y DUN16
     Http::get('http://192.168.0.154:105/codigos_de_barra_truper/'.$codigo_de_origen);
    

    // */

    } //Cierre if isset $id_producto

    return "INGRESO DE PRODUCTOS EJECUTADO";
}



























/* ANEXO PRODUCTOS REQUERIMIENTO JEFE */
public function productos (){
    $productos = DB::connection('imatronix_ecosa')->table('producto')->get();    
    return view('productos',['productos' => $productos]);
}



/* MODULO DE CONTABILIDAD → ANEXO PARA LUEGO DEJAR DE LADO */


public function productos_modulo_contabilidad ($fecha){

    if($fecha==1){
        $arg = '2022-01-';
    }
    else{
        $arg = $fecha;     
    }


/*
SELECT producto.codigo,
producto.descripcion,
producto.precio_base_pesos,
producto.costo_moneda_extranjera,
SUM(cantidad) AS cantidad_vendida,
to_char(SUM (detalle_venta.total_pesos), '999G999G999G999G999') AS total_pesos
FROM detalle_venta
JOIN producto ON detalle_venta.producto = producto._id 
JOIN factura ON detalle_venta.factura_id = factura._id
WHERE factura.fecha_emision LIKE '%2022-01-%'
GROUP BY producto.codigo, producto.descripcion, producto.precio_base_pesos, producto.costo_moneda_extranjera
ORDER BY cantidad_vendida DESC;


   $query = DB::connection('imatronix_ecosa')
   ->table('detalle_venta')
   ->select('producto.codigo',
            'producto.descripcion',
            'producto.precio_base_pesos AS precio_producto',
            'producto.costo_moneda_extranjera AS costo_moneda_extranjera',
            DB::raw('SUM(detalle_venta.cantidad) AS cantidad_vendida'),
            DB::raw("to_char(SUM (detalle_venta.total_pesos), '999G999G999G999G999') AS total_pesos"), 
            'factura.fecha_emision')
   ->join('factura', 'detalle_venta.factura_id', '=', 'factura._id')
   ->join('producto', 'detalle_venta.producto', '=', 'producto._id')
   ->where('factura.fecha_emision', 'LIKE','%'.$arg.'%')
   ->groupBy('descripcion', 'producto.codigo', 'producto.precio_base_pesos', 'factura.fecha_emision', 'producto.costo_moneda_extranjera')
   ->orderBy('cantidad_vendida','DESC')
   ->get();




*/


   $query = DB::connection('imatronix_ecosa')
   ->table('detalle_venta')
   ->select('producto.codigo',
            'producto.descripcion',
            'producto.precio_base_pesos AS precio_producto',
            'producto.costo_moneda_extranjera AS costo_moneda_extranjera',
            DB::raw('CAST(SUM(detalle_venta.cantidad) AS INTEGER) AS cantidad_vendida'),
            DB::raw("to_char(SUM (detalle_venta.total_pesos), '999G999G999G999G999') AS total_pesos"))
   ->join('factura', 'detalle_venta.factura_id', '=', 'factura._id')
   ->join('producto', 'detalle_venta.producto', '=', 'producto._id')
   ->where('factura.fecha_emision', 'LIKE','%'.$arg.'%')
   ->groupBy('descripcion', 'producto.codigo', 'producto.precio_base_pesos', 'producto.costo_moneda_extranjera')
   ->orderBy('cantidad_vendida','DESC')
   ->get();












   
 

    return view('productos_contabilidad', ['productos' => $query]);
}





}
