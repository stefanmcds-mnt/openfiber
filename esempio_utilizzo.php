<?php

use OpenFiber\DynamicSoapClient;
use OpenFiber\DynamicDtoInterface;
use InvalidArgumentException;

// 1. Definiamo e valorizziamo il DTO al volo come classe anonima con le regole di PHP 8.4
$attivazioneAlVolo = new #[Readonly] class(
    codiceOperatore: '321',
    codiceOrdineOlo: '321_ORD20260918',
    idNotifica: 'NOT-' . uniqid(),
    cognomeCliente: 'Rossi S.p.A.',
    telefono: '0141123456',
    idBuilding: '01005_0023_00012',
    pop: 'POP_AT_01',
    profilo: 'DIA_1GB_100MB'
) implements DynamicDtoInterface {

    // Validazione immediata tramite Property Hooks di PHP 8.4 al momento dell'assegnazione
    public string $CODICE_ORDINE_OLO {
        set {
            if (strlen($value) > 18) throw new InvalidArgumentException("Lunghezza massima 18 caratteri.");
            if (preg_match('/[\x5C\x2F\x2A\x3F\x3C\x3E\x7C\x24]/', $value)) { // Caratteri speciali vietati da specifiche: \, /, *, ?, <, >, |, $
                throw new InvalidArgumentException("Caratteri speciali non ammessi nel CODICE_ORDINE_OLO.");
            }
            $this->CODICE_ORDINE_OLO = $value;
        }
    }

    public string $CODICE_OPERATORE {
        set {
            if (strlen($value) !== 3) throw new InvalidArgumentException("Il codice operatore deve essere di 3 cifre.");
            $this->CODICE_OPERATORE = $value;
        }
    }

    public function __construct(
        string $codiceOperatore,
        string $codiceOrdineOlo,
        public string $ID_NOTIFICA,
        public string $COGNOME_CLIENTE,
        public string $RECAPITO_TELEFONICO_CLIENTE_1,
        public string $ID_BUILDING,
        public string $IDENTIFICATIVO_DEL_POP,
        public string $PROFILO
    ) {
        $this->CODICE_OPERATORE = $codiceOperatore;
        $this->CODICE_ORDINE_OLO = $codiceOrdineOlo;
    }

    public function toSoapPayload(): array 
    {
        return [
            'CODICE_OPERATORE' => $this->CODICE_OPERATORE,
            'CODICE_ORDINE_OLO' => $this->CODICE_ORDINE_OLO,
            'DATA_NOTIFICA' => (new \DateTimeImmutable())->format('Y-m-d\TH:i:sP'), // Formato richiesto YYYY-MM-DDThh:mm:ssTZD
            'ID_NOTIFICA' => $this->ID_NOTIFICA,
            'COGNOME_CLIENTE' => $this->COGNOME_CLIENTE,
            'RECAPITO_TELEFONICO_CLIENTE_1' => $this->RECAPITO_TELEFONICO_CLIENTE_1,
            'ID_BUILDING' => $this->ID_BUILDING,
            'IDENTIFICATIVO_DEL_POP' => $this->IDENTIFICATIVO_DEL_POP,
            'PROFILO' => $this->PROFILO,
            'DATA_PREVISTA_ATTIVAZIONE' => (new \DateTimeImmutable('+14 days'))->format('Y-m-d') // Formato richiesto YYYY-MM-DD
        ];
    }
};

// 2. Passiamo l'oggetto istanziato direttamente al costruttore del client ed inviamo
$client = new DynamicSoapClient($pdo, 'OLO_ActivationSetup_DIA', $attivazioneAlVolo);
$risposta = $client->send();

print_r($risposta);

