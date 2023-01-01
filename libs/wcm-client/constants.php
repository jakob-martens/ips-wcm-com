<?php
const Type = array(
    "MODULTYP" => 0,
    "BUSKENNUNG" => 1,
    "COMMAND" => 2,
    "INFONR" => 3,
    "INDEX" => 4,
    "PROT" => 5,
    "DATA" => 6,
    "HIGH_BYTE" => 7,
);

const Command = array(
    "READ" => 1,
    "WRITE" => 2,
    "ERROR" => 255,
);

const Protocol = array(
    "STANDARD" => 0,
    "GENERIC" => 1,
);

const Info = array(
    /** Take it as it is */
    "Fehlercode" => 1,
    /** Divide by 10 */
    "Waermeanforderung" => 2,
    /** Divide by 10 */
    "Aussentemperatur" => 12,
    /** We need to calculate: 4 is 26 and 14 is 27, 24 is 28 */
    "Vorlauftemperatur" => 13,
    /** Divide by 10 */
    "Warmwassertemperatur" => 14,
    /** Divide by 10 */
    "Ruecklauftemperatur" => 22,
    /** Divide by 10 */
    "B10PufferOben" => 118,
    /** Divide by 10 */
    "B11PufferUnten" => 120,
    /** Divide by 100 */
    "Durchfluss" => 130,
    /** As it is, it is percent */
    "Laststellung" => 138,
    /** Divide by 10 */
    "MaxLeistungHeizung" => 319,
    /** Divide by 10 */
    "MaxLeistungWW" => 345,
    /** Divide by 10 */
    "Abgastemperatur" => 325,
    /** Take as it is */
    "Betriebsphase" => 373,
    /** Divide by 100 */
    "LeistungSolar" => 475,
    /** Divide by 10 */
    "GedaempfteAussentemperatur" => 2572,
    /** Divide by 10 */
    "T1Kollektor" => 2601,
    /** Divide by 10 */
    "T2SolarUnten" => 2602,
    /** Divide by 10 */
    "VorlauftemperaturEstb" => 3101,
    "StartsiteFooter" => 5066,
    "Password" => 5056,
    "BetriebsartHK" => 274,
);

const Unit = array(
    "Waermeanforderung" => '°C',
    "Aussentemperatur" => '°C',
    "Vorlauftemperatur" => '°C',
    "Warmwassertemperatur" => '°C',
    "B10PufferOben" => '°C',
    "B11PufferUnten" => '°C',
    "Durchfluss" => 'l/min',
    "Laststellung" => '%',
    "MaxLeistungHeizung" => '%',
    "MaxLeistungWW" => '%',
    "Abgastemperatur" => '°C',
    "LeistungSolar" => 'W',
    "GedaempfteAussentemperatur" => '°C',
    "T1Kollektor" => '°C',
    "T2SolarUnten" => '°C',
    "VorlauftemperaturEstb" => '°C',
);

const BetriebsartHK = array(
    "Standby" => 1,
    "Normal" => 3,
    "Absenk" => 4,
    "Sommer" => 5,
    "Programm1" => 11,
    "Programm2" => 12,
    "Programm3" => 13,
    "WieLeitstelle" => 255,
);

const Operation = array(
    "Lesen" => 1,
    "Schreiben" => 2,
);
?>