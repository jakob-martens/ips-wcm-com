<?
require __DIR__ . '/../libs/wcm-client/wcm.php';

class WCMConnector extends IPSModule {


    public function Create() {
        parent::Create();
        
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
        
        $this->UpdateWCMStatus();
        
        $this->SetTimerInterval("Update", $this->ReadPropertyInteger("UpdateInterval") * 1000);
    }
    
    public function UpdateWCMStatus() {
        $api = new Weishaupt(new WeishauptOptions($this->ReadPropertyString("URL"), $this->ReadPropertyString("Username"), $this->ReadPropertyString("Password")));
        
        for($i = $this->ReadPropertyInteger("FirstHK"); $i <= $this->ReadPropertyInteger("LastHK"); $i++) {
            $params = $api->getHKParameters($i);
            $this->SetValue("BetriebsartHeizkreis".$i, $params->getIterator()[0]->DATA);
        }
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