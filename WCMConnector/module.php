<?
require __DIR__ . '/../libs/wcm-client/wcm.php';

class WCMConnector extends IPSModule {
    private const BETRIEBSART_HK_PREFIX = "BetriebsartHeizkreis";
    private Weishaupt $api;

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
        
        $this->RegisterTimer("Update", 0, "WCM_UpdateWCMStatus(".$this->InstanceID.");");
        
        $this->CreateVarProfileWCMBetriebsartHK();
    }
    
    public function ApplyChanges() {
        parent::ApplyChanges();
        
        for($i = $this->ReadPropertyInteger("FirstHK"); $i <= $this->ReadPropertyInteger("LastHK"); $i++) {
            $this->RegisterVariableInteger(self::BETRIEBSART_HK_PREFIX.$i, "Betriebsart Heizkreis ".$i, "WCM.BetriebsartHK");
            $this->EnableAction(self::BETRIEBSART_HK_PREFIX.$i);
        }
        
        $this->RegisterVariableInteger("KesselFehlercode", "Kessel Fehlercode");
        $this->RegisterVariableInteger("KesselLaststellung", "Kessel Laststellung", "~Intensity.100");
        $this->RegisterVariableFloat("KesselWaermeanforderung", "Kessel Wärmeanforderung", "~Temperature");
        $this->RegisterVariableFloat("KesselAussentemperatur", "Kessel Außentemperatur", "~Temperature");
        $this->RegisterVariableFloat("KesselVorlauftemperatur", "Kessel Vorlauftemperatur", "~Temperature");
        
        $this->RetrieveWCMStatus();
        $this->RequestAction("ParameterUpdate", $this->GetValue("ParameterUpdate"));
        
        $this->api = new Weishaupt(new WeishauptOptions($this->ReadPropertyString("URL"), $this->ReadPropertyString("Username"), $this->ReadPropertyString("Password")));
    }
    
    public function RequestAction($Ident, $Value) {

        switch($Ident) {
            case "ParameterUpdate":
                SetValue($this->GetIDForIdent($Ident), $Value);
                
                if($Value === True)
                    $this->SetTimerInterval("Update", $this->ReadPropertyInteger("UpdateInterval") * 1000);
                else
                    $this->SetTimerInterval("Update", 0);
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
        $this->api->bufferedUpdateBetriebsartHK($heizkreis, $betriebsart);
        $this->api->sendBuffer();
        $this->api->clearBuffer();
    }

    public function RetrieveWCMStatus() {
        $bufferPositions = [];
        
        for($i = $this->ReadPropertyInteger("FirstHK"); $i <= $this->ReadPropertyInteger("LastHK"); $i++) {
            $bufferPositions[self::BETRIEBSART_HK_PREFIX.$i] = $this->api->bufferedRequestBetriebsartHK($i);
        }
        
        //$bufferPositions["KesselFehlercode"] = $this->api->bufferedRequestFehlercode();
        $bufferPositions["KesselLaststellung"] = $this->api->bufferedRequestLaststellung();
        $bufferPositions["KesselWaermeanforderung"] = $this->api->bufferedRequestWaermeanforderung();
        $bufferPositions["KesselAussentemperatur"] = $this->api->bufferedRequestAussentemperatur();
        $bufferPositions["KesselVorlauftemperatur"] = $this->api->bufferedRequestVorlauftemperatur();
        
        $error = false;
        try {
            $response = $this->api->sendBuffer();
        } catch(Exception $e) {
            $error = true;
            $this->LogMessage($e->getMessage(), KL_ERROR);
        }
        
        foreach($bufferPositions as $key => $value) {
            if($error == true) {
                $this->SetValue($key, 0);
            } else {
                $this->SetValue($key, $response->getIterator()[$value]->DATA);
            }
        }
        
        $this->api->clearBuffer();
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