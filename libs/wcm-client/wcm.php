<?php
include __DIR__ . '/constants.php';

class WeishauptOptions {
    public string $url;
    public string $username;
    public string $password;
    
    public function __construct(string $url, string $username, string $password) {
        $this->url = $url;
        $this->username = $username;
        $this->password = $password;
    }
}

class TelegramObject {
    public int $MODULTYP;
    public int $BUSKENNUNG;
    public int $COMMAND;
    public int $INFONR;
    public int $INDEX;
    public int $PROT;
    public int $DATA;
    public int $HIGH_BYTE;
}

class FinalTelegramObject {
    public int $MODULTYP;
    public int $BUSKENNUNG;
    public int $COMMAND;
    public int $PROT;
    public int $INFONR;
    public int $INDEX;
    public int $DATA;
    public int $HIGH_BYTE;
    public string $UNIT;
}

class TelegramObjectCollection implements IteratorAggregate {
    protected array $items = [];

    public function add(TelegramObject $telegramObject) : void {
        $this->items[] = $telegramObject;
    }

    public function getIterator() : Traversable {
        return new ArrayIterator($this->items);
    }
}

class FinalTelegramObjectCollection implements IteratorAggregate {
    protected array $items = [];

    public function add(FinalTelegramObject $telegramObject) : void {
        $this->items[] = $telegramObject;
    }

    public function getIterator() : Traversable {
        return new ArrayIterator($this->items);
    }
}

class Weishaupt {
    /** URL of the API */
    private string $url;
    private string $username;
    private string $password;

    public function __construct(WeishauptOptions $options) {
        $this->url = $options->url;
        $this->username = $options->username;
        $this->password = $options->password;
    }


    public function getHKParameters(int $heizkreis): FinalTelegramObjectCollection {
        $body = [
            "prot" => "coco",
            "telegramm" => [
                [6, ($heizkreis - 1), Operation["Lesen"], Info["BetriebsartHK"], 0, 0, 0, 0]
            ]
        ];

        $res = $this->_callAPI("POST", $this->url."/parameter.json", $body);

        if ($res["http_code"] != 200) {
            throw new Exception("HTTP return code ".$res["http_code"]."\n".$res["header"].$res["body"]);
        }
        
        return $this->_decodeTelegram($res["header"]);
    }
    
    public function setHKParameters(int $heizkreis, BetriebsartHK $betriebsart): FinalTelegramObjectCollection {
        $body = [
            "prot" => "coco",
            "telegramm" => [
                [6, ($heizkreis - 1), Operation["Schreiben"], Info["BetriebsartHK"], 0, 0, $betriebsart->value, 0]
            ]
        ];

        $res = $this->_callAPI("POST", $this->url."/parameter.json", $body);

        if ($res["http_code"] != 200) {
            throw new Exception($res["body"]);
        }
        
        return $this->_decodeTelegram($res["header"]);
    }

    /**
     * Returns parameters present on Startsite
     */
    public function getHomeParameters(): FinalTelegramObjectCollection {
        $body = [
            "prot" => "coco",
            "telegramm" => [
                [0, 0, Operation["Lesen"], Info["Fehlercode"], 0, 0, 0, 0],
                [10, 0, Operation["Lesen"], Info["Waermeanforderung"], 0, 0, 0, 0],
                [10, 0, Operation["Lesen"], Info["Aussentemperatur"], 0, 0, 0, 0],
                [10, 0, Operation["Lesen"], Info["Vorlauftemperatur"], 0, 0, 0, 0]
            ]
        ];

        $res = $this->_callAPI("POST", $this->url."/parameter.json", $body);

        if ($res["http_code"] != 200) {
            throw new Exception($res["body"]);
        }

        return $this->_decodeTelegram($res["header"]);
    }

    /**
     * Returns the parameters present on WTC-G Process Parameter Page
     */
    public function getWTCGProcessParameters(): FinalTelegramObjectCollection {
        $body = [
            "prot" => "coco",
            "telegramm" => [
                [10, 0, Operation["Lesen"], Info["Laststellung"], 0, 0, 0, 0],
                [10, 0, Operation["Lesen"], Info["GedaempfteAussentemperatur"], 0, 0, 0, 0],
                [10, 0, Operation["Lesen"], Info["Waermeanforderung"], 0, 0, 0, 0],
                [10, 0, Operation["Lesen"], Info["VorlauftemperaturEstb"], 0, 0, 0, 0],
                [10, 0, Operation["Lesen"], Info["Abgastemperatur"], 0, 0, 0, 0],
                [10, 0, Operation["Lesen"], Info["Aussentemperatur"], 0, 0, 0, 0],
                [10, 0, Operation["Lesen"], Info["Warmwassertemperatur"], 0, 0, 0, 0],
                [10, 0, Operation["Lesen"], Info["Betriebsphase"], 0, 0, 0, 0]
            ]
        ];

        $res = $this->_callAPI("POST", $this->url."/parameter.json", $body);

        if ($res["http_code"] != 200) {
            throw new Exception($res["body"]);
        }

        return $this->_decodeTelegram($res["header"]);
    }

    /**
     * Returns the parameters from WCM-SOL Process Parameter Page
     */
    public function getWCMSOLProcessParameters(): FinalTelegramObjectCollection {
        $body = [
            "prot" => "coco",
            "telegramm" => [
                [3, 0, Operation["Lesen"], Info["T1Kollektor"], 0, 0, 0],
                [3, 0, Operation["Lesen"], Info["Durchfluss"], 0, 0, 0],
                [3, 0, Operation["Lesen"], Info["LeistungSolar"], 0, 0, 0],
                [3, 0, Operation["Lesen"], Info["T2SolarUnten"], 0, 0, 0],
                [3, 0, Operation["Lesen"], Info["B10PufferOben"], 0, 0, 0],
                [3, 0, Operation["Lesen"], Info["B11PufferUnten"], 0, 0, 0]
            ]
        ];

        $res = $this->_callAPI("POST", $this->url."/parameter.json", $body);

        if ($res["http_code"] != 200) {
            throw new Exception($res["body"]);
        }

        return $this->_decodeTelegram($res["header"]);
    }

    /**
     * Decodes a Telegram given from API
     * @param body telegram as given as response from API
     */
    private function _decodeTelegram(string $body): FinalTelegramObjectCollection {
        $response = new TelegramObjectCollection();

        $decoded = json_decode($body);

        $telegramArray = $decoded->telegramm;
        
        foreach($telegramArray as $telegramEntry) {
            $respObj = new TelegramObject();
            foreach($telegramEntry as $i => $value) {
                $attributeName = array_search($i, Type);
                $respObj->{$attributeName} = $value;
            }
            $response->add($respObj);
        }

        return $this->_decodeTelegramValues($response);
    }

    /**
     * Decodes the values of an array of telegramObjects, then returns an array of FinalTelegramObjects
     *
     * @param telegramObjects Array matching the interface
     */
    private function _decodeTelegramValues(TelegramObjectCollection $telegramObjects): FinalTelegramObjectCollection {
        $finalTelegramObjects = new FinalTelegramObjectCollection();
        foreach ($telegramObjects as $telegramObject) {
            $finalTelegramObj = new FinalTelegramObject();
            $finalTelegramObj->COMMAND = $telegramObject->COMMAND;
            $finalTelegramObj->MODULTYP = $telegramObject->COMMAND;
            $finalTelegramObj->DATA = $this->_convertData($telegramObject);
            $finalTelegramObj->BUSKENNUNG = $telegramObject->BUSKENNUNG;
            $finalTelegramObj->PROT = $telegramObject->PROT;
            $finalTelegramObj->INDEX = $telegramObject->INDEX;
            $finalTelegramObj->INFONR = $telegramObject->INFONR;
            $finalTelegramObj->HIGH_BYTE = $telegramObject->HIGH_BYTE;
            
            $enumName = array_search($finalTelegramObj->INFONR, Info);
            $match = array_key_exists($enumName, Unit);

            if($match !== false) {
                $finalTelegramObj->UNIT = Unit[$enumName];
            }
            
            $finalTelegramObjects->add($finalTelegramObj);
        }

        return $finalTelegramObjects;
    }

    /**
     * Data is extracted and converted according to its INFONR field
     *
     * @param telegramObject a single telegramObject
     */
    private function _convertData(TelegramObject $telegramObject): int {
        switch ($telegramObject->INFONR) {
            case Info["VorlauftemperaturEstb"]:
            case Info["GedaempfteAussentemperatur"]:
            case Info["Waermeanforderung"]:
            case Info["Aussentemperatur"]:
            case Info["Warmwassertemperatur"]:
            case Info["Abgastemperatur"]:
            case Info["Vorlauftemperatur"]:
            case Info["T2SolarUnten"]:
            case Info["B11PufferUnten"]:
            case Info["B10PufferOben"]:
            case Info["T1Kollektor"]:
                $val = $this->_extractValue($telegramObject->DATA, $telegramObject->HIGH_BYTE);
                return (int) ($val / 10);
            case Info["LeistungSolar"]:
            case Info["Durchfluss"]:
                $val = $this->_extractValue($telegramObject->DATA, $telegramObject->HIGH_BYTE);
                return (int) ($val / 100);
            case Info["Fehlercode"]:
            case Info["Password"]:
            case Info["StartsiteFooter"]:
            case Info["Laststellung"]:
            case Info["Betriebsphase"]:
            case Info["BetriebsartHK"]:
                return $telegramObject->DATA;
            default:
                throw new Exception("Unknown Info: {$telegramObject->INFONR}");
        }
    }

    /**
     * Calculate the Value from the low byte and high byte
     *
     * @param lowByte
     * @param highByte
     */
    private function _extractValue(int $lowByte, int $highByte): int {
        $usValue;

        if ($highByte <= 127) {
            $usValue = $highByte * 256 + $lowByte;
        } else if ($highByte === 128 && $lowByte === 0) {
            $usValue = $highByte * 256 + $lowByte;
        } else {
            $usValue = -32768 + ($highByte - 128) * 256 + $lowByte;
        }
        return $usValue;
    }
    
    // Method: POST, PUT, GET etc
    // Data: array("param" => "value") ==> index.php?param=value
    private function _callAPI($method, $url, $data = false) {
        $curl = curl_init();

        switch ($method)
        {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);

                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_PUT, 1);
                break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }

        // Optional Authentication:
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($curl, CURLOPT_USERPWD, $this->username.":".$this->password);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $res = curl_exec($curl);
        $error = curl_error($curl);
        
        $result = array( 'header' => '',
                         'body' => '',
                         'curl_error' => '',
                         'http_code' => '',
                         'last_url' => '');
        if ($error != "") {
            $result['curl_error'] = $error;
            return $result;
        }
        
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $result['header'] = substr($res, 0, $header_size);
        $result['body'] = substr($res, $header_size);
        $result['http_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $result['last_url'] = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);

        curl_close($curl);

        return $result;
    }
}
?>