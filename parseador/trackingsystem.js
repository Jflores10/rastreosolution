#!/usr/bin/env node

'use strict';

const net = require('net');//Loading net library from nodejs
const moment = require('moment');//Loading moment library used to manage dates
const PORT = 8085;//Declaring tcp server's port //secure 8089//tracking 8085
const connection = 'mongodb://trackingsystem:uVC7x254i1VJ@127.0.0.1:27017/dbtrackingsystem?authSource=admin';//securetrack';//Connection string for mongo
const GTHBD = 'GTHBD';//String that represents hearbeat data
const GTDAT = 'GTDAT';//String that represents counter data
const GTFRI = 'GTFRI';//String that represents normal data
const GTGEO = 'GTGEO';//String that represents control points data
const GPRMC = 'GPRMC';//String that represents GPTMC devices
const GTDIS = 'GTDIS'; //TRAMA APERTURA DE PUERTAS
const GTMPN = 'GTMPN'; //TRAMA MOVIL ENCENDIDO 2 conecta GV300
const GTMPF = 'GTMPF'; //TRAMA MOVIL APAGO 2 conecta GV300
const GTIGF = 'GTIGF'; //TRAMA MOVIL APAGADO GV300
const GTIGN = 'GTIGN'; //TRAMA MOVIL ENCENDIDO GV300
const GTDTT = 'GTDTT'; //TRAMA PUERTAS ADICIONALES GV300
const GTDTTDGT = 'DGT'; //TRAMA PUERTAS ADICIONALES GV300 DGT
const ADMIN = 'ADMIN';
const GTLOG = 'GTLOG'; //TRAMA LOGS
const ATM = '*ATM*';
const DATE_FORMAT = 'YYYY-MM-DD HH:mm:ss';//Formato de fecha
const DEVICE_DATE_FORMAT = 'YYYYMMDDHHmmss';//Format provenient from device
const GPRMC_DATE_FORMAT = 'DDMMYYHHmmss';//Format provenient from GPRMC device
const restartHour = 4;//Hour when the counters are initialized
const restartMinute = 0;//Minutes when the counters are initialized
const restartSecond = 0;//Segundos when the counters are initialized
const restartMilisecond = 0;//Miliseconds when the counters are initialized
const nodemailer = require('nodemailer');
const ACK = 'ACK'; //RESPUESTA
const GTALC = 'GTALC'; //RESPUESTA GTALC
const schedule = require('node-schedule');
const correoinfinty = 'management.infinity.fleets@gmail.com';
//const correokimera = 'jorge.molina@kimerasoft-ec.com';
const debug = false;
const ObjectID = require('mongodb').ObjectID;
var MongoClient = require('mongodb').MongoClient, dbTrackingSystem = null;//Loading database client

var socketArray = [];//Declaring socket array for all default clients
var logsAdmin = [];

/*
  This event is used to get a socket from the current array
*/
function getSocket(imei) {
    for (var i = 0; i < socketArray.length; i++) {
        if (socketArray[i].imei === imei)
            return socketArray[i];
    }
    return null;
}

function sendLogsToAdminSockets(data) {
    let socketsToClear = [];
    for (let i = 0; i < logsAdmin.length; i++) {
        try {
            if (logsAdmin[i] && logsAdmin[i].writable) {
                logsAdmin[i].write(`${data}\n`);
            }
            else {
                socketsToClear.push(logsAdmin[i]);
            }
        }
        catch (e) {
            socketsToClear.push(logsAdmin[i]);
            console.log(e);
        }
    }
    logsAdmin = logsAdmin.filter(socket => !socketsToClear.includes(socket));
}

/*
  This event is used to get the speed as a valid decimal value
*/
function getSpeed(speed) {
    if (speed != '' && speed != null && !isNaN(speed))
        return (parseFloat(speed) * 1.85);
    return 0;
}

/*
  This event is used to transform the coordinates given in DMS format
*/
function getCoordinates(value, orientation, latitud) {
    if (value != '' && value != null && !isNaN(value)) {
        let sign = (orientation === 'N' || orientation === 'E') ? 1 : -1;
        let degrees = parseFloat(value.substring(0, ((latitud) ? 2 : 3)));
        let minutes = parseFloat(value.substring(((latitud) ? 2 : 3), value.length));
        let coord = (degrees + ((minutes) / 60)) * sign;
        return coord;
    }
    return 0;
}

/*
  Convert to a valid integer any string
*/
function toInteger(value) {
    return (value == '' || isNaN(value)) ? 0 : parseInt(value);
}

/*
  Convert to a valid float any string
*/
function toFloat(value) {
    return (value == '' || isNaN(value)) ? 0 : parseFloat(value);
}

function toDecimalHex(value) {
    let dec = parseInt(value, 16);
    return isNaN(dec) ? 0 : dec;
}

/*
  Get the time needed to restart the counter
*/
function getTimeToRestartCounter() {
    var today = new Date();
    var tomorrow = new Date();
    tomorrow.setHours(restartHour);
    tomorrow.setMinutes(restartMinute);
    tomorrow.setSeconds(restartSecond);
    tomorrow.setMilliseconds(restartMilisecond);
    if (today.getHours() >= restartHour && today.getMinutes() >= restartMinute &&
        today.getSeconds() >= restartSecond && today.getMilliseconds() >= restartMilisecond)
        tomorrow.setDate(tomorrow.getDate() + 1);
    var secs = (tomorrow.getTime() - today.getTime());
    return secs;
}

function restartCounter() {
    /*
    * Iterating each unidad in order to update it
    */
    dbTrackingSystem.collection('unidads').find({}).each(function (err, document) {
        if (err)
            console.log(err);
        else {
            /*
                Updating the current iterated document
            */
            if (document != null) {
                dbTrackingSystem.collection('unidads').updateOne({
                    _id: document._id
                }, {
                    $set: {
                        contador_diario: 0,
                        contador_inicial: document.contador_total,
                        contador_diario_sensor_2: 0,
                        contador_inicial_sensor_2: document.contador_total_sensor_2,
                        contador_diario_sensor_3: 0,
                        contador_inicial_sensor_3: document.contador_total_sensor_3
                    }
                }, function (err, result) {
                    if (err)
                        console.log(err);
                });
            }
        }
    });
    // setTimeout(restartCounter, getTimeToRestartCounter());//Executing the restarter task
}

MongoClient.connect(connection, { useUnifiedTopology: true }, function (error, client) {
    if (error)//If there is error, the program ends and shows the error
        console.log(error);
    else //If there is no error, the program continues
    {
        let server = net.createServer(onClientConnected);//Initialiazing the server and assigning the method to receive clients
        // server.maxConnections = 50;
        server.listen(PORT);//Starting the server
        dbTrackingSystem = client.db('dbtrackingsystem');
        // dbTrackingSystem = db;//Assigning the global variable for the database during all program
        // setTimeout(restartCounter, getTimeToRestartCounter());
        let secuencia = "0 0 4 * * *";
        schedule.scheduleJob(secuencia, function () {
            restartCounter();
        });
    }
});
/*
This event is triggered when a new client is connected
*/
function onClientConnected(socket) {
    let clientName = `${socket.remoteAddress}:${socket.remotePort}`;//Name of the client, it contains de ip and the port
    /*
        Determining the event triggered when a client sends a message
    */
    socket.on('data', (data) => {
        let message = data.toString();//Getting the message in a string variable
        sendLogsToAdminSockets(message);//Sending the message to admin sockets
        let imeiIndex = 2;
        //console.log(message);

        // if (message.includes('862894021341824')) {
        //     console.log(message);
        // }
        try {
            if (!message.includes(ADMIN)) {
                let array = message.split(',');
                let currentSocket = getSocket(array[imeiIndex]);
                if (currentSocket === null)
                    socketArray.push({ imei: array[imeiIndex], socket: socket });
                else
                    currentSocket.socket = socket;
            }
            if (message.includes(GTHBD) && !message.includes(ACK))//If the message contains heartbeat data
            {
                let count = message.split(',')[5].split('$')[0];//We get the counter of the device
                let heartbeat = `+SACK:GTHBD,,${count}$\n`;//Preparing the message to write to the client
                socket.write(heartbeat);//Writting the heartbeat response
            }
            else if (message.includes(GTFRI) && !message.includes(ACK)) {
                let imei = 2;//Index for imei
                let voltage = 4;//Index for voltage
                let speed = 8;//Index for speed
                let angle = 9;//Index for angle
                let height = 10;//Index for height
                let longitude = 11;//Index for longitude
                let latitude = 12;//Index for latitude
                let datetime = 13;//Index for datetime
                let battery = 23;//Index for battery
                let status = 24;//Index for status
                let sentTime = 28;//Index for sent time
                let data = message.split(',');//Array wich contains the data from device
                let fechaGPS = toInteger(data[datetime]);
                let velocidad_unidad_permitida;
                let email_send;
                let message_notification;
                let cooperativa_descripcion;
                let mileage = 17;//Index for km
                /*
                    We get and update the unidad from unidads collection
                */
                dbTrackingSystem.collection('unidads').findOneAndUpdate({ imei: data[imei], estado: 'A' },
                    {
                        $set: {
                            estado_movil: (toInteger(data[status]) >= 420000) ? 'M' : 'D',
                            latitud: toFloat(data[latitude]),
                            longitud: toFloat(data[longitude]),
                            voltaje: toFloat(data[voltage]),
                            velocidad_actual: toFloat(data[speed]),
                            mileage: toDecimalHex(data[mileage]),
                            bateria: toFloat(data[battery]),
                            is_atm: (message.includes(ATM) ? 1 : 0),
                            angulo: toInteger(data[angle]),
                            fecha_gps: (fechaGPS != 0) ? (moment(data[datetime], DEVICE_DATE_FORMAT).toDate()) : new Date(),
                            fecha: new Date()
                        }
                    }, { returnNewDocument: true },
                    function (err, document) {
                        if (err)//If there is a database error, show the message
                            console.log(err);
                        else //If there is no error
                        {
                            if (document != undefined && document != null) //If unidad was updated and taken
                            {
                                /*
                                    We create a new record for recorridos collection
                                */
                                if (document.value != undefined && document.value != null) {

                                    if (document.value.velocidad != null && document.value.velocidad != undefined && document.value.velocidad != '') {
                                        velocidad_unidad_permitida = toFloat(document.value.velocidad);
                                        if (document.value.control_velocidad) {
                                            if (document.value.velocidad_actual > velocidad_unidad_permitida) {
                                                dbTrackingSystem.collection('cooperativas').findOne({ _id: new ObjectID(document.value.cooperativa_id) },
                                                    function (err, document_coop) {
                                                        if (err)
                                                            console.log(err);
                                                        else {
                                                            if (document_coop != null && document_coop != undefined) {
                                                                email_send = document_coop.email;
                                                                cooperativa_descripcion = document_coop.descripcion;
                                                            }

                                                            dbTrackingSystem.collection('users').find({
                                                                estado: 'A',
                                                                unidades_pertenecientes: { $in: [document.value._id] }
                                                            }).toArray(function (err, document_users) {
                                                                if (err)
                                                                    console.log(err);
                                                                else {
                                                                    if (document_users != null && document_users != undefined) {
                                                                        for (var i = 0; i < document_users.length; i++) {
                                                                            if (email_send != null) {
                                                                                email_send = email_send + "," + document_users[i].email;
                                                                            } else {
                                                                                email_send = document_users[i].email;
                                                                            }
                                                                        }
                                                                    }

                                                                    if (document.value.email_alarma != undefined && document.value.email_alarma != null) {
                                                                        if (email_send != null) {
                                                                            email_send = email_send + "," + document.value.email_alarma;
                                                                        } else {
                                                                            email_send = document.value.email_alarma;
                                                                        }
                                                                    }

                                                                    if (email_send != null) {
                                                                        email_send = email_send + ",management.infinity.fleets@gmail.com";
                                                                    } else {
                                                                        email_send = document_users[i].email;
                                                                    }

                                                                    //console.log("emails : "+email_send);
                                                                    let dategps = document.value.fecha_gps;

                                                                    if (dategps != null && dategps != undefined) {
                                                                        let datetime = moment(dategps, DEVICE_DATE_FORMAT);
                                                                        let time = moment.duration("05:00:00");
                                                                        dategps = datetime.subtract(time).format(DATE_FORMAT);
                                                                    }

                                                                    message_notification = "  Notificación Infinity Solutions\n";
                                                                    message_notification = message_notification + "Fecha GPS: " + dategps;
                                                                    message_notification = message_notification + "\nhttps://www.google.com.ec/maps/dir/" + document.value.latitud + "," + document.value.longitud + "//@" + document.value.latitud + "," + document.value.longitud + ",16z?hl=en";
                                                                    message_notification = message_notification + " \nExceso de velocidad de: " + document.value.velocidad_actual + " km/h \n\n\nInfinity Solutions";

                                                                    let transporter = nodemailer.createTransport({
                                                                        service: 'gmail',
                                                                        auth: {
                                                                            user: 'notificaciones.infinity@gmail.com',
                                                                            pass: 'qwertyuiop1'
                                                                        }

                                                                    });

                                                                    let options = {
                                                                        from: 'TRACKINGSYSTEM <notificaciones.infinity@gmail.com>',
                                                                        to: email_send,
                                                                        subject: "Notificaciones Infinity Exceso de Velocidad Disco " + document.value.descripcion + " Placa " + document.value.placa + " (" + cooperativa_descripcion + ")",
                                                                        text: message_notification
                                                                    };

                                                                    // transporter.sendMail(options, function (error, info) {
                                                                    //     if (error)
                                                                    //         console.log(error);
                                                                    //     /*else 
                                                                    //        console.log('Mensaje enviado correctamente.');*/
                                                                    // });
                                                                }
                                                            });


                                                        }

                                                    }
                                                );
                                            }
                                        }
                                    }

                                    if (document.value.voltaje != null && document.value.voltaje != undefined && document.value.voltaje != '') {
                                        if (document.value.sistema_energizado) {
                                            if (document.value.voltaje <= 11000) {
                                                dbTrackingSystem.collection('cooperativas').findOne({ _id: new ObjectID(document.value.cooperativa_id) },
                                                    function (err, document_coop) {
                                                        if (err)
                                                            console.log(err);
                                                        else {
                                                            if (document_coop != null && document_coop != undefined) {
                                                                email_send = document_coop.email;
                                                                cooperativa_descripcion = document_coop.descripcion;
                                                            }

                                                            dbTrackingSystem.collection('users').find({
                                                                estado: 'A',
                                                                unidades_pertenecientes: { $in: [document.value._id] }
                                                            }).toArray(function (err, document_users) {
                                                                if (err)
                                                                    console.log(err);
                                                                else {
                                                                    if (document_users != null && document_users != undefined) {
                                                                        for (var i = 0; i < document_users.length; i++) {
                                                                            if (email_send != null) {
                                                                                email_send = email_send + "," + document_users[i].email;
                                                                            } else {
                                                                                email_send = document_users[i].email;
                                                                            }
                                                                        }
                                                                    }

                                                                    if (document.value.email_alarma != undefined && document.value.email_alarma != null) {
                                                                        if (email_send != null) {
                                                                            email_send = email_send + "," + document.value.email_alarma;
                                                                        } else {
                                                                            email_send = document.value.email_alarma;
                                                                        }
                                                                    }

                                                                    if (email_send != null) {
                                                                        email_send = email_send + ",management.infinity.fleets@gmail.com";
                                                                    } else {
                                                                        email_send = document_users[i].email;
                                                                    }

                                                                    email_send = "management.infinity.fleets@gmail.com";

                                                                    //console.log("emails : "+email_send);
                                                                    let dategps = document.value.fecha_gps;
                                                                    if (dategps != null && dategps != undefined) {
                                                                        let datetime = moment(dategps, DEVICE_DATE_FORMAT);
                                                                        let time = moment.duration("05:00:00");
                                                                        dategps = datetime.subtract(time).format(DATE_FORMAT);
                                                                    }

                                                                    message_notification = "  Notificación Infinity Solutions\n";
                                                                    message_notification = message_notification + "Fecha GPS: " + dategps;
                                                                    message_notification = message_notification + "\nhttps://www.google.com.ec/maps/dir/" + document.value.latitud + "," + document.value.longitud + "//@" + document.value.latitud + "," + document.value.longitud + ",16z?hl=en";
                                                                    message_notification = message_notification + " \nUnidad con voltaje de dispositivo de:" + document.value.voltaje + " \n\n\nInfinity Solutions";

                                                                    let transporter = nodemailer.createTransport({
                                                                        service: 'gmail',
                                                                        auth: {
                                                                            user: 'notificaciones.infinity@gmail.com',
                                                                            pass: 'qwertyuiop1'
                                                                        }

                                                                    });

                                                                    let options = {
                                                                        from: 'TRACKINGSYSTEM <notificaciones.infinity@gmail.com>',
                                                                        to: email_send,
                                                                        subject: "Notificaciones Infinity Voltaje 0 Disco " + document.value.descripcion + " Placa " + document.value.placa + " (" + cooperativa_descripcion + ")",
                                                                        text: message_notification
                                                                    };

                                                                    // transporter.sendMail(options, function (error, info) {
                                                                    //     if (error)
                                                                    //         console.log(error);
                                                                    //     /*else 
                                                                    //        console.log('Mensaje enviado correctamente.');*/
                                                                    // });
                                                                }
                                                            });


                                                        }

                                                    }
                                                );
                                            }
                                        }
                                    }


                                    dbTrackingSystem.collection('recorridos').insertOne({
                                        imei: data[imei],
                                        tipo: GTFRI,
                                        voltaje: document.value.voltaje,
                                        fecha_gps: (document.value.fecha_gps != null) ? new Date(document.value.fecha_gps) : new Date(),
                                        latitud: document.value.latitud,
                                        longitud: document.value.longitud,
                                        velocidad: document.value.velocidad_actual,
                                        mileage: document.value.mileage,
                                        bateria: (document.value.bateria),
                                        altura: toFloat(data[height]),
                                        angulo: document.value.angulo,
                                        fecha_envio: (toInteger(data[sentTime]) != 0) ? (moment(data[sentTime], DEVICE_DATE_FORMAT).toDate()) : new Date(),
                                        unidad_id: document.value._id,
                                        fecha: (document.value.fecha != null) ? new Date(document.value.fecha) : new Date(),
                                        estado_movil: document.value.estado_movil,
                                        evento: document.value.evento,
                                        contador_total: document.value.contador_total,
                                        contador_diario: document.value.contador_diario,
                                        js: true
                                    }, function (err, result) {
                                        if (err)
                                            console.log(err);
                                    });
                                }
                            }
                        }
                    }
                );
            }
            else if (message.includes(GTDAT) && !message.includes(ACK))//If the message contains counter data
            {
                let imei = 2;//Index for imei
                let flag = 4;//Index for flag
                let deviceName = 5;//Index for device name
                let count = 6;//Index for count of passengers
                let p2 = 7;//Index for count of passengers
                let p3 = 8;//Index for count of passengers
                let status = 7;//Index for device status
                let sentTime = 8;//Index for sent time
                let fechaSend = 10;
                let data = message.split(',');//Array wich contains the data from device
                const MAX_COUNT = 65535;
                const MAX_COUNT_C2 = 999999;
                let puerta1, puerta2, puerta3;
                if (data[flag] === '>PC' || data[flag] === '>PC3') {
                    /*
                    * Finding the unidad node
                    */
                    if (data[deviceName] === 'P1' || data[deviceName] === 'P2' || data[deviceName] === 'P3') {
                        dbTrackingSystem.collection('unidads').findOne({ imei: data[imei], estado: 'A' }, function (err, document) {
                            if (err)
                                console.log(err);
                            else if (document != undefined && document != null)//Verifying if unidad exists
                            {
                                var contador_diario = 0;//Initializing the daily counter to zero
                                var contador_inicial = null;
                                var contador_diario_anterior = 0;
                                contador_diario_anterior = document.contador_diario;
                                if (data[deviceName] === 'P1') {
                                    contador_diario_anterior = document.contador_diario;
                                    contador_diario = 0;//Initializing the daily counter to zero
                                    contador_inicial = document.contador_inicial;
                                }
                                if (data[deviceName] === 'P2') {
                                    contador_diario_anterior = document.contador_diario_sensor_2;
                                    contador_diario = 0;//Initializing the daily counter to zero
                                    contador_inicial = document.contador_inicial_sensor_2;
                                }
                                if (data[deviceName] === 'P3') {
                                    contador_diario_anterior = document.contador_diario_sensor_3;
                                    contador_diario = 0;//Initializing the daily counter to zero
                                    contador_inicial = document.contador_inicial_sensor_3;
                                }
                                if (contador_inicial != undefined && contador_inicial != null)//If contador_inicial property exists in record
                                {
                                    if (contador_inicial > 0)//Verify if contador_inicial is major than zero
                                    {
                                        contador_diario = (toInteger(data[count])) - contador_inicial;//Difference between device count and initial counter
                                        if (contador_diario < 0)//If the difference is negative
                                        {
                                            if (deviceName === 'C1')
                                                contador_diario = (toInteger(data[count])) + MAX_COUNT;//The daily count is converted to positive
                                            else
                                                contador_diario = (toInteger(data[count])) + MAX_COUNT_C2;//The daily count is converted to positive
                                        }
                                    }
                                    else
                                        contador_inicial = toInteger(data[count]);//If initial counter is zero, it becomes the first counter sent by device
                                }
                                else
                                    contador_inicial = toInteger(data[count]);//The initial counter is null or undefined, it becomes the first counter sent by device
                                /*
                                    The unidad is updated by the new counters
                                */
                                if (contador_diario >= contador_diario_anterior) {
                                    if (data[deviceName] === 'P1') {
                                        dbTrackingSystem.collection('unidads').updateOne({
                                            _id: document._id
                                        }, {
                                            $set: {
                                                contador_total: toInteger(data[count]),
                                                contador_diario: contador_diario,
                                                contador_inicial: contador_inicial,
                                                is_atm: (message.includes(ATM) ? 1 : 0),
                                                evento: data[status]
                                            }
                                        }, function (err, result) {
                                            if (err)
                                                console.log(err);
                                        });
                                    }
                                    if (data[deviceName] === 'P2') {
                                        dbTrackingSystem.collection('unidads').updateOne({
                                            _id: document._id
                                        }, {
                                            $set: {
                                                contador_total_sensor_2: toInteger(data[count]),
                                                contador_diario_sensor_2: contador_diario,
                                                contador_inicial_sensor_2: contador_inicial,
                                                is_atm: (message.includes(ATM) ? 1 : 0),
                                                evento: data[status]
                                            }
                                        }, function (err, result) {
                                            if (err)
                                                console.log(err);
                                        });
                                    }
                                    if (data[deviceName] === 'P3') {
                                        dbTrackingSystem.collection('unidads').updateOne({
                                            _id: document._id
                                        }, {
                                            $set: {
                                                contador_total_sensor_3: toInteger(data[count]),
                                                contador_diario_sensor_3: contador_diario,
                                                contador_inicial_sensor_3: contador_inicial,
                                                is_atm: (message.includes(ATM) ? 1 : 0),
                                                evento: data[status]
                                            }
                                        }, function (err, result) {
                                            if (err)
                                                console.log(err);
                                        });
                                    }
                                }

                            }
                        });
                    } else {
                        if (data[deviceName] === 'PAC') {
                            dbTrackingSystem.collection('unidads').findOne({ imei: data[imei], estado: 'A' }, function (err, document) {
                                if (err)
                                    console.log(err);
                                else if (document != undefined && document != null)//Verifying if unidad exists
                                {
                                    var fecha_gps = (toInteger(data[fechaSend]) != 0) ? moment(data[fechaSend], DEVICE_DATE_FORMAT).toDate() : new Date();//GPS Date
                                    let puerta_1 = parseInt(data[count]);
                                    let puerta_2 = parseInt(data[p2]);
                                    let puerta_3 = parseInt(data[p3]);
                                    var fecha_servidor = new Date();//Server date

                                    puerta1 = (puerta_1 == 0) ? 'PUERTA CERRADA (DELANTERA)' : 'PUERTA ABIERTA (DELANTERA)';
                                    puerta2 = (puerta_2 == 0) ? 'PUERTA CERRADA (MEDIO)' : 'PUERTA ABIERTA (MEDIO)';
                                    puerta3 = (puerta_3 == 0) ? 'PUERTA CERRADA (TRASERA)' : 'PUERTA ABIERTA (TRASERA)';

                                    dbTrackingSystem.collection('recorridos').insertOne({
                                        imei: data[imei],
                                        tipo: GTDIS,
                                        unidad_id: document._id,
                                        velocidad: document.velocidad,
                                        angulo: document.angulo,
                                        longitud: document.longitud,
                                        latitud: document.latitud,
                                        fecha_gps: fecha_gps,
                                        fecha: fecha_servidor,
                                        evento: puerta1,
                                        fecha_envio: fecha_gps,
                                        js: true
                                    }, function (err, result) {
                                        if (err)
                                            console.log(err);
                                    });
                                    dbTrackingSystem.collection('recorridos').insertOne({
                                        imei: data[imei],
                                        tipo: GTDIS,
                                        unidad_id: document._id,
                                        velocidad: document.velocidad,
                                        angulo: document.angulo,
                                        longitud: document.longitud,
                                        latitud: document.latitud,
                                        fecha_gps: fecha_gps,
                                        fecha: fecha_servidor,
                                        evento: puerta2,
                                        fecha_envio: fecha_gps,
                                        js: true
                                    }, function (err, result) {
                                        if (err)
                                            console.log(err);
                                    });
                                    dbTrackingSystem.collection('recorridos').insertOne({
                                        imei: data[imei],
                                        tipo: GTDIS,
                                        unidad_id: document._id,
                                        velocidad: document.velocidad,
                                        angulo: document.angulo,
                                        longitud: document.longitud,
                                        latitud: document.latitud,
                                        fecha_gps: fecha_gps,
                                        fecha: fecha_servidor,
                                        evento: puerta3,
                                        fecha_envio: fecha_gps,
                                        js: true
                                    }, function (err, result) {
                                        if (err)
                                            console.log(err);
                                    });
                                }
                            });

                        } else {
                            dbTrackingSystem.collection('unidads').findOne({ imei: data[imei], estado: 'A' }, function (err, document) {
                                if (err)
                                    console.log(err);
                                else if (document != undefined && document != null)//Verifying if unidad exists
                                {
                                    var contador_diario = 0;//Initializing the daily counter to zero
                                    var contador_inicial = document.contador_inicial;
                                    if (document.contador_inicial != undefined && document.contador_inicial != null)//If contador_inicial property exists in record
                                    {
                                        if (document.contador_inicial > 0)//Verify if contador_inicial is major than zero
                                        {
                                            contador_diario = (toInteger(data[count])) - document.contador_inicial;//Difference between device count and initial counter
                                            if (contador_diario < 0)//If the difference is negative
                                            {
                                                if (deviceName === 'C1')
                                                    contador_diario = (toInteger(data[count])) + MAX_COUNT;//The daily count is converted to positive
                                                else
                                                    contador_diario = (toInteger(data[count])) + MAX_COUNT_C2;//The daily count is converted to positive
                                            }
                                        }
                                        else
                                            contador_inicial = toInteger(data[count]);//If initial counter is zero, it becomes the first counter sent by device
                                    }
                                    else
                                        contador_inicial = toInteger(data[count]);//The initial counter is null or undefined, it becomes the first counter sent by device
                                    /*
                                        The unidad is updated by the new counters
                                    */
                                    if (contador_diario >= document.contador_diario) {
                                        dbTrackingSystem.collection('unidads').updateOne({
                                            _id: document._id
                                        }, {
                                            $set: {
                                                contador_total: toInteger(data[count]),
                                                contador_diario: contador_diario,
                                                contador_inicial: contador_inicial,
                                                is_atm: (message.includes(ATM) ? 1 : 0),
                                                evento: data[status]
                                            }
                                        }, function (err, result) {
                                            if (err)
                                                console.log(err);
                                        });
                                    }
                                }
                            });
                        }
                    }
                }
            }
            else if (!message.includes(ADMIN) && message.includes(GTGEO) && !message.includes(ACK))//If the message contains control points data
            {
                let imei = 2;//Index for imei
                let infoControlPoint = 5;//Index for control point number
                let speed = 8;//Index for speed
                let angle = 9;//Index of angle
                let height = 10;//Index of height
                let longitude = 11;//Index of longitude
                let latitude = 12;//Index of latitude
                let datetime = 13;//Index of datetime
                let sentTime = 20;//Index of sent time
                let data = message.split(',');//Array wich contains the data from device
                /*
                * Finding de unidad by imei
                */
                dbTrackingSystem.collection('unidads').findOne({
                    imei: data[imei], estado: 'A'
                }, function (err, document) {
                    if (err)
                        console.log(err);
                    else if (document != undefined && document != null)//If the document is defined
                    {
                        let pdi, inout;//Declaring pdi and in or out
                        if (data[infoControlPoint].length === 3)//Verifying is the length of info is 3, it means that pdi is bigger than 9
                        {
                            pdi = parseInt(data[infoControlPoint].substring(0, 2), 16);
                            inout = parseInt(data[infoControlPoint].substring(2, 3));
                        }
                        else if (data[infoControlPoint].length === 2)//If pdi is minor or equal than 9
                        {
                            pdi = parseInt(data[infoControlPoint].charAt(0), 16);
                            inout = parseInt(data[infoControlPoint].charAt(1));
                        }
                        var estado_movil = document.estado_movil;//Default status
                        var latitud = toFloat(data[latitude]);//Default latitude
                        var longitud = toFloat(data[longitude]);//Default longitude
                        var fecha_servidor = new Date();//Server date
                        var fecha_gps = (toInteger(data[datetime]) != 0) ? moment(data[datetime], DEVICE_DATE_FORMAT).toDate() : new Date();//GPS Date
                        if (latitud === 0 || longitud === 0)//If gps is not valid
                        {
                            estado_movil = 'E';//GPS Error
                            latitud = document.latitud;//Assigning document latitude
                            longitud = document.longitud;//Assigning document longitude
                        }
                        /*
                            Updating the unidad
                        */
                        dbTrackingSystem.collection('unidads').updateOne({
                            _id: document._id
                        }, {
                            $set: {
                                latitud: latitud,
                                longitud: longitud,
                                estado_movil: estado_movil,
                                velocidad_actual: toFloat(data[speed]),
                                angulo: toInteger(data[angle]),
                                fecha_gps: fecha_gps,
                                is_atm: (message.includes(ATM) ? 1 : 0),
                                fecha: fecha_servidor
                            }
                        }, function (err, result) {
                            if (err)
                                console.log(err);
                        });
                        /*
                            Creating new record for recorridos collection with GTGEO type
                        */
                        dbTrackingSystem.collection('recorridos').insertOne({
                            imei: data[imei],
                            tipo: GTGEO,
                            unidad_id: document._id,
                            pdi: pdi,
                            entrada: inout,
                            latitud: latitud,
                            longitud: longitud,
                            velocidad: toFloat(data[speed]),
                            angulo: toInteger(data[angle]),
                            altura: toFloat(data[height]),
                            fecha_gps: fecha_gps,
                            fecha: fecha_servidor,
                            fecha_envio: (toInteger(data[sentTime]) != 0) ? moment(data[sentTime], DEVICE_DATE_FORMAT).toDate() : new Date(),
                            contador_diario: document.contador_diario,
                            contador_total: document.contador_total,
                            js: true
                        }, function (err, result) {
                            if (err)
                                console.log(err);
                        });
                    }
                });
            }
            else if (message.includes(GPRMC) && !message.includes(ACK))//If the string contains GPRMC data
            {
                let arrayFromEmpty = message.split(' ');//Getting array with big space separator
                let arrayGPRMC = message.split('$');//Getting main array
                let deviceEvent = parseInt(arrayFromEmpty[8]);//Getting the device event as an integer
                let deviceIMEI = arrayFromEmpty[15];//Getting device IMEI from simple secondary array
                let mainData = arrayGPRMC[1];//Getting the main data
                let mainArray = mainData.split(',');//Getting the main array with data
                let deviceTime = 1;//Index for the device time
                let navigationReceiver = 2;//Index for the gps status
                let latitude = 3;//Index for latitude
                let cLatitude = 4;//Index for latitude orientation
                let longitude = 5;//Index for longitude
                let cLongitude = 6;//Index for longitude orientation
                let speed = 7;//Index for speed
                let direction = 8;//Index for direction
                let deviceDate = 9;//Index for the device data
                let magneticVariation = 10;//Index for the magnetic variation
                let serverDate = new Date();//Current server date
                let deviceDatetime = mainArray[deviceDate] + mainArray[deviceTime];
                let socketObject = getSocket(deviceIMEI);
                if (socketObject == null) {
                    socketArray.push({
                        imei: deviceIMEI,
                        socket: socket
                    });
                }
                else {
                    socketObject.socket = socket;
                }
                dbTrackingSystem.collection('unidads').findOne({
                    imei: deviceIMEI, estado: 'A'
                }, function (err, document) {
                    if (err)
                        console.log(err);
                    else {
                        let entrada = 0;
                        if (deviceEvent >= 38) {
                            let res = deviceEvent % 2;
                            if (res === 0)
                                entrada = 1;
                        }
                        dbTrackingSystem.collection('recorridos').insertOne({
                            tipo: GPRMC,
                            unidad_id: document._id,
                            imei: deviceIMEI,
                            evento: deviceEvent,
                            entrada: entrada,
                            fecha: serverDate,
                            fecha_gps: moment(deviceDatetime, GPRMC_DATE_FORMAT).toDate(),
                            latitud: getCoordinates(mainArray[latitude], mainArray[cLatitude], true),
                            longitud: getCoordinates(mainArray[longitude], mainArray[cLongitude], false),
                            velocidad: getSpeed(mainArray[speed]),
                            angulo: toFloat(mainArray[direction]),
                            estado: mainArray[navigationReceiver]
                        }, function (err, result) {
                            if (err)
                                console.log(err);
                            else {
                                dbTrackingSystem.collection('unidads').updateOne({
                                    _id: document._id
                                }, {
                                    $set: {
                                        latitud: getCoordinates(mainArray[latitude], mainArray[cLatitude], true),
                                        longitud: getCoordinates(mainArray[longitude], mainArray[cLongitude], false),
                                        velocidad_actual: getSpeed(mainArray[speed]),
                                        angulo: toFloat(mainArray[direction]),
                                        fecha_gps: moment(deviceDatetime, GPRMC_DATE_FORMAT).toDate(),
                                        fecha: serverDate,
                                        is_atm: (message.includes(ATM) ? 1 : 0),
                                        estado_movil: (getSpeed(mainArray[speed]) > 0) ? 'M' : 'D'
                                    }
                                }, function (err, result) {
                                    if (err)
                                        console.log(err);
                                });
                            }
                        });
                    }
                });
            }
            else if (message.includes(ADMIN) && !message.includes(ACK)) {
                if (message.includes(GTLOG)) {
                    logsAdmin.push(socket);
                }
                else {
                    let commandArray = message.split(';');
                    let imei = 1;
                    let command = 2;
                    let clientImei = commandArray[imei];
                    let socketObject = getSocket(clientImei);
                    let response = `${commandArray[command]}\n`;
                    if (socketObject != null && socketObject.socket.writable) {
                        socketObject.socket.write(response);
                        console.log(`Response sent to ${clientImei}: ${response}`);
                    }
                    console.log(`Command received from admin: ${clientImei} - ${commandArray[command]}`);
                }
            }
            else if (message.includes(GTDIS) && !message.includes(ACK)) {
                // console.log(message);
                let imei = 2;//Index for imei
                let speed = 8;//Index for speed
                let angle = 9;//Index of angle
                let longitude = 11;//Index of longitude
                let latitude = 12;//Index of latitude
                let datetime = 13;//Index of datetime
                let sentTime = 20;//Index of sent time
                let indexdoor = 5;//Index for open/close door
                let data = message.split(',');//Array wich contains the data from device
                let puerta = '';
                //20 ABIERTA 21 CERRADA  --- PUERTAS ADELANTE
                //30 ABIERTA 31 CERRADA   --- PUERTA ATRAS Y ENMEDIO
                if (toInteger(data[indexdoor]) == 20)
                    puerta = 'PUERTA ABIERTA (DELANTERA)';
                if (toInteger(data[indexdoor]) == 21)
                    puerta = 'PUERTA CERRADA (DELANTERA)';
                if (toInteger(data[indexdoor]) == 30)
                    puerta = 'PUERTA ABIERTA (TRASERA)';
                if (toInteger(data[indexdoor]) == 31)
                    puerta = 'PUERTA CERRADA (TRASERA)';

                dbTrackingSystem.collection('unidads').findOne({
                    imei: data[imei], estado: 'A'
                }, function (err, document) {
                    if (err)
                        console.log(err);
                    else if (document != undefined && document != null)//If the document is defined
                    {
                        var latitud = toFloat(data[latitude]);//Default latitude
                        var longitud = toFloat(data[longitude]);//Default longitude
                        var fecha_servidor = new Date();//Server date
                        var fecha_gps = (toInteger(data[datetime]) != 0) ? moment(data[datetime], DEVICE_DATE_FORMAT).toDate() : new Date();//GPS Date
                        if (latitud === 0 || longitud === 0)//If gps is not valid
                        {
                            latitud = document.latitud;//Assigning document latitude
                            longitud = document.longitud;//Assigning document longitude
                        }

                        dbTrackingSystem.collection('recorridos').insertOne({
                            imei: data[imei],
                            tipo: GTDIS,
                            unidad_id: document._id,
                            velocidad: toFloat(data[speed]),
                            angulo: toInteger(data[angle]),
                            longitud: longitud,
                            latitud: latitud,
                            fecha_gps: fecha_gps,
                            fecha: fecha_servidor,
                            evento: puerta,
                            fecha_envio: (toInteger(data[sentTime]) != 0) ? moment(data[sentTime], DEVICE_DATE_FORMAT).toDate() : new Date(),
                            js: true
                        }, function (err, result) {
                            if (err)
                                console.log(err);
                        });

                        if (toInteger(data[indexdoor]) == 20 || toInteger(data[indexdoor]) == 21) {
                            dbTrackingSystem.collection('unidads').updateOne({
                                _id: document._id
                            }, {
                                $set: {
                                    puerta: puerta,
                                    alerta_puerta_message: puerta,
                                    alerta_puerta_fecha: fecha_gps,
                                    fecha_puerta_abierta: ((puerta == 'PUERTA ABIERTA (DELANTERA)') ? fecha_gps : ((document.fecha_puerta_abierta != null && document.fecha_puerta_abierta != undefined) ? document.fecha_puerta_abierta : null)),
                                    fecha_puerta_cerrada: ((puerta == 'PUERTA CERRADA (DELANTERA)') ? fecha_gps : ((document.fecha_puerta_cerrada != null && document.fecha_puerta_cerrada != undefined) ? document.fecha_puerta_cerrada : null)),
                                    is_atm: 0
                                }
                            }, function (err, result) {
                                if (err)
                                    console.log(err);
                            });
                        } else {
                            dbTrackingSystem.collection('unidads').updateOne({
                                _id: document._id
                            }, {
                                $set: {
                                    puerta_trasera: puerta,
                                    alerta_puerta_message_trasera: puerta,
                                    alerta_puerta_fecha_trasera: fecha_gps,
                                    fecha_puerta_abierta_trasera: ((puerta == 'PUERTA ABIERTA (TRASERA)') ? fecha_gps : ((document.fecha_puerta_abierta_trasera != null && document.fecha_puerta_abierta_trasera != undefined) ? document.fecha_puerta_abierta_trasera : null)),
                                    fecha_puerta_cerrada_trasera: ((puerta == 'PUERTA CERRADA (TRASERA)') ? fecha_gps : ((document.fecha_puerta_cerrada_trasera != null && document.fecha_puerta_cerrada_trasera != undefined) ? document.fecha_puerta_cerrada_trasera : null)),
                                    is_atm: 0
                                }
                            }, function (err, result) {
                                if (err)
                                    console.log(err);
                            });
                        }
                    }
                });

            } else if (message.includes(GTMPF) && !message.includes(ACK)) {

                let imei = 2;//Index for imei
                let data = message.split(',');//Array wich contains the data from device
                // let voltage = 4;//Index for voltage
                let speed = 5;//Index for speed
                let angle = 6;//Index for angle
                let height = 7;//Index for height
                let longitude = 8;//Index for longitude
                let latitude = 9;//Index for latitude
                let datetime = 10;//Index for datetime
                let fechaGPS = toInteger(data[datetime]);

                // console.log('AQUI GTMPF ');
                // console.log('AQUI GTMPF+ '+data[imei]+"+");

                dbTrackingSystem.collection('unidads').findOne({
                    imei: data[imei], estado: 'A'
                }, function (err, document) {
                    // console.log('AQUI GTMPF _'+document);
                    if (err)
                        console.log(err);
                    else if (document != undefined && document != null)//If the document is defined
                    {
                        // console.log('AQUI GTMPF existe');
                        dbTrackingSystem.collection('recorridos').insertOne({
                            imei: data[imei],
                            tipo: GTIGF,
                            fecha_gps: (fechaGPS != 0) ? (moment(data[datetime], DEVICE_DATE_FORMAT).toDate()) : new Date(),
                            latitud: toFloat(data[latitude]),
                            longitud: toFloat(data[longitude]),
                            velocidad: toFloat(data[speed]),
                            altura: toFloat(data[height]),
                            angulo: toInteger(data[angle]),
                            unidad_id: document._id,
                            fecha: (document.fecha != null) ? new Date(document.fecha) : new Date(),
                            js: true
                        }, function (err, result) {
                            if (err)
                                console.log(err);
                        });

                        dbTrackingSystem.collection('unidads').updateOne({
                            _id: document._id
                        }, {
                            $set: {
                                alerta_desconx_message: 'Dispositivo GPS apagado ',
                                alerta_desconx_fecha: (fechaGPS != 0) ? (moment(data[datetime], DEVICE_DATE_FORMAT).toDate()) : new Date()
                            }
                        }, function (err, result) {
                            if (err)
                                console.log(err);
                        });
                    }
                });
            } else if (message.includes(GTMPN) && !message.includes(ACK)) {

                let imei = 2;//Index for imei
                let data = message.split(',');//Array wich contains the data from device
                // let voltage = 4;//Index for voltage
                let speed = 5;//Index for speed
                let angle = 6;//Index for angle
                let height = 7;//Index for height
                let longitude = 8;//Index for longitude
                let latitude = 9;//Index for latitude
                let datetime = 10;//Index for datetime
                let fechaGPS = toInteger(data[datetime]);

                dbTrackingSystem.collection('unidads').findOne({
                    imei: data[imei], estado: 'A'
                }, function (err, document) {
                    if (err)
                        console.log(err);
                    else if (document != undefined && document != null)//If the document is defined
                    {
                        dbTrackingSystem.collection('recorridos').insertOne({
                            imei: data[imei],
                            tipo: GTIGN,
                            fecha_gps: (fechaGPS != 0) ? (moment(data[datetime], DEVICE_DATE_FORMAT).toDate()) : new Date(),
                            latitud: toFloat(data[latitude]),
                            longitud: toFloat(data[longitude]),
                            velocidad: toFloat(data[speed]),
                            altura: toFloat(data[height]),
                            angulo: toInteger(data[angle]),
                            unidad_id: document._id,
                            fecha: (document.fecha != null) ? new Date(document.fecha) : new Date(),
                            js: true
                        }, function (err, result) {
                            if (err)
                                console.log(err);
                        });

                        dbTrackingSystem.collection('unidads').updateOne({
                            _id: document._id
                        }, {
                            $set: {
                                alerta_desconx_message: 'Dispositivo GPS encendido ',
                                alerta_desconx_fecha: (fechaGPS != 0) ? (moment(data[datetime], DEVICE_DATE_FORMAT).toDate()) : new Date()
                            }
                        }, function (err, result) {
                            if (err)
                                console.log(err);
                        });
                    }
                });

            } else if (message.includes(GTDTT) && !message.includes(ACK)) {
                let imei = 2;//Index for imei
                let count = 8;//Index for count of passengers
                let sentTime = 9;//Index for sent time
                let data = message.split(',');//Array wich contains the data from device
                const MAX_COUNT = 9999999;
                dbTrackingSystem.collection('unidads').findOne({ imei: data[imei], estado: 'A' }, function (err, document) {
                    if (err)
                        console.log(err);
                    else if (document != undefined && document != null)//Verifying if unidad exists
                    {
                        var contador_diario = 0;//Initializing the daily counter to zero
                        var contador_diario_sensor_2 = 0;//Initializing the daily counter to zero
                        var contador_diario_sensor_3 = 0;//Initializing the daily counter to zero
                        var contador_inicial = document.contador_inicial;
                        var contador_inicial_sensor_2 = document.contador_inicial_sensor_2;
                        var contador_inicial_sensor_3 = document.contador_inicial_sensor_3;

                        var count_sensor_1 = 0;
                        var count_sensor_2 = 0;
                        var count_sensor_3 = 0;

                        if (message.includes(GTDTTDGT)) {
                            // Example of message: +RESP:GTDTT,250E05,868789024386996,,,,,0,22,DGT,10194281000074860,20250806184640,2631$
                            console.log('GTDTTDGT message: ' + message);
                            count = 9;
                            sentTime = 10;
                            var count_parse = data[count];
                            // counter1 is 10194281 and counter2 is 74860
                            const m = /^(\d{8})(\d{4})(\d{5})$/.exec(count_parse);
                            if (m) {
                                count_sensor_1 = toInteger(m[1]); // 10194281
                                count_sensor_2 = toInteger(m[3]); // 74860
                                console.log('count_sensor_1 GTDTTDGT: ' + count_sensor_1);
                                console.log('count_sensor_2 GTDTTDGT: ' + count_sensor_2);
                            }
                            else {
                                console.log('Error parsing GTDTTDGT count: ' + count_parse);
                            }
                            
                        }
                        else {
                            var count_parse = data[count].replace("RSC", "");
                            count_parse = count_parse.replace("\n", "");
                            count_parse = count_parse.replace(" ", "");
                            count_sensor_1 = toInteger(count_parse.substr(0, 7));
                            count_sensor_2 = toInteger(count_parse.substr(7, 7));
                            count_sensor_3 = toInteger(count_parse.substr(14, 7));
                        }

                        if (count_sensor_1 != 9999999) {

                            if (document.contador_inicial != undefined && document.contador_inicial != null)//If contador_inicial property exists in record
                            {
                                if (document.contador_inicial > 0)//Verify if contador_inicial is major than zero
                                {
                                    if (data[imei] == "862894020844018") {
                                        console.log('contador_inicial: ' + document.contador_inicial);
                                    }
                                    contador_diario = count_sensor_1 - document.contador_inicial;//Difference between device count and initial counter
                                    if (data[imei] == "862894020844018") {
                                        console.log('contador_diario: ' + contador_diario);
                                    }
                                    if (contador_diario < 0)//If the difference is negative
                                    {
                                        contador_diario = count_sensor_1 + MAX_COUNT;//The daily count is converted to positive
                                        if (data[imei] == "862894020844018") {
                                            console.log('contador_diario MAX_COUNT: ' + contador_diario);
                                        }
                                    }
                                }
                                else
                                    contador_inicial = count_sensor_1;//If initial counter is zero, it becomes the first counter sent by device
                            }
                            else
                                contador_inicial = count_sensor_1;//The initial counter is null or undefined, it becomes the first counter sent by device

                            if (document.contador_inicial_sensor_2 != undefined && document.contador_inicial_sensor_2 != null) {
                                if (document.contador_inicial_sensor_2 > 0) {
                                    contador_diario_sensor_2 = count_sensor_2 - document.contador_inicial_sensor_2;
                                    if (contador_diario_sensor_2 < 0) {
                                        contador_diario_sensor_2 = count_sensor_2 + MAX_COUNT;
                                    }
                                }
                                else
                                    contador_inicial_sensor_2 = count_sensor_2;
                            }
                            else
                                contador_inicial_sensor_2 = count_sensor_2;

                            if (document.contador_inicial_sensor_3 != undefined && document.contador_inicial_sensor_3 != null) {
                                if (document.contador_inicial_sensor_3 > 0) {
                                    contador_diario_sensor_3 = count_sensor_3 - document.contador_inicial_sensor_3;
                                    if (contador_diario_sensor_3 < 0) {
                                        contador_diario_sensor_3 = count_sensor_3 + MAX_COUNT;
                                    }
                                }
                                else
                                    contador_inicial_sensor_3 = count_sensor_3;
                            }
                            else
                                contador_inicial_sensor_3 = count_sensor_3;


                            if (contador_diario >= document.contador_diario)
                                contador_diario = contador_diario;
                            else
                                contador_diario = document.contador_diario;

                            if (contador_diario_sensor_2 >= document.contador_diario_sensor_2)
                                contador_diario_sensor_2 = contador_diario_sensor_2;
                            else
                                contador_diario_sensor_2 = document.contador_diario_sensor_2;

                            if (contador_diario_sensor_3 >= document.contador_diario_sensor_3)
                                contador_diario_sensor_3 = contador_diario_sensor_3;
                            else
                                contador_diario_sensor_3 = document.contador_diario_sensor_3;

                            dbTrackingSystem.collection('unidads').updateOne({
                                _id: document._id
                            }, {
                                $set: {
                                    contador_total: count_sensor_1,
                                    contador_diario: contador_diario,
                                    contador_inicial: contador_inicial,
                                    contador_total_sensor_2: count_sensor_2,
                                    contador_diario_sensor_2: contador_diario_sensor_2,
                                    contador_inicial_sensor_2: contador_inicial_sensor_2,
                                    contador_total_sensor_3: count_sensor_3,
                                    contador_diario_sensor_3: contador_diario_sensor_3,
                                    contador_inicial_sensor_3: contador_inicial_sensor_3,
                                    is_atm: (message.includes(ATM) ? 1 : 0)
                                }
                            }, function (err, result) {
                                if (err)
                                    console.log(err);
                            });

                            dbTrackingSystem.collection('recorridos').insertOne({
                                imei: data[imei],
                                tipo: GTDAT,
                                unidad_id: document._id,
                                velocidad: document.velocidad_actual,
                                angulo: document.angulo,
                                longitud: document.longitud,
                                latitud: document.latitud,
                                fecha_gps: (toInteger(data[sentTime]) != 0) ? moment(data[sentTime], DEVICE_DATE_FORMAT).toDate() : new Date(),
                                fecha: new Date(),
                                contador_total: count_sensor_1,
                                contador_total_sensor_2: count_sensor_2,
                                contador_total_sensor_3: count_sensor_3,
                                trama: message
                            }, function (err, result) {
                                if (err)
                                    console.log(err);
                            });
                        }
                    }
                });
            }

            // if (message.includes('867162027537489')) {
            //     dbTrackingSystem.collection('tramas').insertOne({
            //         contenido: message,
            //         visto: false,
            //         created_at: new Date(),
            //         updated_at: new Date()
            //     }, function (err, result) {
            //         if (err)
            //             console.log(err);
            //     });
            // }

            dbTrackingSystem.collection('tramas').insertOne({
                contenido: message,
                visto: false,
                created_at: new Date(),
                updated_at: new Date()
            }, function (err, result) {
                if (err)
                    console.log(err);
            });
        }
        catch (err) {
            dbTrackingSystem.collection('LogError').insertOne({
                Trama: message,
                Error: err.message
            }, function (err, result) {
                if (err)
                    console.log(err);
            });
            console.log(err);
        } finally {
            /* if(socket !=null)
                 socket.disconnect(true);*/
        }
    });
    socket.on('error', (error) => {
        console.log(error);
    });
}

