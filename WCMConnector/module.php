<?
require __DIR__ . '/../libs/wcm-client/wcm.php';

class WCMConnector extends IPSModule {


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
            $this->RegisterVariableInteger("BetriebsartHeizkreis".$i, "Betriebsart Heizkreis ".$i, "WCM.BetriebsartHK");
        }
        
        $this->RegisterVariableInteger("KesselFehlercode", "Kessel Fehlercode");
        $this->RegisterVariableInteger("KesselLaststellung", "Kessel Laststellung", "~Intensity.100");
        $this->RegisterVariableFloat("KesselWaermeanforderung", "Kessel Wärmeanforderung", "~Temperature");
        $this->RegisterVariableFloat("KesselAussentemperatur", "Kessel Außentemperatur", "~Temperature");
        $this->RegisterVariableFloat("KesselVorlauftemperatur", "Kessel Vorlauftemperatur", "~Temperature");
        
        $this->UpdateWCMStatus();
        $this->RequestAction("ParameterUpdate", $this->GetValue("ParameterUpdate"));
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
                throw new Exception("Invalid Ident");
        }
        
    }
    
    public function UpdateWCMStatus() {
        $api = new Weishaupt(new WeishauptOptions($this->ReadPropertyString("URL"), $this->ReadPropertyString("Username"), $this->ReadPropertyString("Password")));
        
        $bufferPositions = [];
        
        for($i = $this->ReadPropertyInteger("FirstHK"); $i <= $this->ReadPropertyInteger("LastHK"); $i++) {
            $bufferPositions["BetriebsartHeizkreis".$i] = $api->bufferedRequestBetriebsartHK($i);
        }
        
        //$bufferPositions["KesselFehlercode"] = $api->bufferedRequestFehlercode();
        $bufferPositions["KesselLaststellung"] = $api->bufferedRequestLaststellung();
        $bufferPositions["KesselWaermeanforderung"] = $api->bufferedRequestWaermeanforderung();
        $bufferPositions["KesselAussentemperatur"] = $api->bufferedRequestAussentemperatur();
        $bufferPositions["KesselVorlauftemperatur"] = $api->bufferedRequestVorlauftemperatur();
        
        $response = $api->sendBuffer();
        
        foreach($bufferPositions as $key => $value) {
            $this->SetValue($key, $response->getIterator()[$value]->DATA);
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