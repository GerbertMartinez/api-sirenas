<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Historic;
use App\Models\Record;
use App\Models\Siren;
use App\Models\Web;
use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class MainController
{

    /*
    * INSERTS A LA BASE DE DATOS DESDE RASPBERRY
    */
    public function register_activity(Request $request)
    {

        $activity = new Activity();
        $activity->id_siren = $request->sirena;
        $activity->action = $request->accion;
        $activity->save();

        $siren = Siren::find($request->sirena);

        if ($request->accion == "OFFLINE"){

            $status = 0;
            $relay = 0;

            if ($siren->signal != 0) {
                $UserController = new UserController();
                $UserController->sendNotifications("🔴 ".$siren->name, "La sirena " . $siren->name . " se encuentra fuera de linea");
            }

        }

        if ($request->accion == "ONLINE"){

            $status = 1;
            $relay = 0;

            if ($siren->signal != 1) {
                $UserController = new UserController();
                $UserController->sendNotifications("🟢 ".$siren->name, "La sirena " . $siren->name . " se encuentra nuevamente en linea");
            }

        }

        if ($request->accion == "ON"){ 

            $status = 1;
            $relay = 1; 

        }
        if ($request->accion == "OFF") { 

            $status = 1;
            $relay = 0; 

        }

        $siren->signal = $status;
        $siren->relay = $relay;
        $siren->save();

    }

    public function register_info(Request $request)
    {

        $register = new Record();
        $register->id_siren = $request->sirena;
        $register->cputemp = $request->cputemp;
        $register->pin18 = $request->pin;
        $register->save();

        if ($request->cputemp > 70){

            $siren = Siren::find($request->sirena);

            $UserController = new UserController();
            $UserController->sendNotifications("🌡️ ".$siren->name, "La sirena " . $siren->name . " está a ".$request->cputemp . " °C revisar ventilación");

        }

        $siren = Siren::find($request->sirena);
        $siren->signal = 0;
        $siren->save();

        $siren = Siren::find($request->sirena);
        $siren->temp = $request->cputemp;
        $siren->signal = 1;
        $siren->save();

    }

    /*
    * CONSULTAS A LA BASE DE DATOS
    */
    public function get_main_data() 
    {

        $sirens = Siren::get();

        $hot = $sirens->where('temp', '>', 70)->count();
        $online = $sirens->where('signal', 1)->count();
        $sounding = $sirens->where('relay', 1)->count();
        $total = $sirens->count();

        return response()->json([
            'hot' => $hot,
            'online' => $online,
            'total' => $total,
            'sounding' => $sounding
        ]);

    }

    public function get_data(Request $request) 
    {

        $data = User::with('webs.sirens')->find($request->id_user);

        $response = $data->webs->map(function($web) {
            
            return [
                'id' => $web->id_web,
                'name' => $web->name,
                'hot' => $web->sirens->where('temp', '>', 70)->count(),
                'online' => $web->sirens->where('signal', 1)->count(),
                'sounding' => $web->sirens->where('relay', 1)->count(),
                'total' => $web->sirens->count()
            ];

        });

        return response()->json($response);

    }

    public function get_sirens()
    {

        $sirens = Siren::get();
        return response()->json($sirens);

    }

    public function get_sirens_user(Request $request)
    {

        $user = User::with('webs.sirens')->where('id_user', $request->id_user)->first();
        $sirens = $user->webs->pluck('sirens')->flatten()->unique('id_siren')->values();
        return response()->json($sirens);

    }

    public function get_activities()
    {

        $actions = Activity::get();
        return response()->json($actions);

    }

    public function get_historic(Request $request)
    {

        $type = User::find($request->id_user);

        if ($type->level == 1){

            $history = Historic::with(['user', 'siren', 'web'])->orderBy('created_at', 'desc')->get();
                    
        } else {

            $user = User::with('webs.sirens')->where('id_user', $request->id_user)->first();
            $webIds = $user->webs->pluck('id_web');
            $sirenIds = $user->webs->pluck('sirens')->flatten()->pluck('id_siren')->unique();
            $history = Historic::with(['user', 'siren', 'web'])->where(function ($q) use ($webIds, $sirenIds) {
                $q->whereIn('id_web', $webIds)
                ->orWhereIn('id_siren', $sirenIds);
            })->orderBy('created_at', 'desc')->get();

        }
        
        return response()->json($history);

    }

    public function get_webs()
    {

        $webs = Web::with('sirens')->get();
        return response()->json($webs);

    }

    public function get_web(Request $request)
    {

        $web = Web::with('sirens')->find($request->id_web);
        return response()->json($web);

    }

    public function get_siren(Request $request)
    {

        $siren = Siren::find($request->id_siren);

        $siren->records = $siren->records()
            ->orderBy('created_at', 'desc')
            ->limit(350)
            ->get();

        $siren->activities = $siren->activities()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $siren->last_on = $siren->activities()
            ->where('action', 'ON')
            ->latest('created_at')
            ->first();

        $siren->last_off = $siren->activities()
            ->where('action', 'OFF')
            ->latest('created_at')
            ->first();

        $siren->last_online = $siren->activities()
            ->where('action', 'ONLINE')
            ->latest('created_at')
            ->first();

        return response()->json($siren);

    }

    /*
    * ACCIONES HACIA LAS RASPBERRY
    */
    public function on_sirens()
    {

        $server = "appsavemuniguate.com";
        $port = 8883;
        $cafile = "/etc/mosquitto/certs/chain.crt";
        $clientId = 'laravel-publisher-' . uniqid();
        $message = "ON";
        $contador = 0;

        $sirens = Siren::get();

        foreach ($sirens as $siren) {

            $topic = 'SIRENA/' . str($siren->id_siren);

            $connectionSettings = (new ConnectionSettings)
                ->setUseTls(true)
                ->setTlsCertificateAuthorityFile($cafile)
                ->setKeepAliveInterval(60)
                ->setLastWillTopic('SIRENA/lastwill')
                ->setLastWillMessage('Laravel die')
                ->setLastWillQualityOfService(1);

            $mqtt = new MqttClient($server, $port, $clientId, MqttClient::MQTT_3_1);

            try {
                $mqtt->connect($connectionSettings, true);
                $mqtt->publish($topic, $message, 1);
                $contador++;
                $mqtt->disconnect();
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

        }

        $MainController = new UserController();
        $MainController->pushToken(1, "ACTIVANDO TODO", "Se ejecutó el comando desde UMG para activar todas las sirenas municipales");
        $MainController->pushToken(6, "ACTIVANDO TODO", "Se ejecutó el comando desde UMG para activar todas las sirenas municipales");

        return response()->json([
            'message' => 'Comando ON enviado a sirenas',
            'mensajes_exitosos' => $contador,
            'sirenas' => $sirens
        ]);

    }

    public function off_sirens()
    {

        $server = "appsavemuniguate.com";
        $port = 8883;
        $cafile = "/etc/mosquitto/certs/chain.crt";
        $clientId = 'laravel-publisher-' . uniqid();
        $message = "OFF";
        $contador = 0;

        $sirens = Siren::get();
        
        foreach ($sirens as $siren) {

            $topic = 'SIRENA/' . str($siren->id_siren);

            $connectionSettings = (new ConnectionSettings)
                ->setUseTls(true)
                ->setTlsCertificateAuthorityFile($cafile)
                ->setKeepAliveInterval(60)
                ->setLastWillTopic('SIRENA/lastwill')
                ->setLastWillMessage('Laravel die')
                ->setLastWillQualityOfService(1);

            $mqtt = new MqttClient($server, $port, $clientId, MqttClient::MQTT_3_1);

            try {
                $mqtt->connect($connectionSettings, true);
                $mqtt->publish($topic, $message, 1);
                $contador++;
                $mqtt->disconnect();
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

        }

        $MainController = new UserController();
        $MainController->pushToken(1, "APAGANDO TODO", "Se ejecutó el comando desde UMG para apagar todas las sirenas municipales");
        $MainController->pushToken(6, "APAGANDO TODO", "Se ejecutó el comando desde UMG para apagar todas las sirenas municipales");

        return response()->json([
            'message' => 'Comando OFF enviado a sirenas',
            'mensajes_exitosos' => $contador,
            'sirenas' => $sirens
        ]);

    }

    public function test_sirens()
    {

        $server = "appsavemuniguate.com";
        $port = 8883;
        $cafile = "/etc/mosquitto/certs/chain.crt";
        $clientId = 'laravel-publisher-' . uniqid();
        $message = "TEST";
        $contador = 0;

        $sirens = Siren::get();

        foreach ($sirens as $siren) {

            $topic = 'TEST/' . str($siren->id_siren);

            $connectionSettings = (new ConnectionSettings)
                ->setUseTls(true)
                ->setTlsCertificateAuthorityFile($cafile)
                ->setKeepAliveInterval(60)
                ->setLastWillTopic('SIRENA/lastwill')
                ->setLastWillMessage('Laravel die')
                ->setLastWillQualityOfService(1);

            $mqtt = new MqttClient($server, $port, $clientId, MqttClient::MQTT_3_1);

            try {
                $mqtt->connect($connectionSettings, true);
                $mqtt->publish($topic, $message, 1);
                $contador++;
                $mqtt->disconnect();
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

        }

        return response()->json([
            'message' => 'Comando TEST enviado a sirenas',
            'mensajes_exitosos' => $contador,
            'sirenas' => $sirens
        ]);

    }

    public function on_all(Request $request)
    {

        $result = new \stdClass();

        $server = "appsavemuniguate.com";
        $port = 8883;
        $cafile = "/etc/mosquitto/certs/chain.crt";
        $clientId = 'laravel-publisher-' . uniqid();
        $message = "ON";
        $contador = 0;

        $sirens = Siren::get();
        //$sirens = Siren::where('id_siren', '13')->get();

        foreach ($sirens as $siren) {

            $topic = 'SIRENA/' . str($siren->id_siren);

            $connectionSettings = (new ConnectionSettings)
                ->setUseTls(true)
                ->setTlsCertificateAuthorityFile($cafile)
                ->setKeepAliveInterval(60)
                ->setLastWillTopic('SIRENA/lastwill')
                ->setLastWillMessage('Laravel die')
                ->setLastWillQualityOfService(1);

            $mqtt = new MqttClient($server, $port, $clientId, MqttClient::MQTT_3_1);

            try {
                $mqtt->connect($connectionSettings, true);
                $mqtt->publish($topic, $message, 1);
                $contador++;
                $mqtt->disconnect();
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

        }

        $history = new Historic();
        $history->action = $message;
        $history->success = $contador;
        $history->id_user = $request->id_user;
        $history->save();

        $UserController = new UserController();
        $UserController->sendAlerts("ACTIVANDO TODO", "Se ejecutó el comando para activar todas las sirenas municipales");

        $result->message = 'Comando ON enviado a sirenas';
        $result->mensajes_exitosos = $contador;

        return response()->json($result);

    }

    public function off_all(Request $request)
    {

        $result = new \stdClass();

        $server = "appsavemuniguate.com";
        $port = 8883;
        $cafile = "/etc/mosquitto/certs/chain.crt";
        $clientId = 'laravel-publisher-' . uniqid();
        $message = "OFF";
        $contador = 0;

        $sirens = Siren::get();
        //$sirens = Siren::where('id_siren', '13')->get();
        
        foreach ($sirens as $siren) {

            $topic = 'SIRENA/' . str($siren->id_siren);

            $connectionSettings = (new ConnectionSettings)
                ->setUseTls(true)
                ->setTlsCertificateAuthorityFile($cafile)
                ->setKeepAliveInterval(60)
                ->setLastWillTopic('SIRENA/lastwill')
                ->setLastWillMessage('Laravel die')
                ->setLastWillQualityOfService(1);

            $mqtt = new MqttClient($server, $port, $clientId, MqttClient::MQTT_3_1);

            try {
                $mqtt->connect($connectionSettings, true);
                $mqtt->publish($topic, $message, 1);
                $contador++;
                $mqtt->disconnect();
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

        }

        $history = new Historic();
        $history->action = $message;
        $history->success = $contador;
        $history->id_user = $request->id_user;
        $history->save();

        $UserController = new UserController();
        $UserController->sendAlerts("APAGANDO TODO", "Se ejecutó el comando para apagar todas las sirenas municipales", 2);

        $result->message = 'Comando OFF enviado a sirenas';
        $result->mensajes_exitosos = $contador;

        return response()->json($result);

    }

    public function test_all(Request $request)
    {

        $result = new \stdClass();

        $server = "appsavemuniguate.com";
        $port = 8883;
        $cafile = "/etc/mosquitto/certs/chain.crt";
        $clientId = 'laravel-publisher-' . uniqid();
        $message = "TEST";
        $contador = 0;

        $sirens = Siren::get();
        //$sirens = Siren::where('id_siren', '13')->get();

        foreach ($sirens as $siren) {

            $topic = 'TEST/' . str($siren->id_siren);

            $connectionSettings = (new ConnectionSettings)
                ->setUseTls(true)
                ->setTlsCertificateAuthorityFile($cafile)
                ->setKeepAliveInterval(60)
                ->setLastWillTopic('SIRENA/lastwill')
                ->setLastWillMessage('Laravel die')
                ->setLastWillQualityOfService(1);

            $mqtt = new MqttClient($server, $port, $clientId, MqttClient::MQTT_3_1);

            try {
                $mqtt->connect($connectionSettings, true);
                $mqtt->publish($topic, $message, 1);
                $contador++;
                $mqtt->disconnect();
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

        }
        
        $history = new Historic();
        $history->action = $message;
        $history->success = $contador;
        $history->id_user = $request->id_user;
        $history->save();

        $UserController = new UserController();
        $UserController->sendAlerts("TESTEANDO TODO", "Se corrió el test en todas las sirenas municipales");

        $result->message = 'Comando TEST enviado a sirenas';
        $result->mensajes_exitosos = $contador;

        return response()->json($result);

    }

    public function on_web(Request $request)
    {

        $result = new \stdClass();

        $id_web = $request->id_web;
        $server = "appsavemuniguate.com";
        $port = 8883;
        $cafile = "/etc/mosquitto/certs/chain.crt";
        $clientId = 'laravel-publisher-' . uniqid();
        $message = "ON";
        $contador = 0;

        $web = Web::with('sirens')->find($id_web);

        $sirens = $web->sirens;
        foreach ($sirens as $siren) {

            $topic = 'SIRENA/' . str($siren->id_siren);

            $connectionSettings = (new ConnectionSettings)
                ->setUseTls(true)
                ->setTlsCertificateAuthorityFile($cafile)
                ->setKeepAliveInterval(60)
                ->setLastWillTopic('SIRENA/lastwill')
                ->setLastWillMessage('Laravel die')
                ->setLastWillQualityOfService(1);

            $mqtt = new MqttClient($server, $port, $clientId, MqttClient::MQTT_3_1);

            try {
                $mqtt->connect($connectionSettings, true);
                $mqtt->publish($topic, $message, 1);
                $contador++;
                $mqtt->disconnect();
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

        }

        $history = new Historic();
        $history->action = $message;
        $history->id_web = $id_web;
        $history->success = $contador;
        $history->id_user = $request->id_user;
        $history->save();

        $UserController = new UserController();
        $UserController->sendAlerts("ACTIVANDO: ".$web->name, "Se ejecutó el comando para activar el corredor:" . $web->name);

        $result->message = 'Comando ON enviado a corredor';
        $result->mensajes_exitosos = $contador;

        return response()->json($result);

    }

    public function off_web(Request $request)
    {

        $result = new \stdClass();

        $id_web = $request->id_web;
        $server = "appsavemuniguate.com";
        $port = 8883;
        $cafile = "/etc/mosquitto/certs/chain.crt";
        $clientId = 'laravel-publisher-' . uniqid();
        $message = "OFF";
        $contador = 0;

        $web = Web::with('sirens')->find($id_web);
        
        $sirens = $web->sirens;
        foreach ($sirens as $siren) {

            $topic = 'SIRENA/' . str($siren->id_siren);

            $connectionSettings = (new ConnectionSettings)
                ->setUseTls(true)
                ->setTlsCertificateAuthorityFile($cafile)
                ->setKeepAliveInterval(60)
                ->setLastWillTopic('SIRENA/lastwill')
                ->setLastWillMessage('Laravel die')
                ->setLastWillQualityOfService(1);

            $mqtt = new MqttClient($server, $port, $clientId, MqttClient::MQTT_3_1);

            try {
                $mqtt->connect($connectionSettings, true);
                $mqtt->publish($topic, $message, 1);
                $contador++;
                $mqtt->disconnect();
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

        }

        $history = new Historic();
        $history->action = $message;
        $history->id_web = $id_web;
        $history->success = $contador;
        $history->id_user = $request->id_user;
        $history->save();

        $UserController = new UserController();
        $UserController->sendAlerts("APAGANDO: ".$web->name, "Se ejecutó el comando para apagar el corredor:" . $web->name, 2);

        $result->message = 'Comando OFF enviado a corredor';
        $result->mensajes_exitosos = $contador;

        return response()->json($result);

    }

    public function test_web(Request $request)
    {

        $result = new \stdClass();

        $id_web = $request->id_web;
        $server = "appsavemuniguate.com";
        $port = 8883;
        $cafile = "/etc/mosquitto/certs/chain.crt";
        $clientId = 'laravel-publisher-' . uniqid();
        $message = "TEST";
        $contador = 0;

        $web = Web::with('sirens')->find($id_web);

        $sirens = $web->sirens;
        foreach ($sirens as $siren) {

            $topic = 'TEST/' . str($siren->id_siren);

            $connectionSettings = (new ConnectionSettings)
                ->setUseTls(true)
                ->setTlsCertificateAuthorityFile($cafile)
                ->setKeepAliveInterval(60)
                ->setLastWillTopic('SIRENA/lastwill')
                ->setLastWillMessage('Laravel die')
                ->setLastWillQualityOfService(1);

            $mqtt = new MqttClient($server, $port, $clientId, MqttClient::MQTT_3_1);

            try {
                $mqtt->connect($connectionSettings, true);
                $mqtt->publish($topic, $message, 1);
                $contador++;
                $mqtt->disconnect();
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

        }

        $history = new Historic();
        $history->action = $message;
        $history->id_web = $id_web;
        $history->success = $contador;
        $history->id_user = $request->id_user;
        $history->save();

        $UserController = new UserController();
        $UserController->sendAlerts("TEST: ".$web->name, "Se corrió el test en el corredor:" . $web->name);

        $result->message = 'Comando TEST enviado a corredor';
        $result->mensajes_exitosos = $contador;

        return response()->json($result);

    }

    public function on_siren(Request $request)
    {

        $result = new \stdClass();

        $siren = $request->id_siren;
        $server = "appsavemuniguate.com";
        $port = 8883;
        $cafile = "/etc/mosquitto/certs/chain.crt";
        $clientId = 'laravel-publisher-' . uniqid();
        $topic = 'SIRENA/' . str($siren);
        $message = "ON";

        $connectionSettings = (new ConnectionSettings)
            ->setUseTls(true)
            ->setTlsCertificateAuthorityFile($cafile)
            ->setKeepAliveInterval(60)
            ->setLastWillTopic('SIRENA/lastwill')
            ->setLastWillMessage('Laravel die')
            ->setLastWillQualityOfService(1);

        $mqtt = new MqttClient($server, $port, $clientId, MqttClient::MQTT_3_1);

        try {

            $mqtt->connect($connectionSettings, true);
            $mqtt->publish($topic, $message, 1);
            $mqtt->disconnect();

            $result->success = true;
            $success = 1;

        } catch (\Exception $e) {

            $result = $e->getMessage();
            $success = 0;

        } finally {

            $history = new Historic();
            $history->action = $message;
            $history->success = $success;
            $history->id_siren = $siren;
            $history->id_user = $request->id_user;
            $history->save();

            $name = Siren::find($siren);

            $UserController = new UserController();
            $UserController->sendAlerts("ACTIVANDO: ".$name->name, "Se ejecutó el comando para activar la sirena:" . $name->name);

        }

        return response()->json($result);

    }

    public function off_siren(Request $request)
    {

        $result = new \stdClass();

        $siren = $request->id_siren;
        $server = "appsavemuniguate.com";
        $port = 8883;
        $cafile = "/etc/mosquitto/certs/chain.crt";
        $clientId = 'laravel-publisher-' . uniqid();
        $topic = 'SIRENA/' . str($siren);
        $message = "OFF";

        $connectionSettings = (new ConnectionSettings)
            ->setUseTls(true)
            ->setTlsCertificateAuthorityFile($cafile)
            ->setKeepAliveInterval(60)
            ->setLastWillTopic('SIRENA/lastwill')
            ->setLastWillMessage('Laravel die')
            ->setLastWillQualityOfService(1);

        $mqtt = new MqttClient($server, $port, $clientId, MqttClient::MQTT_3_1);

        try {

            $mqtt->connect($connectionSettings, true);
            $mqtt->publish($topic, $message, 1);
            $mqtt->disconnect();

            $result->success = true;
            $success = 1;

        } catch (\Exception $e) {

            $result = $e->getMessage();
            $success = 0;

        } finally {

            $history = new Historic();
            $history->action = $message;
            $history->success = $success;
            $history->id_siren = $siren;
            $history->id_user = $request->id_user;
            $history->save();

            $name = Siren::find($siren);

            $UserController = new UserController();
            $UserController->sendAlerts("APAGANDO: ".$name->name, "Se ejecutó el comando para apagar la sirena:" . $name->name, 2);

        }

        return response()->json($result);

    }

    public function test(Request $request)
    {

        $result = new \stdClass();
        
        $siren = $request->id_siren;
        $server = "appsavemuniguate.com";
        $port = 8883;
        $cafile = "/etc/mosquitto/certs/chain.crt";
        $clientId = 'laravel-publisher-' . uniqid();
        $topic = 'TEST/' . str($siren);
        $message = "TEST";

        $connectionSettings = (new ConnectionSettings)
            ->setUseTls(true)
            ->setTlsCertificateAuthorityFile($cafile)
            ->setKeepAliveInterval(60)
            ->setLastWillTopic('SIRENA/lastwill')
            ->setLastWillMessage('Laravel die')
            ->setLastWillQualityOfService(1);

        $mqtt = new MqttClient($server, $port, $clientId, MqttClient::MQTT_3_1);

        try {

            $mqtt->connect($connectionSettings, true);
            $mqtt->publish($topic, $message, 1);
            $mqtt->disconnect();

            $result->success = true;
            $success = 1;

        } catch (\Exception $e) {

            $result = $e->getMessage();
            $success = 0;

        } finally {

            $history = new Historic();
            $history->action = $message;
            $history->success = $success;
            $history->id_siren = $siren;
            $history->id_user = $request->id_user;
            $history->save();

            $name = Siren::find($siren);

            $UserController = new UserController();
            $UserController->sendAlerts("TEST: ".$name->name, "Se corrió el test en la sirena:" . $name->name);

        }

        return response()->json($result);

    }

    public function ping(Request $request)
    {

        $siren = $request->id_siren;
        
        $server = "appsavemuniguate.com";
        $port = 8883;
        $cafile = "/etc/mosquitto/certs/chain.crt";
        $clientId = 'laravel-publisher-' . uniqid();
        $topic = 'PING/' . str($siren);
        $message = "PING";

        $connectionSettings = (new ConnectionSettings)
            ->setUseTls(true)
            ->setTlsCertificateAuthorityFile($cafile)
            ->setKeepAliveInterval(60)
            ->setLastWillTopic('SIRENA/lastwill')
            ->setLastWillMessage('Laravel die')
            ->setLastWillQualityOfService(1);

        $mqtt = new MqttClient($server, $port, $clientId, MqttClient::MQTT_3_1);

        try {
            $mqtt->connect($connectionSettings, true);
            $mqtt->publish($topic, $message, 1);
            $mqtt->disconnect();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    /*
    * ACCIONES A BOCINAS INTELIGENTES
    */
    public function on_speakers()
    {
        
        $url = 'http://191.98.192.125:5880/alarmas.php';

        try {

            /* 
                En archivo se le pueden pasar 3 parametros: 
                    'silencio' para pruebas audio de 10 minutos
                    'sirena' para audio de ave personalizado dura 20 segundos
                    'sirena10' para reproducir el audio que dura 10 minutos (usar este para sismos)
            */
            $response = Http::asForm()->post($url, [
                'funcion' => 'alarma',
                'archivo' => 'sirena10'
            ]);

            return response()->json([
                'http_code' => $response->status(),
                'response' => $response->body(),
                'url' => $url
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Request failed',
                'message' => $e->getMessage()
            ], 500);

        }

    }

    public function off_speakers()
    {

        $url = 'http://191.98.192.125:5880/alarmas.php';

        try {

            $response = Http::asForm()->post($url, [
                'funcion' => 'abortar'
            ]);

            return response()->json([
                'http_code' => $response->status(),
                'response' => $response->body(),
                'url' => $url
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Request failed',
                'message' => $e->getMessage()
            ], 500);

        }

    }

    public function test_speakers()
    {

        $url = 'http://191.98.192.125:5880/alarmas.php';

        try {

            $response = Http::asForm()->post($url, [
                'funcion' => 'estado'
            ]);

            return response()->json([
                'http_code' => $response->status(),
                'response' => $response->body(),
                'url' => $url
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Request failed',
                'message' => $e->getMessage()
            ], 500);

        }

    }

}