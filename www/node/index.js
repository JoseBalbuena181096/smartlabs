var mysql = require('mysql');
var mqtt = require('mqtt');
var serial_loan_user=null;
var count_loan_card = 0;
//CREDENCIALES MYSQL

var con = mysql.createConnection({
    host: "192.168.0.100",
    user: "root",
    password: "emqxpass",
    database: "emqx",
    port: '4000'
});

//CREDENCIALES MQTT
var options = {
    port: 1883,
    host: '192.168.0.100',
    clientId: 'access_control_server_' + Math.round(Math.random() * (0- 10000) * -1) ,
    username: 'jose',
    password: 'public',
    keepalive: 60,
    reconnectPeriod: 1000,
    protocolId: 'MQIsdp',
    protocolVersion: 3,
    clean: true,
    encoding: 'utf8'
  };
  

var client = mqtt.connect("mqtt://192.168.0.100", options);
//SE REALIZA LA CONEXION
client.on('connect', function () {
    console.log("Conexión  MQTT Exitosa!!");
    client.subscribe('+/#', function (err) {
      console.log("Subscripción exitosa!")
    });
});
  
//CUANDO SE RECIBE MENSAJE
client.on('message', function (topic, message) {
  console.log("Mensaje recibido desde -> " + topic + " Mensaje -> " + message.toString());

  var topic_splitted = topic.split("/");
  var serial_number = topic_splitted[0];
  var query = topic_splitted[1];

  if(query=="access_query"){
    var rfid_number = message.toString();
    //hacemos la consulta
    //var query = "SELECT * FROM cards_habs WHERE cards_number = '" + rfid_number + "' AND devices_serie = '" + serial_number + "'";
    var query = "SELECT * FROM cards_habs WHERE cards_number = '" + rfid_number+ "'";
    con.query(query, function (err, result_, fields) {
      console.log(result_);
      if (err) throw err;
      //consultamos rfid y devolvemos mensaje
      if(result_.length==1){
        //GRANTED
        client.publish(serial_number + "/user_name",result_[0].hab_name);
        
        // Nombre del dispositivo específico que estás buscando
        var trafficDevice = serial_number;
        // Consulta para obtener la última inserción para el dispositivo específico
        const selectQuery = `
          SELECT *
          FROM traffic
          WHERE traffic_device = ? 
          ORDER BY traffic_date DESC
          LIMIT 1
        `;
        // Inserta una nueva fila con traffic_state 1 si no existe, o actualiza según los criterios
        con.query(selectQuery, [trafficDevice], (error, results) => {
          if (err) throw err;
          if (results.length === 0) {
            // No hay registros para el dispositivo, inserta un nuevo elemento por defecto
            const insertQuery = `
              INSERT INTO traffic (traffic_date, traffic_hab_id, traffic_device, traffic_state)
              VALUES (CURRENT_TIMESTAMP, ?, ?, 1)
            `;
            con.query(insertQuery, [result_[0].hab_id, trafficDevice], (insertError, insertResults) => {
              if (insertError) {
                console.error('Error al insertar un nuevo elemento:', insertError);
              } 
              else {
                console.log('Nuevo elemento insertado con éxito');
              }
            });
            client.publish(serial_number + "/command","granted1");
            console.log("Acceso permitido a..." + result_[0].hab_name);
          } 
          else {
            // Hay un registro para el dispositivo, actualiza según los criterios especificados
            const lastTrafficState = results[0].traffic_state;
            // Determina el nuevo valor para traffic_state
            const newTrafficState = (lastTrafficState === 1) ? 0 : 1;
            // Inserta una nueva fila con el nuevo valor de traffic_state
            const updateQuery = `
              INSERT INTO traffic (traffic_date, traffic_hab_id, traffic_device, traffic_state)
              VALUES (CURRENT_TIMESTAMP, ?, ?, ?)
            `;
            con.query(updateQuery, [result_[0].hab_id, trafficDevice, newTrafficState], (updateError, updateResults) => {
              if (updateError) {
                console.error('Error al insertar un nuevo elemento con actualización de traffic_state:', updateError);
              } 
              else {
                console.log('Nuevo elemento insertado con éxito');
              }
            });
            if(newTrafficState){
              client.publish(serial_number + "/command","granted1");
              console.log("Acceso permitido a..." + result_[0].hab_name);
            }
            else{
              client.publish(serial_number + "/command","granted0");
              console.log("Acceso permitido a..." + result_[0].hab_name);
            }
          }
        });
       // var query = "INSERT INTO `traffic` (`traffic_hab_id`,`traffic_device`) VALUES (" + result[0].hab_id +","+serial_number+ ");";
       // con.query(query, function (err, result, fields) {
       //   if (err) throw err;
       //   console.log("Ingreso registrado en 'TRAFFIC' ");
       // });
      }else{
        //REFUSED
        client.publish(serial_number + "/command","refused");
      }

    });

  }

  // Becarios
  if(query=="scholar_query"){
    var rfid_number = message.toString();
    //hacemos la consulta
    //var query = "SELECT * FROM cards_habs WHERE cards_number = '" + rfid_number + "' AND devices_serie = '" + serial_number + "'";
    var query = "SELECT * FROM cards_habs WHERE cards_number = '" + rfid_number+ "'";
    con.query(query, function (err, result_, fields) {
      console.log(result_);
      if (err) throw err;
      //consultamos rfid y devolvemos mensaje
      if(result_.length==1){
        //GRANTED
        client.publish(serial_number + "/user_name",result_[0].hab_name);
        
        // Nombre del dispositivo específico que estás buscando
        var trafficDevice = serial_number;
        // Consulta para obtener la última inserción para el dispositivo específico
        const selectQuery = `
          SELECT *
            FROM traffic
            WHERE traffic_hab_id = ?
            AND traffic_device = ?
            ORDER BY traffic_date DESC
            LIMIT 1
        `;
        // Inserta una nueva fila con traffic_state 1 si no existe, o actualiza según los criterios
        con.query(selectQuery, [result_[0].hab_id, trafficDevice], (error, results) => {
          if (err) throw err;
          if (results.length === 0) {
            // No hay registros para el dispositivo, inserta un nuevo elemento por defecto
            const insertQuery = `
              INSERT INTO traffic (traffic_date, traffic_hab_id, traffic_device, traffic_state)
              VALUES (CURRENT_TIMESTAMP, ?, ?, 1)
            `;
            con.query(insertQuery, [result_[0].hab_id, trafficDevice], (insertError, insertResults) => {
              if (insertError) {
                console.error('Error al insertar un nuevo elemento:', insertError);
              } 
              else {
                console.log('Nuevo elemento insertado con éxito');
              }
            });
            client.publish(serial_number + "/command","granted1");
            console.log("Becario ingreso ..." + result_[0].hab_name);
          } 
          else {
            // Hay un registro para el dispositivo, actualiza según los criterios especificados
            const lastTrafficState = results[0].traffic_state;
            // Determina el nuevo valor para traffic_state
            const newTrafficState = (lastTrafficState === 1) ? 0 : 1;
            // Inserta una nueva fila con el nuevo valor de traffic_state
            const updateQuery = `
              INSERT INTO traffic (traffic_date, traffic_hab_id, traffic_device, traffic_state)
              VALUES (CURRENT_TIMESTAMP, ?, ?, ?)
            `;
            con.query(updateQuery, [result_[0].hab_id, trafficDevice, newTrafficState], (updateError, updateResults) => {
              if (updateError) {
                console.error('Error al insertar un nuevo elemento con actualización de traffic_state:', updateError);
              } 
              else {
                console.log('Nuevo elemento insertado con éxito');
              }
            });
            if(newTrafficState){
              client.publish(serial_number + "/command","granted1");
              console.log("Acceso permitido a..." + result_[0].hab_name);
            }
            else{
              client.publish(serial_number + "/command","granted0");
              console.log("Acceso permitido a..." + result_[0].hab_name);
            }
          }
        });
       // var query = "INSERT INTO `traffic` (`traffic_hab_id`,`traffic_device`) VALUES (" + result[0].hab_id +","+serial_number+ ");";
       // con.query(query, function (err, result, fields) {
       //   if (err) throw err;
       //   console.log("Ingreso registrado en 'TRAFFIC' ");
       // });
      }else{
        //REFUSED
        client.publish(serial_number + "/command","refused");
      }

    });

  }


  if(query=="loan_queryu"){
    var rfid_number = message.toString();
    //hacemos la consulta
    console.log("user", rfid_number);
    //var query = "SELECT * FROM cards_habs WHERE cards_number = '" + rfid_number + "' AND devices_serie = '" + serial_number + "'";
    var query = "SELECT * FROM cards_habs WHERE cards_number = '" + rfid_number+ "'";
    con.query(query, function (err, result_, fields) {
      console.log(result_);
      if (err) throw err;
      //consultamos rfid y devolvemos mensaje
      if(result_.length==1){
        //GRANTED
        if(count_loan_card===1){
          client.publish(serial_number + "/command","unload");
          count_loan_card = 0;
          serial_loan_user = null;
        }
        else{
          client.publish(serial_number + "/user_name",result_[0].hab_name);
          client.publish(serial_number + "/command","found");
          serial_loan_user = result_;
          count_loan_card += 1;
        }
      }
      else{
        client.publish(serial_number + "/command","nofound");
      }
    });

  }

  if(query=="loan_querye" && count_loan_card !==0 && serial_loan_user!==null){
    var rfid_number = message.toString();
    var query_eq = "SELECT * FROM equipments WHERE equipments_rfid = '" + rfid_number+ "'";
    con.query(query_eq, function (err, result_eq, fields) {
      
      if (err) throw err;
      //consultamos rfid y devolvemos mensaje
      if(result_eq.length==1){
        client.publish(serial_number + "/user_name",result_eq[0].equipments_name);
        //GRANTED
        //console.log(result_[0].equipments_name);
        console.log(result_eq);
        const selectQueryLoans = `
          SELECT *
            FROM loans
            WHERE loans_equip_rfid = ?
            ORDER BY loans_date DESC
            LIMIT 1
        `;
        con.query(selectQueryLoans, [result_eq[0].equipments_rfid], (error, resultsLoan) => {
          if (err) throw err;
          if (resultsLoan.length === 0) {
            // No hay registros para el dispositivo, inserta un nuevo elemento por defecto
            const insertQuery = `
              INSERT INTO loans (loans_date, loans_hab_rfid, loans_equip_rfid, loans_state)
              VALUES (CURRENT_TIMESTAMP, ?, ?, 1)
            `;
            con.query(insertQuery, [serial_loan_user[0].cards_number, result_eq[0].equipments_rfid], (insertError, insertResults) => {
              if (insertError) {
                console.error('Error al insertar un nuevo elemento:', insertError);
              } 
              else {
                console.log('Nuevo elemento insertado con éxito');
                 //client.publish(serial_number + "/command","granted1");
                console.log("Prestamo exitoso ...");
                client.publish(serial_number + "/command","prestado");
              }
            });
          } 
          else if(resultsLoan.length > 0) {
            // Hay un registro para el dispositivo, actualiza según los criterios especificados
            const lastLoanState = resultsLoan[0].loans_state;
            // Determina el nuevo valor para traffic_state
            const newLoanState = (lastLoanState === 1) ? 0 : 1;
            // Inserta una nueva fila con el nuevo valor de traffic_state
            const updateQuery = `
              INSERT INTO loans (loans_date, loans_hab_rfid, loans_equip_rfid, loans_state)
              VALUES (CURRENT_TIMESTAMP, ?, ?, ?)
            `;
            con.query(updateQuery, [serial_loan_user[0].cards_number, resultsLoan[0].loans_equip_rfid, newLoanState], (updateError, updateResults) => {
              if (updateError) {
                console.error('Error al insertar un nuevo elemento con actualización de traffic_state:', updateError);
              } 
              else {
                console.log('Nuevo elemento insertado con éxito');
              }
              if(newLoanState===0){
                client.publish(serial_number + "/command","devuelto");
              }
              else if(newLoanState===1){
                client.publish(serial_number + "/command","prestado");
              }
            });
          }
        });
      }
      else{
        client.publish(serial_number + "/command","nofound");
      }
    });
  }
  else if(query=="loan_querye" && serial_loan_user===null){
    client.publish(serial_number + "/command","nologin");
  }

  if (topic == "values"){
    var msg = message.toString();
    var sp = msg.split(",");
    var temp1 = sp[0];
    var temp2 = sp[1];
    var volts = sp[2];
    //hacemos la consulta para insertar....
    var query = "INSERT INTO `emqx`.`data` (`data_temp1`, `data_temp2`, `data_volts`) VALUES (" + temp1 + ", " + temp2 + ", " + volts + ");";
    con.query(query, function (err, result, fields) {
      if (err) throw err;
      console.log("Fila insertada correctamente");
    });
  }
});


//nos conectamos
con.connect(function(err){
  if (err) throw err;

  //una vez conectados, podemos hacer consultas.
  console.log("Conexión a MYSQL exitosa!!!")

  //hacemos la consulta
  var query = "SELECT * FROM devices WHERE 1";
  con.query(query, function (err, result, fields) {
    if (err) throw err;
    if(result.length>0){
      console.log(result);
    }
  });

});

//para mantener la sesión con mysql abierta
setInterval(function () {
  var query ='SELECT 1 + 1 as result';
  con.query(query, function (err, result, fields) {
    if (err) throw err;
  });
  console.log("esto es ",serial_loan_user);
}, 5000);