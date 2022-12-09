<?
//require __DIR__ . '/../libs/';

class WCMClient extends IPSModule {

    public function Create() {
        parent::Create();
        
        $this->RegisterPropertyString("URL", "[]");
        $this->RegisterPropertyString("Username", "[]");
        $this->RegisterPropertyString("Password", "[]");
    }
    
    public function ApplyChanges() {
        parent::ApplyChanges();
    }
    
    public function MeineErsteEigeneFunktion() {
        echo $this->RegisterPropertyString("URL");
    }
}
?>