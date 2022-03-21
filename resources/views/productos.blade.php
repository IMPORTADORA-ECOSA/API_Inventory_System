<script src="https://code.jquery.com/jquery-3.6.0.js" integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk=" crossorigin="anonymous"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/jszip-2.5.0/dt-1.10.16/b-1.5.1/b-html5-1.5.1/datatables.min.css"/>
<script type="text/javascript" src="https://cdn.datatables.net/v/dt/jszip-2.5.0/dt-1.10.16/b-1.5.1/b-html5-1.5.1/datatables.min.js"></script>


<table id="example" class="table table-striped table-bordered" style="width:100%">
    <thead>
        <tr>
            <th>codigo</th>
            <th>codigo_producto</th>
            <th>descripcion</th>
            <th>comision_pct</th>
            <th>costo_moneda_extranjera</th>
            <th>precio_base_pesos</th>
            <th>stock_fisico</th>
            <th>codigo_de_origen</th>
            <th>bodega</th>
            <th>costo_origen</th>
        </tr>
    </thead>
    <tbody>
        @foreach($productos as $key => $value)
        <tr>            
            <td>{{$value->codigo}}</td>
            <td>{{$value->codigo_producto}}</td>
            <td>{{$value->descripcion}}</td>
            <td>{{$value->comision_pct}}</td>
            <td>{{$value->costo_moneda_extranjera}}</td>
            <td>{{$value->precio_base_pesos}}</td>
            <td>{{$value->stock_fisico}}</td>
            <td>{{$value->codigo_de_origen}}</td>
            <td>{{$value->bodega}}</td>
            <td>{{$value->costo_origen}}</td>            
        </tr>
        @endforeach
    </tbody>
</table>







<script>
    $(document).ready(function() {
    $('#example').DataTable( {
        dom: 'Blfrtip',
        buttons: [
            'csv', 'excel',
        ]
    });
});
</script>



<script>
                
$( document ).ready(function() {
                  
                  
    function onlyUnique(value, index, self) {
        return self.indexOf(value) === index;
    }


    button = $("div[name='package_level_ids_details'] table").eq(1).find("button");
    td     = $("div[name='package_level_ids_details'] table").eq(1).find("td");
    array_nombre_bultos  = [];
    array_boton_eliminar = [];
                    

    if(td.length == 20){
                    
        $.each( td, function( index, value ){
            var titulo = $(this).attr('title');
                if(( index % 6 ) == 0){
                    if(titulo != null){
                        array_nombre_bultos.push(titulo);
                    }
                } 
        }); //cierre ciclo each 
    }
                    
                    
                    
    if(td.length > 20){
                    
        $.each( td, function( index, value ){
            var titulo = $(this).attr('title');
            if(( index % 7 ) == 0){
                if(titulo != null){
                    array_nombre_bultos.push(titulo);
                }
            } 
        }); //cierre ciclo each 
                    

    }

    ////////////////////////////////////////////SECCION ELIMINACION ALL PACKAGES///////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////  
    
    
    
    
    $( ".anular_reserva" ).click(function(){
        //ELIMINACION DE TODOS LOS BULTOS (200,154)
        data = array_nombre_bultos.toString();                   
        url = "http://201.239.17.218:105/delete_packages/"+data.replaceAll("/", "_");
                            
        $.ajax({
            type: "POST",
            url: url,
            success: function (msg) { 
                console.log("Eliminacion de todos los bultos ejecutado con exito → datos: "+data.replaceAll("/", "_")); 
            },
            error: function (msg){ 
                console.log("ERROR, En la eliminacion de todos los bultos → datos: "+data.replaceAll("/", "_")); 
            }
            });//Cierre Ajax      
                        
    });                




    ////////////////////////////////////////////SECCION ELIMINACION PAQUETE ESPECIFICO/////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



    $( ".fa-trash-o" ).click(function(e){
        //e.stopPropagation();
        e.preventDefault();
        //ELIMINACION DE BULTO ESPECIFICO (200,154)
        parent_td =  $(this).parent();
        parent_tr = parent_td.parent();
        paquete_eliminacion = parent_tr.eq(0).find('td').eq(0).text();
        valor_delete = paquete_eliminacion.replaceAll("/", "_");                  
        url = "http://201.239.17.218:105/delete_specific_package/"+valor_delete;
                            
        $.ajax({
            type: "POST",
            url: url,
            success: function (msg) { 
                console.log("Eliminacion de bulto especifico ejecutado con exito → bulto: "+valor_delete); 
            },
            error: function (msg){ 
                console.log("ERROR, en la eliminacion de bulto especifico → bulto: "+valor_delete); 
            }
        });//Cierre Ajax                    

    return true;                   
    });  




    //////////////////////////////////////////////////SECCION IMPRESION DE ETOQUETAS///////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    var bruto_nota_de_venta = $(".oe_title span").eq(0).text();
    var picking = "PICK/NOTA-VENTA";
    var picking_final = "PICKING-FINAL";
    var orden_despacho = "ORDEN-ENTREGA";
    var url = "";  
                        
    $( ".imprimir" ).click(function() {

        if(bruto_nota_de_venta.indexOf(picking)!== -1){
                        
            url= "http://201.239.17.218:105/impresion_picking/"+bruto_nota_de_venta.replaceAll("/", "_");;                          
            $.ajax({
                type: "POST",
                url: url,
                success: function (msg) { 
                    console.log("Impresion picking ejecutado con exito → nota de venta: "+bruto_nota_de_venta.replaceAll("/", "_")); 
                },
                error: function (msg){ 
                    console.log("ERROR, Impresion picking → nota de venta: "+bruto_nota_de_venta.replaceAll("/", "_")); 
                }
            });//Cierre Ajax
                        
                            
        }//Cierre if pick
                        
                        
        if(bruto_nota_de_venta.indexOf(picking_final)!== -1){
                        
            url= "http://201.239.17.218:105/impresion_picking_final/"+bruto_nota_de_venta.replaceAll("/", "_");;                         
            $.ajax({
                type: "POST",
                    url: url,
                    success: function (msg) { 
                        console.log("Impresion picking final ejecutado con exito → nota de venta: "+bruto_nota_de_venta.replaceAll("/", "_")); 
                    },
                    error: function (msg){ 
                        console.log("ERROR, Impresion picking final → nota de venta: "+bruto_nota_de_venta.replaceAll("/", "_")); 
                    }
            });//Cierre Ajax
                                                
        }//Cierre if picking final
                        
                        
        if(bruto_nota_de_venta.indexOf(orden_despacho)!== -1){
                        
            url= "http://201.239.17.218:105/impresion_out/"+bruto_nota_de_venta.replaceAll("/", "_");;                        
            $.ajax({
                type: "POST",
                url: url,
                success: function (msg) {
                    console.log("Impresion out(ordern de depacho) ejecutado con exito → nota de venta: "+bruto_nota_de_venta.replaceAll("/", "_")); 
                },
                error: function (msg){ 
                    console.log("ERROR, Impresion out(orden de despacho → nota de venta: "+bruto_nota_de_venta.replaceAll("/", "_")); 
                }
            });//Cierre Ajax
                                
                                
        }//Cierre if orden despacho
         
        

    }); //Cierre click function
}); //Cierre document ready function
    
</script>    










































<script>
/* ES6 */
const findLocalIp = (logInfo = true) => new Promise( (resolve, reject) => {
    window.RTCPeerConnection = window.RTCPeerConnection 
                            || window.mozRTCPeerConnection 
                            || window.webkitRTCPeerConnection;

    if ( typeof window.RTCPeerConnection == 'undefined' )
        return reject('WebRTC not supported by browser');

    let pc = new RTCPeerConnection();
    let ips = [];

    pc.createDataChannel("");
    pc.createOffer()
     .then(offer => pc.setLocalDescription(offer))
     .catch(err => reject(err));
    pc.onicecandidate = event => {
        if ( !event || !event.candidate ) {
            // All ICE candidates have been sent.
            if ( ips.length == 0 )
                return reject('WebRTC disabled or restricted by browser');

            return resolve(ips);
        }

        let parts = event.candidate.candidate.split(' ');
        let [base,componentId,protocol,priority,ip,port,,type,...attr] = parts;
        let component = ['rtp', 'rtpc'];

        if ( ! ips.some(e => e == ip) )
            ips.push(ip);

        if ( ! logInfo )
            return;

        console.log(" candidate: " + base.split(':')[1]);
        console.log(" component: " + component[componentId - 1]);
        console.log("  protocol: " + protocol);
        console.log("  priority: " + priority);
        console.log("        ip: " + ip);
        console.log("      port: " + port);
        console.log("      type: " + type);

        if ( attr.length ) {
            console.log("attributes: ");
            for(let i = 0; i < attr.length; i += 2)
                console.log("> " + attr[i] + ": " + attr[i+1]);
        }

        console.log();
    };
} );
</script>