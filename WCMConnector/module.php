<?
require __DIR__ . '/../libs/wcm-client/wcm.php';

class WCMConnector extends IPSModule {
    private const BETRIEBSART_HK_PREFIX = "BetriebsartHeizkreis";

    public function Create() {
        parent::Create();
        
        $this->RegisterVariableBoolean("ParameterUpdate", "Parameter Update", "~Switch");
        $this->EnableAction("ParameterUpdate");

        $this->RegisterPropertyString("URL", "");
        $this->RegisterPropertyString("Username", "");
        $this->RegisterPropertyString("Password", "");
        $this->RegisterPropertyInteger("UpdateInterval", 0);
        $this->RegisterPropertyInteger("FirstHK", 2);
        $this->RegisterPropertyInteger("LastHK", 5);
        
        $this->RegisterTimer("Update", 0, "WCM_RetrieveWCMStatus(".$this->InstanceID.", true);");
        
        $this->CreateVarProfileWCMBetriebsartHK();
        $this->CreateVarProfileWCMMaxLeistung();
    }
    
    public function ApplyChanges() {
        parent::ApplyChanges();
                
        for($i = $this->ReadPropertyInteger("FirstHK"); $i <= $this->ReadPropertyInteger("LastHK"); $i++) {
            $catID = IPS_CreateCategory();
            IPS_SetName($catID, "Heizkreis ".$i);
            IPS_SetParent($catID, $this->InstanceID);
            
            $this->RegisterVariableInteger(self::BETRIEBSART_HK_PREFIX.$i, "Betriebsart Heizkreis ".$i, "WCM.BetriebsartHK");
            $this->EnableAction(self::BETRIEBSART_HK_PREFIX.$i);
            IPS_SetParent($this->GetIDForIdent(self::BETRIEBSART_HK_PREFIX.$i), $catID);
        }
        
        $this->RegisterVariableInteger("KesselFehlercode", "Kessel Fehlercode", "", 1);
        $this->RegisterVariableFloat("KesselAussentemperatur", "Kessel Außentemperatur", "~Temperature", 2);
        $this->RegisterVariableFloat("KesselGedaempfteAussentemperatur", "Kessel Gedämpfte Außentemperatur", "~Temperature", 3);
        $this->RegisterVariableInteger("KesselLaststellung", "Kessel Laststellung", "~Intensity.100", 4);
        $this->RegisterVariableInteger("KesselMaxLeistungHeizung", "Kessel Max. Leistung Heizung", "WCM.MaxLeistung", 5);
        $this->EnableAction("KesselMaxLeistungHeizung");
        $this->RegisterVariableInteger("KesselMaxLeistungWW", "Kessel Max. Leistung WW", "WCM.MaxLeistung", 6);
        $this->EnableAction("KesselMaxLeistungWW");
        $this->RegisterVariableFloat("KesselWaermeanforderung", "Kessel Wärmeanforderung", "~Temperature", 7);
        $this->RegisterVariableFloat("KesselVorlauftemperatur", "Kessel Vorlauftemperatur", "~Temperature", 8);
        $this->RegisterVariableFloat("KesselVorlauftemperaturEstb", "Kessel Vorlauftemperatur eSTB", "~Temperature", 9);
        $this->RegisterVariableFloat("KesselRuecklauftemperatur", "Kessel Rücklauftemperatur", "~Temperature", 10);
        $this->RegisterVariableFloat("KesselWarmwassertemperatur", "Kessel Warmwassertemperatur", "~Temperature", 11);
        $this->RegisterVariableFloat("KesselAbgastemperatur", "Kessel Abgastemperatur", "~Temperature", 12);
        
        $this->RequestAction("ParameterUpdate", $this->GetValue("ParameterUpdate"));
    }
    
    public function RequestAction($Ident, $Value) {

        switch($Ident) {
            case "ParameterUpdate":
                SetValue($this->GetIDForIdent($Ident), $Value);
                
                if($Value === True) {
                    $this->RetrieveWCMStatus(true);
                    $this->SetTimerInterval("Update", $this->ReadPropertyInteger("UpdateInterval") * 1000);
                } else {
                    $this->SetTimerInterval("Update", 0);
                    $this->RetrieveWCMStatus(false);
                }
                break;
            case "KesselMaxLeistungHeizung":
                $this->UpdateWCMMaxLeistungHeizung($Value);
                SetValue($this->GetIDForIdent($Ident), $Value);
                break;
            case "KesselMaxLeistungWW":
                $this->UpdateWCMMaxLeistungWW($Value);
                SetValue($this->GetIDForIdent($Ident), $Value);
                break;
            default:
                if(strpos($Ident, self::BETRIEBSART_HK_PREFIX) !== false) {
                    $this->UpdateWCMBetriebsartHK(intval(substr($Ident, strlen(self::BETRIEBSART_HK_PREFIX))), $Value);
                    SetValue($this->GetIDForIdent($Ident), $Value);
                } else {
                    throw new Exception("Invalid Ident");
                }
        }
        
    }
    public function UpdateWCMBetriebsartHK(int $heizkreis, int $betriebsart) {
        $api = new Weishaupt(new WeishauptOptions($this->ReadPropertyString("URL"), $this->ReadPropertyString("Username"), $this->ReadPropertyString("Password")));

        $api->bufferedUpdateBetriebsartHK($heizkreis, $betriebsart);
        $api->sendBuffer(10);
        $api->clearBuffer();
    }

    public function UpdateWCMMaxLeistungHeizung(int $maxLeistung) {
        $api = new Weishaupt(new WeishauptOptions($this->ReadPropertyString("URL"), $this->ReadPropertyString("Username"), $this->ReadPropertyString("Password")));

        $api->bufferedUpdateMaxLeistungHeizung($maxLeistung);
        $api->sendBuffer(10);
        $api->clearBuffer();
    }

    public function UpdateWCMMaxLeistungWW(int $maxLeistung) {
        $api = new Weishaupt(new WeishauptOptions($this->ReadPropertyString("URL"), $this->ReadPropertyString("Username"), $this->ReadPropertyString("Password")));

        $api->bufferedUpdateMaxLeistungWW($maxLeistung);
        $api->sendBuffer(10);
        $api->clearBuffer();
    }

    public function RetrieveWCMStatus(bool $sendBuffer = true) {
        $api = new Weishaupt(new WeishauptOptions($this->ReadPropertyString("URL"), $this->ReadPropertyString("Username"), $this->ReadPropertyString("Password")));

        $bufferPositions = [];
        
        $bufferPositions["KesselFehlercode"] = $api->bufferedRequestFehlercode();

        for($i = $this->ReadPropertyInteger("FirstHK"); $i <= $this->ReadPropertyInteger("LastHK"); $i++) {
            $bufferPositions[self::BETRIEBSART_HK_PREFIX.$i] = $api->bufferedRequestBetriebsartHK($i);
        }
        
        $bufferPositions["KesselLaststellung"] = $api->bufferedRequestLaststellung();
        $bufferPositions["KesselMaxLeistungHeizung"] = $api->bufferedRequestMaxLeistungHeizung();
        $bufferPositions["KesselMaxLeistungWW"] = $api->bufferedRequestMaxLeistungWW();
        $bufferPositions["KesselAussentemperatur"] = $api->bufferedRequestAussentemperatur();
        $bufferPositions["KesselGedaempfteAussentemperatur"] = $api->bufferedRequestGedaempfteAussentemperatur();
        $bufferPositions["KesselWaermeanforderung"] = $api->bufferedRequestWaermeanforderung();
        $bufferPositions["KesselVorlauftemperatur"] = $api->bufferedRequestVorlauftemperatur();
        $bufferPositions["KesselVorlauftemperaturEstb"] = $api->bufferedRequestVorlauftemperaturEstb();
        $bufferPositions["KesselRuecklauftemperatur"] = $api->bufferedRequestRuecklauftemperatur();
        $bufferPositions["KesselWarmwassertemperatur"] = $api->bufferedRequestWarmwassertemperatur();
        $bufferPositions["KesselAbgastemperatur"] = $api->bufferedRequestAbgastemperatur();

        $error = false;
        if($sendBuffer == true) {
            try {
                $response = $api->sendBuffer();
            } catch(Exception $e) {
                $error = true;
                $this->LogMessage($e->getMessage(), KL_ERROR);
            }
        }
        
        foreach($bufferPositions as $key => $value) {
            if($error == true || $sendBuffer == false) {
                $this->SetValue($key, 0);
            } else {
                $this->SetValue($key, $response->getIterator()[$value]->DATA);
            }
        }
        
        $api->clearBuffer();
    }
    
    private function CreateVarProfileWCMBetriebsartHK() {
		if (!IPS_VariableProfileExists("WCM.BetriebsartHK")) {
			IPS_CreateVariableProfile("WCM.BetriebsartHK", 1);
			IPS_SetVariableProfileValues("WCM.BetriebsartHK", 1, 255, 0);
            foreach(BetriebsartHK as $name => $value) {
                IPS_SetVariableProfileAssociation("WCM.BetriebsartHK", $value, $name, "", -1);
            }
		 }
	}
    
    private function CreateVarProfileWCMMaxLeistung() {
		if (!IPS_VariableProfileExists("WCM.MaxLeistung")) {
			IPS_CreateVariableProfile("WCM.MaxLeistung", 1);
			IPS_SetVariableProfileValues("WCM.MaxLeistung", 36, 100, 1);
		 }
	}
}
?>