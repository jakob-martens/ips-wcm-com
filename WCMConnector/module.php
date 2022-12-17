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
    }
    
    public function ApplyChanges() {
        parent::ApplyChanges();
                
        for($i = $this->ReadPropertyInteger("FirstHK"); $i <= $this->ReadPropertyInteger("LastHK"); $i++) {
            $this->RegisterVariableInteger(self::BETRIEBSART_HK_PREFIX.$i, "Betriebsart Heizkreis ".$i, "WCM.BetriebsartHK");
            $this->EnableAction(self::BETRIEBSART_HK_PREFIX.$i);
        }
        
        $this->RegisterVariableInteger("KesselFehlercode", "Kessel Fehlercode");
        $this->RegisterVariableFloat("KesselAussentemperatur", "Kessel Außentemperatur", "~Temperature");
        $this->RegisterVariableFloat("KesselGedaempfteAussentemperatur", "Kessel Gedämpfte Außentemperatur", "~Temperature");
        $this->RegisterVariableInteger("KesselLaststellung", "Kessel Laststellung", "~Intensity.100");
        $this->RegisterVariableFloat("KesselWaermeanforderung", "Kessel Wärmeanforderung", "~Temperature");
        $this->RegisterVariableFloat("KesselVorlauftemperatur", "Kessel Vorlauftemperatur", "~Temperature");
        $this->RegisterVariableFloat("KesselVorlauftemperaturEstb", "Kessel Vorlauftemperatur eSTB", "~Temperature");
        $this->RegisterVariableFloat("KesselWarmwassertemperatur", "Kessel Warmwassertemperatur", "~Temperature");
        $this->RegisterVariableFloat("KesselAbgastemperatur", "Kessel Abgastemperatur", "~Temperature");
        
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
            default:
                if(strpos($Ident, self::BETRIEBSART_HK_PREFIX) !== false) {
                    SetValue($this->GetIDForIdent($Ident), $Value);
                    $this->UpdateWCMBetriebsartHK(intval(substr($Ident, strlen(self::BETRIEBSART_HK_PREFIX))), $Value);
                } else {
                    throw new Exception("Invalid Ident");
                }
        }
        
    }
    public function UpdateWCMBetriebsartHK(int $heizkreis, int $betriebsart) {
        $api = new Weishaupt(new WeishauptOptions($this->ReadPropertyString("URL"), $this->ReadPropertyString("Username"), $this->ReadPropertyString("Password")));

        $api->bufferedUpdateBetriebsartHK($heizkreis, $betriebsart);
        $api->sendBuffer();
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
        $bufferPositions["KesselAussentemperatur"] = $api->bufferedRequestAussentemperatur();
        $bufferPositions["KesselGedaempfteAussentemperatur"] = $api->bufferedRequestGedaempfteAussentemperatur();
        $bufferPositions["KesselWaermeanforderung"] = $api->bufferedRequestWaermeanforderung();
        $bufferPositions["KesselVorlauftemperatur"] = $api->bufferedRequestVorlauftemperatur();
        $bufferPositions["KesselVorlauftemperaturEstb"] = $api->bufferedRequestVorlauftemperaturEstb();
        //$bufferPositions["KesselWarmwassertemperatur"] = $api->bufferedRequestWarmwassertemperatur();
        //$bufferPositions["KesselAbgastemperatur"] = $api->bufferedRequestAbgastemperatur();

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
}
?>