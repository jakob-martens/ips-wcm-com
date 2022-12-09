<?php
enum Type: int {
    case MODULTYP = 0;
    case BUSKENNUNG = 1;
    case COMMAND = 2;
    case INFONR = 3;
    case INDEX = 4;
    case PROT = 5;
    case DATA = 6;
    case HIGH_BYTE = 7;
}

enum Command: int {
    case READ = 1;
    case WRITE = 2;
    case ERROR = 255;
}

enum Protocol: int {
    case STANDARD = 0;
    case GENERIC = 1;
}

enum Info: int {
    /** Take it as it is */
    case Fehlercode = 1;
    /** Divide by 10 */
    case Waermeanforderung = 2;
    /** Divide by 10 */
    case Aussentemperatur = 12;
    /** We need to calculate: 4 is 26 and 14 is 27, 24 is 28 */
    case Vorlauftemperatur = 13;
    /** Divide by 10 */
    case Warmwassertemperatur = 14;
    /** Divide by 10 */
    case B10PufferOben = 118;
    /** Divide by 10 */
    case B11PufferUnten = 120;
    /** Divide by 100 */
    case Durchfluss = 130;
    /** As it is, it is percent */
    case Laststellung = 138;
    /** Divide by 10 */
    case Abgastemperatur = 325;
    /** Take as it is */
    case Betriebsphase = 373;
    /** Divide by 100 */
    case LeistungSolar = 475;
    /** Divide by 10 */
    case GedaempfteAussentemperatur = 2572;
    /** Divide by 10 */
    case T1Kollektor = 2601;
    /** Divide by 10 */
    case T2SolarUnten = 2602;
    /** Divide by 10 */
    case VorlauftemperaturEstb = 3101;
    case StartsiteFooter = 5066;
    case Password = 5056;
    case BetriebsartHK = 274;
}

enum Unit: string {
    case Waermeanforderung = '°C';
    case Aussentemperatur = '°C';
    case Vorlauftemperatur = '°C';
    case Warmwassertemperatur = '°C';
    case B10PufferOben = '°C';
    case B11PufferUnten = '°C';
    case Durchfluss = 'l/min';
    case Laststellung = '%';
    case Abgastemperatur = '°C';
    case LeistungSolar = 'W';
    case GedaempfteAussentemperatur = '°C';
    case T1Kollektor = '°C';
    case T2SolarUnten = '°C';
    case VorlauftemperaturEstb = '°C';
}

enum BetriebsartHK: int {
    case Standby = 1;
    case Normal = 3;
    case Absenk = 4;
    case Sommer = 5;
    case Programm1 = 11;
    case Programm2 = 12;
    case Programm3 = 13;
    case WieLeitstelle = 255;
}

enum Operation: int {
    case Lesen = 1;
    case Schreiben = 2;
}
?>