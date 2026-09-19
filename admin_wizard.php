<?php
declare(strict_types=1);

// 1. Connessione al database
$pdo = new \PDO('mysql:host=localhost;dbname=openfiber_db', 'user', 'password', [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
]);

$messaggio = "";

// 2. Se l'utente preme "Salva", elaboriamo i dati
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codiceOperatore = $_POST['pkg_operator_code'] ?? '';
    $modalitaStorage = $_POST['pkg_storage_mode'] ?? 'hybrid';

    // Validazione base: il codice deve essere di esattamente 3 caratteri (Specifiche Pag. 21)
    if (strlen($codiceOperatore) !== 3) {
        $messaggio = "❌ Errore: Il codice operatore deve essere di esattamente 3 cifre.";
    } else {
        // Salva su Database (Query di inserimento o aggiornamento se già esistente)
        $stmt = $pdo->prepare("INSERT INTO of_package_config (config_key, config_value) VALUES (?, ?) 
                                ON DUPLICATE KEY UPDATE config_value = ?");
        
        $stmt->execute(['olo_operator_code', $codiceOperatore, $codiceOperatore]);
        $stmt->execute(['storage_mode', $modalitaStorage, $modalitaStorage]);

        // Salva in parallelo su File (Manifest JSON locale per cache rapida del sistema)
        $configManifest = [
            'olo_operator_code' => $codiceOperatore,
            'storage_mode' => $modalitaStorage,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        file_put_contents(__DIR__ . '/config_manifest.json', json_encode($configManifest, JSON_PRETTY_PRINT));

        $messaggio = "✅ Configurazione Step 1 salvata con successo a Database e su File JSON!";
    }
}

// 3. Recuperiamo i dati attuali per pre-compilare il form se l'utente ricarica la pagina
$stmtCode = $pdo->query("SELECT config_value FROM of_package_config WHERE config_key = 'olo_operator_code'");
$currentCode = $stmtCode->fetchColumn() ?: '';

$stmtStorage = $pdo->query("SELECT config_value FROM of_package_config WHERE config_key = 'storage_mode'");
$currentStorage = $stmtStorage->fetchColumn() ?: 'hybrid';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Open Fiber Package - Step 1</title>
    <script src="https://jsdelivr.net"></script>
</head>
<body class="bg-slate-900 text-slate-100 p-8">

    <div class="max-w-2xl mx-auto bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-lg">
        
        <!-- Intestazione dello Step -->
        <div class="border-b border-slate-700 pb-4 mb-6">
            <span class="text-xs font-mono text-indigo-400 font-bold uppercase tracking-wider">Configurazione Guidata</span>
            <h2 class="text-xl font-bold text-white mt-1">Step 1: Identificazione Operatore OLO</h2>
            <p class="text-xs text-slate-400 mt-1">Imposta il codice identificativo aziendale e la strategia di archiviazione dei tracciati di rete.</p>
        </div>

        <!-- Notifiche di successo o errore -->
        <?php if (!empty($messaggio)): ?>
            <div class="mb-4 p-3 rounded text-xs font-mono bg-slate-900 border border-slate-700 text-slate-300">
                <?php echo $messaggio; ?>
            </div>
        <?php endif; ?>

        <!-- Form dello Step 1 -->
        <form action="" method="POST" class="space-y-6">
            
            <!-- Campo Codice Operatore -->
            <div>
                <label class="block text-xs font-mono text-slate-300 mb-2">Codice Operatore OLO (Assegnato da Open Fiber - 3 cifre)</label>
                <input type="text" name="pkg_operator_code" maxlength="3" value="<?php echo htmlspecialchars($currentCode); ?>" 
                       class="w-full bg-slate-900 border border-slate-600 p-3 rounded font-mono text-center text-lg tracking-widest text-white focus:border-indigo-500 focus:outline-none" 
                       placeholder="eg: 123" required>
            </div>

            <!-- Campo Modalità di Storage -->
            <div>
                <label class="block text-xs font-mono text-slate-300 mb-2">Strategia di Salvataggio per i File WSDL / Tracciati</label>
                <select name="pkg_storage_mode" class="w-full bg-slate-900 border border-slate-600 p-3 rounded text-sm text-white focus:border-indigo-500 focus:outline-none">
                    <option value="hybrid" <?php echo $currentStorage === 'hybrid' ? 'selected' : ''; ?>>Modalità Ibrida (Salva sia su Database che come file fisico)</option>
                    <option value="database" <?php echo $currentStorage === 'database' ? 'selected' : ''; ?>>Solo Database (Mantiene il file XML puro isolato a DB)</option>
                </select>
            </div>

            <!-- Pulsante di Invio -->
            <div class="pt-2">
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-mono text-xs font-bold py-3 px-4 rounded transition cursor-pointer">
                    SALVA E CONFERMA STEP 1
                </button>
            </div>
        </form>

    </div>

</body>
</html>

--- STEP 2
<?php
declare(strict_types=1);

// 1. Connessione al database
$pdo = new \PDO('mysql:host=localhost;dbname=openfiber_db', 'user', 'password', [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
]);

$messaggio = "";

// 2. Se l'utente invia il form, salviamo le impostazioni del Client
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ambiente = $_POST['client_env'] ?? 'test';
    $endpointUrl = $_POST['client_endpoint'] ?? '';
    $timeout = (int)($_POST['client_timeout'] ?? 30);

    // Validazione base dell'URL inserito
    if (!filter_var($endpointUrl, FILTER_VALIDATE_URL)) {
        $messaggio = "❌ Errore: L'URL inserito non è valido. Controlla il formato (es. https://...)";
    } else {
        // Aggiorniamo o inseriamo il record con ID fisso = 1 per le impostazioni attive del client
        $stmt = $pdo->prepare("
            INSERT INTO of_soap_client_config (id, environment, endpoint_url, timeout)
            VALUES (1, ?, ?, ?)
            ON DUPLICATE KEY UPDATE environment = ?, endpoint_url = ?, timeout = ?
        ");
        
        $stmt->execute([
            $ambiente, $endpointUrl, $timeout,
            $ambiente, $endpointUrl, $timeout
        ]);

        $messaggio = "✅ Configurazione SOAP Client (Step 2) salvata con successo a database!";
    }
}

// 3. Recuperiamo i dati correnti per pre-popolare i campi dell'interfaccia grafico
$stmtConfig = $pdo->query("SELECT * FROM of_soap_client_config WHERE id = 1");
$currentConfig = $stmtConfig->fetch(\PDO::FETCH_ASSOC) ?: [];

$currentEnv = $currentConfig['environment'] ?? 'test';
$currentEndpoint = $currentConfig['endpoint_url'] ?? '';
$currentTimeout = $currentConfig['timeout'] ?? 30;
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Open Fiber Package - Step 2</title>
    <script src="https://jsdelivr.net"></script>
</head>
<body class="bg-slate-900 text-slate-100 p-8">

    <div class="max-w-2xl mx-auto bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-lg">
        
        <!-- Intestazione dello Step -->
        <div class="border-b border-slate-700 pb-4 mb-6">
            <span class="text-xs font-mono text-indigo-400 font-bold uppercase tracking-wider">Configurazione Guidata</span>
            <h2 class="text-xl font-bold text-white mt-1">Step 2: Configurazione Endpoints SOAP Client</h2>
            <p class="text-xs text-slate-400 mt-1">Definisci l'indirizzo di rete del gateway Open Fiber e i tempi massimi di attesa di risposta della transazione.</p>
        </div>

        <!-- Notifiche di feedback -->
        <?php if (!empty($messaggio)): ?>
            <div class="mb-4 p-3 rounded text-xs font-mono bg-slate-900 border border-slate-700 text-slate-300">
                <?php echo $messaggio; ?>
            </div>
        <?php endif; ?>

        <!-- Form dello Step 2 -->
        <form action="" method="POST" class="space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Selezione Ambiente -->
                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-2">Ambiente Open Fiber Gateway</label>
                    <select name="client_env" class="w-full bg-slate-900 border border-slate-600 p-3 rounded text-sm text-white focus:border-indigo-500 focus:outline-none">
                        <option value="test" <?php echo $currentEnv === 'test' ? 'selected' : ''; ?>>Test / Interoperabilità / Collaudo</option>
                        <option value="production" <?php echo $currentEnv === 'production' ? 'selected' : ''; ?>>Produzione (Rete Reale)</option>
                    </select>
                </div>

                <!-- Campo Timeout Connessione -->
                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-2">Timeout di Connessione (Secondi)</label>
                    <input type="number" name="client_timeout" min="5" max="120" value="<?php echo htmlspecialchars((string)$currentTimeout); ?>" 
                           class="w-full bg-slate-900 border border-slate-600 p-3 rounded font-mono text-white focus:border-indigo-500 focus:outline-none" required>
                </div>
            </div>

            <!-- Campo URL dell'Endpoint -->
            <div>
                <label class="block text-xs font-mono text-slate-300 mb-2">URL Base Endpoint Web Service Open Fiber</label>
                <input type="url" name="client_endpoint" value="<?php echo htmlspecialchars($currentEndpoint); ?>" 
                       class="w-full bg-slate-900 border border-slate-600 p-3 rounded font-mono text-sm text-white focus:border-indigo-500 focus:outline-none" 
                       placeholder="https://openfiber.it" required>
                <p class="text-[10px] text-slate-400 mt-1">Questo indirizzo verrà usato come base dinamica combinata con i nomi dei servizi richiesti.</p>
            </div>

            <!-- Pulsante di Invio -->
            <div class="pt-2">
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-mono text-xs font-bold py-3 px-4 rounded transition cursor-pointer">
                    SALVA E CONFERMA STEP 2
                </button>
            </div>
        </form>

    </div>

</body>
</html>


--- step 3
<?php
declare(strict_types=1);

// 1. Connessione al database
$pdo = new \PDO('mysql:host=localhost;dbname=openfiber_db', 'user', 'password', [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
]);

$messaggio = "";

// 2. Se l'utente invia il form, aggiorniamo i percorsi dei certificati
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $certPath = $_POST['client_cert_path'] ?? '';
    $keyPath = $_POST['client_key_path'] ?? '';
    $passphrase = $_POST['passphrase'] ?? '';

    // Validazione preventiva locale: controlla se i file inseriti esistono sul server
    if (!empty($certPath) && !file_exists($certPath)) {
        $messaggio = "⚠️ Attenzione: Il file del certificato client non è stato trovato sul percorso specificato.";
    } elseif (!empty($keyPath) && !file_exists($keyPath)) {
        $messaggio = "⚠️ Attenzione: Il file della chiave privata non è stato trovato sul percorso specificato.";
    } else {
        // Aggiorniamo il record esistente (ID = 1) con i percorsi dei certificati
        $stmt = $pdo->prepare("
            UPDATE of_soap_client_config 
            SET client_cert_path = ?, client_key_path = ?, passphrase = ?
            WHERE id = 1
        ");
        
        $stmt->execute([$certPath, $keyPath, $passphrase]);
        $messaggio = "✅ Certificati e chiavi crittografiche (Step 3) salvati correttamente nel database.";
    }
}

// 3. Recuperiamo i dati correnti per popolare i campi
$stmtConfig = $pdo->query("SELECT client_cert_path, client_key_path, passphrase FROM of_soap_client_config WHERE id = 1");
$currentConfig = $stmtConfig->fetch(\PDO::FETCH_ASSOC) ?: [];

$currentCert = $currentConfig['client_cert_path'] ?? '';
$currentKey = $currentConfig['client_key_path'] ?? '';
$currentPass = $currentConfig['passphrase'] ?? '';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Open Fiber Package - Step 3</title>
    <script src="https://jsdelivr.net"></script>
</head>
<body class="bg-slate-900 text-slate-100 p-8">

    <div class="max-w-2xl mx-auto bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-lg">
        
        <!-- Intestazione dello Step -->
        <div class="border-b border-slate-700 pb-4 mb-6">
            <span class="text-xs font-mono text-indigo-400 font-bold uppercase tracking-wider">Configurazione Guidata</span>
            <h2 class="text-xl font-bold text-white mt-1">Step 3: Certificati di Sicurezza e Autentica mTLS</h2>
            <p class="text-xs text-slate-400 mt-1">Inserisci i percorsi dei file fisici necessari per cifrare lo scambio dati e abilitare il canale SSL mutuo verso Open Fiber.</p>
        </div>

        <!-- Notifiche di feedback -->
        <?php if (!empty($messaggio)): ?>
            <div class="mb-4 p-3 rounded text-xs font-mono bg-slate-900 border border-slate-700 text-slate-300">
                <?php echo $messaggio; ?>
            </div>
        <?php endif; ?>

        <!-- Form dello Step 3 -->
        <form action="" method="POST" class="space-y-6">
            
            <!-- Campo Certificato Client -->
            <div>
                <label class="block text-xs font-mono text-slate-300 mb-2">Percorso Assoluto Certificato Client (.crt / .pem)</label>
                <input type="text" name="client_cert_path" value="<?php echo htmlspecialchars($currentCert); ?>" 
                       class="w-full bg-slate-900 border border-slate-600 p-3 rounded font-mono text-xs text-slate-300 focus:border-indigo-500 focus:outline-none" 
                       placeholder="es: /var/www/certs/openfiber_client.crt" required>
            </div>

            <!-- Campo Chiave Privata -->
            <div>
                <label class="block text-xs font-mono text-slate-300 mb-2">Percorso Assoluto Chiave Privata (.key)</label>
                <input type="text" name="client_key_path" value="<?php echo htmlspecialchars($currentKey); ?>" 
                       class="w-full bg-slate-900 border border-slate-600 p-3 rounded font-mono text-xs text-slate-300 focus:border-indigo-500 focus:outline-none" 
                       placeholder="es: /var/www/certs/openfiber_client.key" required>
            </div>

            <!-- Campo Passphrase (Opzionale) -->
            <div>
                <label class="block text-xs font-mono text-slate-300 mb-2">Passphrase della Chiave Privata (Lascia vuoto se non cifrata)</label>
                <input type="password" name="passphrase" value="<?php echo htmlspecialchars($currentPass); ?>" 
                       class="w-full bg-slate-900 border border-slate-600 p-3 rounded text-sm text-white focus:border-indigo-500 focus:outline-none" 
                       placeholder="••••••••">
            </div>

            <!-- Pulsante di Invio -->
            <div class="pt-2">
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-mono text-xs font-bold py-3 px-4 rounded transition cursor-pointer">
                    SALVA E CONFERMA STEP 3
                </button>
            </div>
        </form>

    </div>

</body>
</html>

--- step 4

<?php
declare(strict_types=1);

// 1. Connessione al database
$pdo = new \PDO('mysql:host=localhost;dbname=openfiber_db', 'user', 'password', [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
]);

$notifiche = [];

// 2. Se l'utente invia gli URL, avviamo il download e il salvataggio RAW
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wsdlUrlsInput = $_POST['wsdl_urls'] ?? '';
    
    // Dividiamo il testo inserito per riga o virgola per estrarre la lista di URL
    $urls = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $wsdlUrlsInput)));

    if (empty($urls)) {
        $notifiche[] = "❌ Errore: Inserisci almeno un URL WSDL valido.";
    } else {
        foreach ($urls as $url) {
            // Estrazione dinamica del nome del servizio (es. OLO_ActivationSetup_DIA) tramite espressione regolare
            if (preg_match('/\/Service\/([A-Za-z5-9_]+)/', $url, $matches)) {
                $serviceName = $matches[1];
            } else {
                $serviceName = 'UnknownService_' . uniqid();
            }

            try {
                // Inizializzazione cURL per scaricare l'XML nudo e crudo in modo sicuro
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Controllo HTTPS obbligatorio nelle comunicazioni con OF
                
                $rawContent = curl_exec($ch);
                
                if (curl_errno($ch)) {
                    throw new \Exception(curl_error($ch));
                }
                curl_close($ch);

                if (empty($rawContent)) {
                    throw new \Exception("Il contenuto del file WSDL restituito dal server remoto è vuoto.");
                }

                // Salvataggio a Database nel campo wsdl_raw nudo e crudo
                $stmt = $pdo->prepare("
                    INSERT INTO of_wsdl_storage (service_name, wsdl_url, wsdl_raw) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE wsdl_url = ?, wsdl_raw = ?
                ");
                $stmt->execute([$serviceName, $url, $rawContent, $url, $rawContent]);

                $notifiche[] = "✅ Servizio <strong>{$serviceName}</strong> scaricato e archiviato correttamente come RAW.";

            } catch (\Throwable $e) {
                $notifiche[] = "❌ Errore durante il download di [{$url}]: " . $e->getMessage();
            }
        }
    }
}

// 3. Recuperiamo l'elenco dei servizi già sincronizzati per mostrarli nel pannello
$stmtWsdls = $pdo->query("SELECT service_name, wsdl_url, updated_at FROM of_wsdl_storage ORDER BY service_name ASC");
$savedWsdls = $stmtWsdls->fetchAll(\PDO::FETCH_ASSOC) ?: [];
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Open Fiber Package - Step 4</title>
    <script src="https://jsdelivr.net"></script>
</head>
<body class="bg-slate-900 text-slate-100 p-8">

    <div class="max-w-3xl mx-auto bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-lg">
        
        <!-- Intestazione dello Step -->
        <div class="border-b border-slate-700 pb-4 mb-6">
            <span class="text-xs font-mono text-indigo-400 font-bold uppercase tracking-wider">Configurazione Guidata</span>
            <h2 class="text-xl font-bold text-white mt-1">Step 4: Downloader e Sincronizzazione WSDL RAW</h2>
            <p class="text-xs text-slate-400 mt-1">Incolla gli indirizzi dei Web Services di Open Fiber per estrarre gli schemi e salvarli nudi e crudi all'interno della base dati.</p>
        </div>

        <!-- Log dei risultati dell'elaborazione cURL -->
        <?php if (!empty($notifiche)): ?>
            <div class="mb-6 p-4 rounded text-xs font-mono bg-slate-900 border border-slate-700 text-slate-300 space-y-2">
                <?php foreach ($notifiche as $notifica): ?>
                    <div><?php echo $notifica; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Form dello Step 4 -->
        <form action="" method="POST" class="space-y-6">
            <div>
                <label class="block text-xs font-mono text-slate-300 mb-2">Incolla gli URL dei file WSDL (Uno per riga)</label>
                <textarea name="wsdl_urls" rows="5" 
                          class="w-full bg-slate-900 border border-slate-600 p-3 rounded font-mono text-xs text-slate-300 focus:border-indigo-500 focus:outline-none" 
                          placeholder="https://openfiber.it" required></textarea>
                <p class="text-[10px] text-slate-400 mt-1">Il sistema analizzerà autonomamente l'URL per estrarre la firma del servizio associato.</p>
            </div>

            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-mono text-xs font-bold py-3 px-4 rounded transition cursor-pointer">
                DOWNLOAD E SINCRONIZZAZIONE REGISTRI WSDL
            </button>
        </form>

        <!-- Sezione Registro: Elenco elementi presenti a DB -->
        <div class="mt-8 border-t border-slate-700 pt-6">
            <h3 class="text-sm font-bold font-mono tracking-wide text-white uppercase mb-4">📦 Tracciati WSDL Attivi in Memoria (<?php echo count($savedWsdls); ?>)</h3>
            
            <?php if (empty($savedWsdls)): ?>
                <p class="text-xs text-slate-500 italic font-mono">Nessuno schema XML è stato ancora memorizzato nel database.</p>
            <?php else: ?>
                <div class="space-y-3 max-h-60 overflow-y-auto pr-2">
                    <?php foreach ($savedWsdls as $wsdl): ?>
                        <div class="p-3 bg-slate-900 border border-slate-700 rounded-lg flex justify-between items-center text-xs">
                            <div class="truncate max-w-xl">
                                <span class="font-mono font-bold text-indigo-400 block"><?php echo htmlspecialchars($wsdl['service_name']); ?></span>
                                <span class="text-[10px] text-slate-400 font-mono block truncate mt-0.5"><?php echo htmlspecialchars($wsdl['wsdl_url']); ?></span>
                            </div>
                            <div class="text-right shrink-0 ml-4">
                                <span class="bg-green-950 text-green-400 border border-green-800 text-[10px] font-mono px-2 py-0.5 rounded font-bold">WSDL_RAW_OK</span>
                                <span class="text-[9px] text-slate-500 font-mono block mt-1">Aggiornato: <?php echo date('d/m/Y H:i', strtotime($wsdl['updated_at'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>


--- step 5

<?php
declare(strict_types=1);

// 1. Connessione al database
$pdo = new \PDO('mysql:host=localhost;dbname=openfiber_db', 'user', 'password', [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
]);

$messaggio = "";

// 2. Se l'utente invia il form, aggiorniamo i parametri del SOAP Server
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $endpointLocale = $_POST['server_endpoint'] ?? '';
    $ipAbilitati = $_POST['server_ips'] ?? '';
    $statoAttivo = isset($_POST['server_active']) ? 1 : 0;

    if (!filter_var($endpointLocale, FILTER_VALIDATE_URL)) {
        $messaggio = "❌ Errore: L'URL di Callback locale inserito non è valido.";
    } else {
        // Aggiorniamo o inseriamo il record con ID fisso = 1 per le notifiche in ingresso
        $stmt = $pdo->prepare("
            INSERT INTO of_soap_server_config (id, service_name, listening_endpoint, allowed_openfiber_ips, is_active)
            VALUES (1, 'OF_NOTIFICATIONS', ?, ?, ?)
            ON DUPLICATE KEY UPDATE listening_endpoint = ?, allowed_openfiber_ips = ?, is_active = ?
        ");
        
        $stmt->execute([
            $endpointLocale, $ipAbilitati, $statoAttivo,
            $endpointLocale, $ipAbilitati, $statoAttivo
        ]);

        $messaggio = "✅ Configurazione SOAP Server (Step 5) aggiornata con successo a database.";
    }
}

// 3. Recuperiamo la configurazione corrente del server per pre-popolare l'interfaccia
$stmtServer = $pdo->query("SELECT * FROM of_soap_server_config WHERE id = 1");
$currentServer = $stmtServer->fetch(\PDO::FETCH_ASSOC) ?: [];

$currentEndpoint = $currentServer['listening_endpoint'] ?? '';
$currentIps = $currentServer['allowed_openfiber_ips'] ?? '';
$currentStatus = $currentServer['is_active'] ?? 1;
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Open Fiber Package - Step 5</title>
    <script src="https://jsdelivr.net"></script>
</head>
<body class="bg-slate-900 text-slate-100 p-8">

    <div class="max-w-2xl mx-auto bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-lg">
        
        <!-- Intestazione dello Step -->
        <div class="border-b border-slate-700 pb-4 mb-6">
            <span class="text-xs font-mono text-indigo-400 font-bold uppercase tracking-wider">Configurazione Guidata</span>
            <h2 class="text-xl font-bold text-white mt-1">Step 5: Configurazione SOAP Server & IP Open Fiber</h2>
            <p class="text-xs text-slate-400 mt-1">Imposta le coordinate di ricezione del tuo server per accettare gli aggiornamenti asincroni e proteggi l'endpoint tramite Whitelist.</p>
        </div>

        <!-- Notifiche di feedback -->
        <?php if (!empty($messaggio)): ?>
            <div class="mb-4 p-3 rounded text-xs font-mono bg-slate-900 border border-slate-700 text-slate-300">
                <?php echo $messaggio; ?>
            </div>
        <?php endif; ?>

        <!-- Form dello Step 5 -->
        <form action="" method="POST" class="space-y-6">
            
            <!-- Campo URL di Callback -->
            <div>
                <label class="block text-xs font-mono text-slate-300 mb-2">URL Locale di Ascolto (Endpoint Pubblico di Callback)</label>
                <input type="url" name="server_endpoint" value="<?php echo htmlspecialchars($currentEndpoint); ?>" 
                       class="w-full bg-slate-900 border border-slate-600 p-3 rounded font-mono text-sm text-white focus:border-indigo-500 focus:outline-none" 
                       placeholder="https://mio-sistema-olo.it" required>
                <p class="text-[10px] text-slate-400 mt-1">Questo è l'indirizzo esatto che i sistemi di Open Fiber contatteranno per inviarti le notifiche.</p>
            </div>

            <!-- Campo Whitelist IP -->
            <div>
                <label class="block text-xs font-mono text-slate-300 mb-2">Indirizzi IP Autorizzati di Open Fiber (Separati da virgola)</label>
                <textarea name="server_ips" rows="3" 
                          class="w-full bg-slate-900 border border-slate-600 p-3 rounded font-mono text-xs text-slate-300 focus:border-indigo-500 focus:outline-none" 
                          placeholder="185.212.x.x, 217.14.x.x" required><?php echo htmlspecialchars($currentIps); ?></textarea>
                <p class="text-[10px] text-slate-400 mt-1">Solo le connessioni provenienti da questi IP specifici avranno accesso al modulo di decodifica SOAP.</p>
            </div>

            <!-- Toggle Stato di Attivazione -->
            <div class="flex items-center gap-3 bg-slate-900/50 p-3 rounded-lg border border-slate-700/50">
                <input type="checkbox" id="server_active" name="server_active" value="1" <?php echo (int)$currentStatus === 1 ? 'checked' : ''; ?> 
                       class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                <label for="server_active" class="text-xs font-medium text-slate-300 select-none cursor-pointer">
                    Abilita ricezione traffico e controllo IP in tempo reale su questo endpoint
                </label>
            </div>

            <!-- Pulsante di Invio -->
            <div class="pt-2">
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-mono text-xs font-bold py-3 px-4 rounded transition cursor-pointer">
                    SALVA E CONFERMA STEP 5
                </button>
            </div>
        </form>

    </div>

</body>
</html>


--- step 6
<?php
declare(strict_types=1);

// 1. Connessione al database per recuperare il codice operatore e verificare la presenza del WSDL
$pdo = new \PDO('mysql:host=localhost;dbname=openfiber_db', 'user', 'password', [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
]);

// Recuperiamo il codice operatore salvato nello Step 1
$stmtCode = $pdo->query("SELECT config_value FROM of_package_config WHERE config_key = 'olo_operator_code'");
$operatorCode = $stmtCode->fetchColumn() ?: 'N/D';

// Verifichiamo se il WSDL di questo servizio è stato registrato a database nello Step 4
$stmtWsdl = $pdo->prepare("SELECT id FROM of_wsdl_storage WHERE service_name = 'OLO_ActivationSetup_DIA'");
$stmtWsdl->execute();
$isWsdlPresent = (bool)$stmtWsdl->fetchColumn();

$simulazionePayload = null;

// 2. Se l'utente richiede una simulazione, generiamo la struttura dati "al volo" simulando il DTO nativo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_simulate'])) {
    $ordineOlo = $_POST['sim_ordine_olo'] ?? '123_ORD999';
    $cognome = $_POST['sim_cognome'] ?? 'Rossi S.r.l.';
    $building = $_POST['sim_building'] ?? '030120_VIA_ROMA_10';
    $profilo = $_POST['sim_profilo'] ?? 'DIA_1GB_100MB';

    // Applichiamo la convalida del Property Hook di PHP 8.4 prima di generare l'output
    if (strlen($ordineOlo) > 18) {
        $error = "❌ Errore di validazione: Il CODICE_ORDINE_OLO supera i 18 caratteri massimi.";
    } elseif (preg_match('/[\x5C\x2F\x2A\x3F\x3C\x3E\x7C\x24]/', $ordineOlo)) {
        $error = "❌ Errore di validazione: Il CODICE_ORDINE_OLO contiene caratteri speciali vietati (\\, /, *, ?, <, >, |, $).";
    } else {
        // Simulazione dell'array generato dal DTO Dinamico in memoria
        $simulazionePayload = [
            'CODICE_OPERATORE' => $operatorCode,
            'CODICE_ORDINE_OLO' => $ordineOlo,
            'DATA_NOTIFICA' => date('Y-m-d\TH:i:sP'), // Formato corretto ISO 8601
            'ID_NOTIFICA' => 'NOT-' . uppercase(uniqid()),
            'COGNOME_CLIENTE' => $cognome,
            'RECAPITO_TELEFONICO_CLIENTE_1' => $_POST['sim_telefono'] ?? '021234567',
            'ID_BUILDING' => $building,
            'IDENTIFICATIVO_DEL_POP' => $_POST['sim_pop'] ?? 'POP_MI_1',
            'PROFILO' => $profilo,
            'DATA_PREVISTA_ATTIVAZIONE' => date('Y-m-d', strtotime('+14 days')) // Formato corretto YYYY-MM-DD
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Open Fiber Package - Step 6</title>
    <script src="https://jsdelivr.net"></script>
</head>
<body class="bg-slate-900 text-slate-100 p-8">

    <div class="max-w-4xl mx-auto bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-lg">
        
        <!-- Intestazione dello Step -->
        <div class="border-b border-slate-700 pb-4 mb-6 flex justify-between items-start">
            <div>
                <span class="text-xs font-mono text-indigo-400 font-bold uppercase tracking-wider">Ispezione Tracciati In Uscita</span>
                <h2 class="text-xl font-bold text-white mt-1">Step 6: Struttura OLO_ActivationSetup_DIA</h2>
                <p class="text-xs text-slate-400 mt-1">Verifica la conformità strutturale dei campi obbligatori per l'invio delle richieste di nuova attivazione ad Open Fiber.</p>
            </div>
            <div class="text-right shrink-0">
                <?php if ($isWsdlPresent): ?>
                    <span class="bg-green-950 text-green-400 border border-green-800 text-[10px] font-mono px-2 py-1 rounded font-bold">WSDL PRONTO</span>
                <?php else: ?>
                    <span class="bg-rose-950 text-rose-400 border border-rose-800 text-[10px] font-mono px-2 py-1 rounded font-bold">WSDL MANCANTE (STEP 4)</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabella Tecnica dei Vincoli di Specifica -->
        <div class="mb-6">
            <h3 class="text-xs font-mono text-slate-300 uppercase tracking-wider mb-2">📌 Specifiche dei Campi Chiave (Release 2.0)</h3>
            <div class="bg-slate-900 p-4 rounded-lg border border-slate-700 font-mono text-[11px] text-slate-400 space-y-1.5">
                <div>• <strong class="text-slate-200">CODICE_ORDINE_OLO</strong>: Lunghezza max 18 caratteri. Caratteri speciali <code class="text-rose-400">\ / * ? < > | $</code> non ammessi.</div>
                <div>• <strong class="text-slate-200">ID_BUILDING</strong>: Codice ISTAT composto da: Regione + Provincia + Comune + Via + Civico (Lunghezza max 150).</div>
                <div>• <strong class="text-slate-200">DATA_PREVISTA_ATTIVAZIONE</strong>: Deve rispettare il formato standard <code class="text-indigo-400">YYYY-MM-DD</code>.</div>
            </div>
        </div>

        <!-- Form di simulazione generazione DTO al volo -->
        <div class="bg-slate-900/40 p-4 rounded-xl border border-slate-700/60 mb-6">
            <h3 class="text-sm font-bold font-mono text-white mb-4">🧪 Simulatore di Generazione Payload</h3>
            
            <?php if (isset($error)): ?>
                <div class="mb-4 p-3 bg-rose-950/60 border border-rose-800 text-rose-300 rounded text-xs font-mono"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <input type="hidden" name="action_simulate" value="1">
                
                <div>
                    <label class="block text-slate-400 mb-1 font-mono">CODICE_ORDINE_OLO</label>
                    <input type="text" name="sim_ordine_olo" value="123_ORD2026" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono">
                </div>
                <div>
                    <label class="block text-slate-400 mb-1 font-mono">COGNOME_CLIENTE / RAGIONE SOCIALE</label>
                    <input type="text" name="sim_cognome" value="Rossi S.r.l." class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white">
                </div>
                <div>
                    <label class="block text-slate-400 mb-1 font-mono">ID_BUILDING (Codifica ISTAT Civico)</label>
                    <input type="text" name="sim_building" value="030120_VIA_ROMA_10" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono">
                </div>
                <div>
                    <label class="block text-slate-400 mb-1 font-mono">PROFILO DI ACCESSO RICHIESTO</label>
                    <input type="text" name="sim_profilo" value="DIA_1GB_100MB" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono">
                </div>
                
                <div class="md:col-span-2 pt-2">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-mono font-bold py-2 px-4 rounded transition cursor-pointer">
                        GENERA STRUTTURA PAYLOAD SOAP
                    </button>
                </div>
            </form>
        </div>

        <!-- Output Visualizzazione Stringa Payload Formattata -->
        <?php if ($simulazionePayload): ?>
            <div class="mt-4">
                <h4 class="text-xs font-mono text-emerald-400 font-bold uppercase tracking-wider mb-2">📋 Dump Dati Esportato dal DTO Dinamico (Pronto per invio SOAP)</h4>
                <pre class="bg-slate-900 p-4 rounded-lg border border-slate-700 font-mono text-[11px] text-emerald-300 overflow-x-auto"><?php echo htmlspecialchars(print_r($simulazionePayload, true)); ?></pre>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>

--- step 7

<?php
declare(strict_types=1);

// 1. Connessione al database per recuperare il codice operatore e controllare lo storage
$pdo = new \PDO('mysql:host=localhost;dbname=openfiber_db', 'user', 'password', [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
]);

// Recuperiamo il codice operatore salvato nello Step 1
$stmtCode = $pdo->query("SELECT config_value FROM of_package_config WHERE config_key = 'olo_operator_code'");
$operatorCode = $stmtCode->fetchColumn() ?: 'N/D';

// Verifichiamo se il WSDL di questo servizio specifico è presente a database
$stmtWsdl = $pdo->prepare("SELECT id FROM of_wsdl_storage WHERE service_name = 'OLO_ChangeSetup_DIA'");
$stmtWsdl->execute();
$isWsdlPresent = (bool)$stmtWsdl->fetchColumn();

$simulazionePayload = null;
$error = null;

// 2. Se l'utente richiede una simulazione, verifichiamo la struttura dati del DTO di variazione
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_simulate'])) {
    $ordineOlo = $_POST['sim_ordine_olo'] ?? '';
    $idRisorsa = $_POST['sim_id_risorsa'] ?? '';
    $profilo = $_POST['sim_profilo'] ?? '';

    // Validazione stringente basata sulle specifiche del documento (Pagina 24)
    if (strlen($ordineOlo) > 18) {
        $error = "❌ Errore: Il CODICE_ORDINE_OLO supera i 18 caratteri massimi.";
    } elseif (preg_match('/[\x5C\x2F\x2A\x3F\x3C\x3E\x7C\x24]/', $ordineOlo)) {
        $error = "❌ Errore: Il CODICE_ORDINE_OLO contiene caratteri speciali non ammessi (\\, /, *, ?, <, >, |, $).";
    } elseif (empty($idRisorsa)) {
        $error = "❌ Errore: Il campo ID_RISORSA è obbligatorio per identificare la linea da variare.";
    } else {
        // Generazione del payload strutturato
        $simulazionePayload = [
            'CODICE_OPERATORE' => $operatorCode,
            'CODICE_ORDINE_OLO' => $ordineOlo,
            'DATA_NOTIFICA' => date('Y-m-d\TH:i:sP'), // ISO 8601 richiesto
            'ID_NOTIFICA' => 'NOT-CHG-' . strtoupper(uniqid()),
            'ID_RISORSA' => $idRisorsa,
            'PROFILO' => $profilo,
            'NOTE' => !empty($_POST['sim_note']) ? $_POST['sim_note'] : null
        ];
        
        // Filtriamo i campi nulli come richiesto dai principi transazionali (Pagina 11)
        $simulazionePayload = array_filter($simulazionePayload, fn($v) => $v !== null);
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Open Fiber Package - Step 7</title>
    <script src="https://jsdelivr.net"></script>
</head>
<body class="bg-slate-900 text-slate-100 p-8">

    <div class="max-w-4xl mx-auto bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-lg">
        
        <!-- Intestazione dello Step -->
        <div class="border-b border-slate-700 pb-4 mb-6 flex justify-between items-start">
            <div>
                <span class="text-xs font-mono text-indigo-400 font-bold uppercase tracking-wider">Ispezione Tracciati In Uscita</span>
                <h2 class="text-xl font-bold text-white mt-1">Step 7: Struttura OLO_ChangeSetup_DIA</h2>
                <p class="text-xs text-slate-400 mt-1">Ispeziona i vincoli per la richiesta di variazione logica e modifica profilo dei servizi attivi.</p>
            </div>
            <div class="text-right shrink-0">
                <?php if ($isWsdlPresent): ?>
                    <span class="bg-green-950 text-green-400 border border-green-800 text-[10px] font-mono px-2 py-1 rounded font-bold">WSDL PRONTO</span>
                <?php else: ?>
                    <span class="bg-rose-950 text-rose-400 border border-rose-800 text-[10px] font-mono px-2 py-1 rounded font-bold">WSDL MANCANTE (STEP 4)</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mappatura Tecnica dei Vincoli -->
        <div class="mb-6">
            <h3 class="text-xs font-mono text-slate-300 uppercase tracking-wider mb-2">📌 Vincoli Elemento di Modifica (Pagina 24)</h3>
            <div class="bg-slate-900 p-4 rounded-lg border border-slate-700 font-mono text-[11px] text-slate-400 space-y-1.5">
                <div>• <strong class="text-slate-200">ID_RISORSA</strong>: Identificativo del servizio (OBB, Lunghezza max 50). Determina la linea bersaglio su cui applicare l'upgrade o la variazione.</div>
                <div>• <strong class="text-slate-200">ATTRIBUTI OPZIONALI</strong>: Nel caso in cui tag come <code class="text-indigo-400">NOTE</code> o <code class="text-indigo-400">PROMOZIONE</code> non siano valorizzati, l'XML escluderà automaticamente il nodo o lo veicolerà come tag chiuso (Pagina 11).</div>
            </div>
        </div>

        <!-- Simulatore di Generazione DTO -->
        <div class="bg-slate-900/40 p-4 rounded-xl border border-slate-700/60 mb-6">
            <h3 class="text-sm font-bold font-mono text-white mb-4">🧪 Simulatore di Generazione Payload Variazione</h3>
            
            <?php if ($error): ?>
                <div class="mb-4 p-3 bg-rose-950/60 border border-rose-800 text-rose-300 rounded text-xs font-mono"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <input type="hidden" name="action_simulate" value="1">
                
                <div>
                    <label class="block text-slate-400 mb-1 font-mono">CODICE_ORDINE_OLO</label>
                    <input type="text" name="sim_ordine_olo" value="123_CHG_2026_01" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono">
                </div>
                <div>
                    <label class="block text-slate-400 mb-1 font-mono">ID_RISORSA (Identificativo Linea da Modificare)</label>
                    <input type="text" name="sim_id_risorsa" value="OF_DIA_9988221" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono" placeholder="es: OF_DIA_xxxxx">
                </div>
                <div>
                    <label class="block text-slate-400 mb-1 font-mono">NUOVO PROFILO DI ACCESSO</label>
                    <input type="text" name="sim_profilo" value="DIA_2GB_200MB" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono">
                </div>
                <div>
                    <label class="block text-slate-400 mb-1 font-mono">NOTE AGGIUNTIVE (Opzionale)</label>
                    <input type="text" name="sim_note" value="Richiesta upgrade banda minima garantita su richiesta del cliente." class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white">
                </div>
                
                <div class="md:col-span-2 pt-2">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-mono font-bold py-2 px-4 rounded transition cursor-pointer">
                        GENERA STRUTTURA VARIAZIONE (DTO)
                    </button>
                </div>
            </form>
        </div>

        <!-- Output JSON/Array del Payload Generato -->
        <?php if ($simulazionePayload): ?>
            <div class="mt-4">
                <h4 class="text-xs font-mono text-emerald-400 font-bold uppercase tracking-wider mb-2">📋 Dump Dati Esportato (Pronto per l'iniezione nel SoapClient)</h4>
                <pre class="bg-slate-900 p-4 rounded-lg border border-slate-700 font-mono text-[11px] text-emerald-300 overflow-x-auto"><?php echo htmlspecialchars(print_r($simulazionePayload, true)); ?></pre>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>

--- step 8

<?php
declare(strict_types=1);

// 1. Connessione al database per recuperare il codice operatore e controllare lo storage
$pdo = new \PDO('mysql:host=localhost;dbname=openfiber_db', 'user', 'password', [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
]);

// Recuperiamo il codice operatore salvato nello Step 1
$stmtCode = $pdo->query("SELECT config_value FROM of_package_config WHERE config_key = 'olo_operator_code'");
$operatorCode = $stmtCode->fetchColumn() ?: 'N/D';

// Verifichiamo se il WSDL del servizio è registrato a database
$stmtWsdl = $pdo->prepare("SELECT id FROM of_wsdl_storage WHERE service_name = 'OLO_StatusUpdate_DIA'");
$stmtWsdl->execute();
$isWsdlPresent = (bool)$stmtWsdl->fetchColumn();

$simulazionePayload = null;
$error = null;

// 2. Elaborazione della richiesta e validazione condizionale (PHP 8.4 nativo logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_simulate'])) {
    $ordineOlo = $_POST['sim_ordine_olo'] ?? '';
    $azione = $_POST['sim_azione'] ?? '0';
    $codiceMotivazione = $_POST['sim_codice_motivazione'] ?? '';
    $motivazione = $_POST['sim_motivazione'] ?? '';

    // Validazione dei vincoli condizionali basata sulle regole di pagina 25
    if (strlen($ordineOlo) > 18 || preg_match('/[\x5C\x2F\x2A\x3F\x3C\x3E\x7C\x24]/', $ordineOlo)) {
        $error = "❌ Errore: CODICE_ORDINE_OLO non valido o contenente caratteri speciali vietati.";
    } elseif ($azione === '1' && (empty($codiceMotivazione) || empty($motivazione))) {
        // Regola stringente: se annulli devi inserire le causali (Pagina 25)
        $error = "❌ Errore di Specifica: Se l'AZIONE è 'Richiesta annullamento' (1), i campi CODICE_MOTIVAZIONE e MOTIVAZIONE sono obbligatori.";
    } else {
        // Generazione del payload dinamicamente
        $payload = [
            'CODICE_OPERATORE' => $operatorCode,
            'CODICE_ORDINE_OLO' => $ordineOlo,
            'DATA_NOTIFICA' => date('Y-m-d\TH:i:sP'),
            'ID_NOTIFICA' => 'NOT-ST-' . strtoupper(uniqid()),
            'AZIONE' => $azione
        ];

        if ($azione === '1') {
            $payload['CODICE_MOTIVAZIONE'] = $codiceMotivazione;
            $payload['MOTIVAZIONE'] = $motivazione;
        } else {
            // Se desospendi (0), puoi allegare opzionalmente una nuova data di attivazione
            $payload['DATA_PREVISTA_ATTIVAZIONE'] = date('Y-m-d', strtotime('+7 days'));
        }

        $simulazionePayload = $payload;
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Open Fiber Package - Step 8</title>
    <script src="https://jsdelivr.net"></script>
</head>
<body class="bg-slate-900 text-slate-100 p-8">

    <div class="max-w-4xl mx-auto bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-lg">
        
        <!-- Intestazione dello Step -->
        <div class="border-b border-slate-700 pb-4 mb-6 flex justify-between items-start">
            <div>
                <span class="text-xs font-mono text-indigo-400 font-bold uppercase tracking-wider">Ispezione Tracciati In Uscita</span>
                <h2 class="text-xl font-bold text-white mt-1">Step 8: Struttura OLO_StatusUpdate_DIA</h2>
                <p class="text-xs text-slate-400 mt-1">Gestione delle regole di sblocco (Desospensione) o chiusura forzata (Richiesta Annullamento) dei Work Order attivi.</p>
            </div>
            <div class="text-right shrink-0">
                <?php if ($isWsdlPresent): ?>
                    <span class="bg-green-950 text-green-400 border border-green-800 text-[10px] font-mono px-2 py-1 rounded font-bold">WSDL PRONTO</span>
                <?php else: ?>
                    <span class="bg-rose-950 text-rose-400 border border-rose-800 text-[10px] font-mono px-2 py-1 rounded font-bold">WSDL MANCANTE (STEP 4)</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Specifica logica condizionale dello step -->
        <div class="mb-6">
            <h3 class="text-xs font-mono text-slate-300 uppercase tracking-wider mb-2">📌 Logica Condizionale di Controllo (Pagina 25)</h3>
            <div class="bg-slate-900 p-4 rounded-lg border border-slate-700 font-mono text-[11px] text-slate-400 space-y-1.5">
                <div>• <span class="text-slate-200">Se AZIONE = "0" (Desospensione)</span>: I campi causale non sono previsti. È possibile inviare invece una nuova <code class="text-indigo-400">DATA_PREVISTA_ATTIVAZIONE</code>.</div>
                <div>• <span class="text-slate-200">Se AZIONE = "1" (Annullamento)</span>: Diventano obbligatori i campi causale. Non sono ammessi o gestiti nodi temporali relativi ad appuntamenti.</div>
            </div>
        </div>

        <!-- Simulatore Dinamico -->
        <div class="bg-slate-900/40 p-4 rounded-xl border border-slate-700/60 mb-6">
            <h3 class="text-sm font-bold font-mono text-white mb-4">🧪 Simulatore di Generazione Payload Aggiornamento Stato</h3>
            
            <?php if ($error): ?>
                <div class="mb-4 p-3 bg-rose-950/60 border border-rose-800 text-rose-300 rounded text-xs font-mono"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <input type="hidden" name="action_simulate" value="1">
                
                <div>
                    <label class="block text-slate-400 mb-1 font-mono">CODICE_ORDINE_OLO</label>
                    <input type="text" name="sim_ordine_olo" value="123_ORD2026_A" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono">
                </div>
                <div>
                    <label class="block text-slate-400 mb-1 font-mono">AZIONE RICHIESTA</label>
                    <select id="sim_azione" name="sim_azione" onchange="toggleCausaliFields(this.value)" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono">
                        <option value="0">0 - Desospensione (Riattiva Ordine)</option>
                        <option value="1">1 - Richiesta Annullamento (Blocco Definitivo)</option>
                    </select>
                </div>
                
                <!-- Campi condizionali inseriti in un container per verifica visiva -->
                <div id="container_causali" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
                    <div>
                        <label class="block text-slate-400 mb-1 font-mono">CODICE_MOTIVAZIONE (Es: D12)</label>
                        <input type="text" name="sim_codice_motivazione" value="D12" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono">
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1 font-mono">MOTIVAZIONE</label>
                        <input type="text" name="sim_motivazione" value="Annullamento ordine commerciale su richiesta esplicita del cliente." class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white">
                    </div>
                </div>
                
                <div class="md:col-span-2 pt-2">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-mono font-bold py-2 px-4 rounded transition cursor-pointer">
                        GENERA STRUTTURA UPDATE (DTO)
                    </button>
                </div>
            </form>
        </div>

        <!-- Output Generato -->
        <?php if ($simulazionePayload): ?>
            <div class="mt-4">
                <h4 class="text-xs font-mono text-emerald-400 font-bold uppercase tracking-wider mb-2">📋 Dump Dati Generato (Conforme a Vincoli di Pagina 25)</h4>
                <pre class="bg-slate-900 p-4 rounded-lg border border-slate-700 font-mono text-[11px] text-emerald-300 overflow-x-auto"><?php echo htmlspecialchars(print_r($simulazionePayload, true)); ?></pre>
            </div>
        <?php endif; ?>

    </div>

    <script>
        // Semplice script per mostrare/nascondere i campi obbligatori condizionali nel simulatore grafico
        function toggleCausaliFields(val) {
            const container = document.getElementById('container_causali');
            if (val === '1') {
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }
        }
        // Eseguiamo il controllo iniziale al caricamento
        document.addEventListener('DOMContentLoaded', () => {
            toggleCausaliFields(document.getElementById('sim_azione').value);
        });
    </script>
</body>
</html>

--- step 9

<?php
declare(strict_types=1);

// 1. Connessione al database per recuperare il codice operatore e controllare lo storage
$pdo = new \PDO('mysql:host=localhost;dbname=openfiber_db', 'user', 'password', [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
]);

// Recuperiamo il codice operatore salvato nello Step 1
$stmtCode = $pdo->query("SELECT config_value FROM of_package_config WHERE config_key = 'olo_operator_code'");
$operatorCode = $stmtCode->fetchColumn() ?: 'N/D';

// Verifichiamo se il WSDL del servizio è registrato a database
$stmtWsdl = $pdo->prepare("SELECT id FROM of_wsdl_storage WHERE service_name = 'OLO_Reschedule_DIA'");
$stmtWsdl->execute();
$isWsdlPresent = (bool)$stmtWsdl->fetchColumn();

$simulazionePayload = null;
$error = null;

// 2. Elaborazione della richiesta e validazione formale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_simulate'])) {
    $ordineOlo = $_POST['sim_ordine_olo'] ?? '';
    $nuovaData = $_POST['sim_nuova_data'] ?? '';
    $codiceMotivazione = $_POST['sim_codice_motivazione'] ?? '';
    $motivazione = $_POST['sim_motivazione'] ?? '';

    // Validazione dei vincoli basata sulle regole di pagina 27 e 40
    if (strlen($ordineOlo) > 18 || preg_match('/[\x5C\x2F\x2A\x3F\x3C\x3E\x7C\x24]/', $ordineOlo)) {
        $error = "❌ Errore: CODICE_ORDINE_OLO non valido o contenente caratteri speciali vietati.";
    } elseif (empty($nuovaData) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $nuovaData)) {
        $error = "❌ Errore: Inserire una DATA_PREVISTA_ATTIVAZIONE valida nel formato YYYY-MM-DD.";
    } elseif ($codiceMotivazione !== 'C08' && $codiceMotivazione !== 'D13') {
        $error = "❌ Errore di Specifica: Il codice motivazione per OLO_RDAC deve essere 'C08' (Motivi tecnici) o 'D13' (Motivi cliente) come da specifiche a Pagina 40.";
    } elseif (empty($motivazione)) {
        $error = "❌ Errore: Il campo MOTIVAZIONE è obbligatorio.";
    } else {
        // Generazione del payload strutturato
        $simulazionePayload = [
            'CODICE_OPERATORE' => $operatorCode,
            'CODICE_ORDINE_OLO' => $ordineOlo,
            'DATA_NOTIFICA' => date('Y-m-d\TH:i:sP'),
            'ID_NOTIFICA' => 'NOT-RES-' . strtoupper(uniqid()),
            'DATA_PREVISTA_ATTIVAZIONE' => $nuovaData,
            'CODICE_MOTIVAZIONE' => $codiceMotivazione,
            'MOTIVAZIONE' => $motivazione,
            'ORARIO_APPUNTAMENTO' => !empty($_POST['sim_orario']) ? $_POST['sim_orario'] : null
        ];

        // Rimuoviamo i campi nulli
        $simulazionePayload = array_filter($simulazionePayload, fn($v) => $v !== null);
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Open Fiber Package - Step 9</title>
    <script src="https://jsdelivr.net"></script>
</head>
<body class="bg-slate-900 text-slate-100 p-8">

    <div class="max-w-4xl mx-auto bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-lg">
        
        <!-- Intestazione dello Step -->
        <div class="border-b border-slate-700 pb-4 mb-6 flex justify-between items-start">
            <div>
                <span class="text-xs font-mono text-indigo-400 font-bold uppercase tracking-wider">Ispezione Tracciati In Uscita</span>
                <h2 class="text-xl font-bold text-white mt-1">Step 9: Struttura OLO_Reschedule_DIA</h2>
                <p class="text-xs text-slate-400 mt-1">Verifica dei vincoli per la rimodulazione dell'appuntamento (DAC) e delle causali obbligatorie lato operatore.</p>
            </div>
            <div class="text-right shrink-0">
                <?php if ($isWsdlPresent): ?>
                    <span class="bg-green-950 text-green-400 border border-green-800 text-[10px] font-mono px-2 py-1 rounded font-bold">WSDL PRONTO</span>
                <?php else: ?>
                    <span class="bg-rose-950 text-rose-400 border border-rose-800 text-[10px] font-mono px-2 py-1 rounded font-bold">WSDL MANCANTE (STEP 4)</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Specifica dei Vincoli dello Step -->
        <div class="mb-6">
            <h3 class="text-xs font-mono text-slate-300 uppercase tracking-wider mb-2">📌 Vincoli di Rischedulazione OLO_RDAC (Pagina 27 e 40)</h3>
            <div class="bg-slate-900 p-4 rounded-lg border border-slate-700 font-mono text-[11px] text-slate-400 space-y-1.5">
                <div>• <span class="text-slate-200">CODICE_MOTIVAZIONE</span>: Deve essere valorizzato obbligatoriamente con i codici della famiglia OLO_RDAC:</div>
                <div class="pl-4 text-indigo-400">- "C08": Motivi tecnici (es. ritardi fornitura apparati)</div>
                <div class="pl-4 text-indigo-400">- "D13": Motivi cliente (es. esigenze del cliente finale)</div>
            </div>
        </div>

        <!-- Simulatore Dinamico -->
        <div class="bg-slate-900/40 p-4 rounded-xl border border-slate-700/60 mb-6">
            <h3 class="text-sm font-bold font-mono text-white mb-4">🧪 Simulatore di Rimodulazione Appuntamento</h3>
            
            <?php if ($error): ?>
                <div class="mb-4 p-3 bg-rose-950/60 border border-rose-800 text-rose-300 rounded text-xs font-mono"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <input type="hidden" name="action_simulate" value="1">
                
                <div>
                    <label class="block text-slate-400 mb-1 font-mono">CODICE_ORDINE_OLO</label>
                    <input type="text" name="sim_ordine_olo" value="123_ORD2026_RES" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono">
                </div>
                <div>
                    <label class="block text-slate-400 mb-1 font-mono">NUOVA DATA RICHIESTA (YYYY-MM-DD)</label>
                    <input type="text" name="sim_nuova_data" value="<?php echo date('Y-m-d', strtotime('+20 days')); ?>" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono">
                </div>
                <div>
                    <label class="block text-slate-400 mb-1 font-mono">CODICE_MOTIVAZIONE</label>
                    <select name="sim_codice_motivazione" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono">
                        <option value="D13">D13 - Motivi cliente (Esigenze utente)</option>
                        <option value="C08">C08 - Motivi tecnici (Ritardo apparati)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-400 mb-1 font-mono">FASCIA ORARIA (Opzionale - hh:mm:ss)</label>
                    <input type="text" name="sim_orario" value="11:30:00" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-slate-400 mb-1 font-mono">MOTIVAZIONE ESTESA</label>
                    <input type="text" name="sim_motivazione" value="Il cliente richiede il posticipo per completamento lavori in casa." class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white">
                </div>
                
                <div class="md:col-span-2 pt-2">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-mono font-bold py-2 px-4 rounded transition cursor-pointer">
                        GENERA STRUTTURA RESCHEDULE (DTO)
                    </button>
                </div>
            </form>
        </div>

        <!-- Output Generato -->
        <?php if ($simulazionePayload): ?>
            <div class="mt-4">
                <h4 class="text-xs font-mono text-emerald-400 font-bold uppercase tracking-wider mb-2">📋 Dump Dati Generato per Rischedulazione</h4>
                <pre class="bg-slate-900 p-4 rounded-lg border border-slate-700 font-mono text-[11px] text-emerald-300 overflow-x-auto"><?php echo htmlspecialchars(print_r($simulazionePayload, true)); ?></pre>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>

--- step 10

<?php
declare(strict_types=1);

// 1. Connessione al database
$pdo = new \PDO('mysql:host=localhost;dbname=openfiber_db', 'user', 'password', [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
]);

// Verifichiamo se il WSDL di questo servizio è presente a database per il SOAP Server
$stmtWsdl = $pdo->prepare("SELECT id FROM of_wsdl_storage WHERE service_name = 'OF_StatusUpdate_DIA'");
$stmtWsdl->execute();
$isWsdlPresent = (bool)$stmtWsdl->fetchColumn();

$risultatoElaborazione = null;

// 2. Simulatore di ricezione notifica (Rappresenta la logica interna del SOAP Server)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_simulate'])) {
    $ordineOlo = $_POST['sim_ordine_olo'] ?? '';
    $ordineOf = $_POST['sim_ordine_of'] ?? 'OF-9988221';
    $statoRicevuto = $_POST['sim_stato_ordine'] ?? '0';
    $codiceMotivazione = $_POST['sim_codice_motivazione'] ?? '';
    $motivazioneText = $_POST['sim_motivazione'] ?? '';

    // Mappatura testuale degli stati di pagina 32 per il log visivo
    $mappaStati = [
        '0' => 'Acquisito',
        '1' => 'Acquisito KO',
        '2' => 'Accettato',
        '3' => 'Accettato KO',
        '4' => 'Sospeso',
        '5' => 'Annullato'
    ];

    $statoTesto = $mappaStati[$statoRicevuto] ?? 'Sconosciuto';

    // Simuliamo l'aggiornamento del database interno del CRM dell'OLO
    // In produzione questa logica viene eseguita dentro la closure passata al $server->handle()
    $risultatoElaborazione = [
        'EVENTO' => 'OF_StatusUpdate_DIA',
        'CODICE_ORDINE_OLO' => $ordineOlo,
        'CODICE_ORDINE_OF' => $ordineOf,
        'STATO_RILEVATO' => "{$statoRicevuto} - {$statoTesto}",
        'TIMESTAMP_RICEZIONE' => date('Y-m-d H:i:s'),
        'AZIONE_SISTEMA_OLO' => 'Nessuna azione richiesta (Stato intermedio)'
    ];

    // Se lo stato è un KO o una Sospensione, analizziamo la causale (Pagina 32)
    if (in_array($statoRicevuto, ['1', '3', '4'])) {
        $risultatoElaborazione['CAUSALE_INTERCETTATA'] = "[{$codiceMotivazione}] {$motivazioneText}";
        
        if ($statoRicevuto === '4') {
            $risultatoElaborazione['AZIONE_SISTEMA_OLO'] = 'Notifica assegnata al reparto On-Field / Provisioning per gestione sospensione.';
        } else {
            $risultatoElaborazione['AZIONE_SISTEMA_OLO'] = 'Work Order interrotto. Richiesta bonifica dati e reinvio via DTO.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Open Fiber Package - Step 10</title>
    <script src="https://jsdelivr.net"></script>
</head>
<body class="bg-slate-900 text-slate-100 p-8">

    <div class="max-w-4xl mx-auto bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-lg">
        
        <!-- Intestazione dello Step -->
        <div class="border-b border-slate-700 pb-4 mb-6 flex justify-between items-start">
            <div>
                <span class="text-xs font-mono text-indigo-400 font-bold uppercase tracking-wider">Ricezione Notifiche Asincrone</span>
                <h2 class="text-xl font-bold text-white mt-1">Step 10: Ricezione OF_StatusUpdate_DIA</h2>
                <p class="text-xs text-slate-400 mt-1">Configurazione del comportamento del SOAP Server all'arrivo dei cambi di stato dei Work Order inviati da Open Fiber.</p>
            </div>
            <div class="text-right shrink-0">
                <?php if ($isWsdlPresent): ?>
                    <span class="bg-green-950 text-green-400 border border-green-800 text-[10px] font-mono px-2 py-1 rounded font-bold">WSDL PRONTO</span>
                <?php else: ?>
                    <span class="bg-rose-950 text-rose-400 border border-rose-800 text-[10px] font-mono px-2 py-1 rounded font-bold">WSDL SERVER ASSENTE (STEP 4)</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Specifica degli Stati -->
        <div class="mb-6">
            <h3 class="text-xs font-mono text-slate-300 uppercase tracking-wider mb-2">📌 Registro Stati e Cambiamento Avanzamento (Pagina 32)</h3>
            <div class="bg-slate-900 p-4 rounded-lg border border-slate-700 font-mono text-[11px] text-slate-400 space-y-1.5">
                <div>• Il gateway del server OLO deve interpretare il campo <code class="text-indigo-400">STATO_ORDINE</code> e, in presenza di valori di KO o Sospensione, estrarre obbligatoriamente i nodi <code class="text-slate-200">CODICE_MOTIVAZIONE</code> e <code class="text-slate-200">MOTIVAZIONE</code> per allineare i sistemi gestionali interni.</div>
            </div>
        </div>

        <!-- Simulatore di Ricezione Notifica -->
        <div class="bg-slate-900/40 p-4 rounded-xl border border-slate-700/60 mb-6">
            <h3 class="text-sm font-bold font-mono text-white mb-4">🧪 Simulatore di Ricezione Chiamata SOAP da Open Fiber</h3>
            
            <form action="" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <input type="hidden" name="action_simulate" value="1">
                
                <div>
                    <label class="block text-slate-400 mb-1 font-mono">CODICE_ORDINE_OLO (Riferimento Interno)</label>
                    <input type="text" name="sim_ordine_olo" value="123_ORD2026_A" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono">
                </div>
                <div>
                    <label class="block text-slate-400 mb-1 font-mono">CODICE_ORDINE_OF (Assegnato da Open Fiber)</label>
                    <input type="text" name="sim_ordine_of" value="OF-4455221" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono">
                </div>
                <div>
                    <label class="block text-slate-400 mb-1 font-mono">STATO_ORDINE INVIATO DA OF</label>
                    <select id="sim_stato_ordine" name="sim_stato_ordine" onchange="toggleCausaliServer(this.value)" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono">
                        <option value="2">2 - Accettato (Validazione OK, in attesa di DAC)</option>
                        <option value="4">4 - Sospeso (Interruzione temporanea on-field/on-call)</option>
                        <option value="1">1 - Acquisito KO (Errore formale bloccante iniziale)</option>
                    </select>
                </div>
                
                <!-- Container condizionale per causali inviate da Open Fiber -->
                <div id="server_causali_block" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
                    <div>
                        <label class="block text-slate-400 mb-1 font-mono">CODICE_MOTIVAZIONE INVIATO (Es: C05 / D02)</label>
                        <input type="text" name="sim_codice_motivazione" value="C05" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono">
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1 font-mono">DESCRITTIVO MOTIVAZIONE DI OF</label>
                        <input type="text" name="sim_motivazione" value="Attesa permessi di transito o accesso da terze parti per verticale palazzina." class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white">
                    </div>
                </div>
                
                <div class="md:col-span-2 pt-2">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-mono font-bold py-2 px-4 rounded transition cursor-pointer">
                        SIMULA RICEZIONE NOTIFICA SERVER
                    </button>
                </div>
            </form>
        </div>

        <!-- Log di Elaborazione del Server -->
        <?php if ($risultatoElaborazione): ?>
            <div class="mt-4">
                <h4 class="text-xs font-mono text-indigo-400 font-bold uppercase tracking-wider mb-2">📥 Log di Logica Applicativa Eseguita dal SOAP Server</h4>
                <pre class="bg-slate-900 p-4 rounded-lg border border-slate-700 font-mono text-[11px] text-slate-300 overflow-x-auto"><?php echo htmlspecialchars(print_r($risultatoElaborazione, true)); ?></pre>
                <p class="text-[10px] text-slate-500 font-mono mt-1">Nota: Il server ha inviato automaticamente una risposta sincrona di ACK (Esito: 0) a Open Fiber come richiesto a Pagina 37.</p>
            </div>
        <?php endif; ?>

    </div>

    <script>
        function toggleCausaliServer(val) {
            const block = document.getElementById('server_causali_block');
            // Mostriamo il blocco delle causali solo se lo stato prevede un KO o una Sospensione (1 o 4)
            if (val === '1' || val === '4') {
                block.classList.remove('hidden');
            } else {
                block.classList.add('hidden');
            }
        }
        document.addEventListener('DOMContentLoaded', () => {
            toggleCausaliServer(document.getElementById('sim_stato_ordine').value);
        });
    </script>
</body>
</html>

--- step 11

<?php
declare(strict_types=1);

// 1. Connessione al database
$pdo = new \PDO('mysql:host=localhost;dbname=openfiber_db', 'user', 'password', [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
]);

// Verifichiamo se il WSDL del servizio è registrato a database
$stmtWsdl = $pdo->prepare("SELECT id FROM of_wsdl_storage WHERE service_name = 'OF_CompletionOrder_DIA'");
$stmtWsdl->execute();
$isWsdlPresent = (bool)$stmtWsdl->fetchColumn();

$risultatoElaborazione = null;

// 2. Simulatore di ricezione ordine espletato (Logica interna del SOAP Server)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_simulate'])) {
    $ordineOlo = $_POST['sim_ordine_olo'] ?? '';
    $statoOrdine = $_POST['sim_stato_ordine'] ?? '0';
    $metriFibra = $_POST['sim_metri_fo'] ?? '0';
    $costoExtra = $_POST['sim_costo_extra'] ?? '0.00';
    $serialRouter = $_POST['sim_serial'] ?? '';

    // Simulazione dell'elaborazione e della scrittura a DB dei dati tecnici finali stesi on-field
    $risultatoElaborazione = [
        'EVENTO' => 'OF_CompletionOrder_DIA',
        'CODICE_ORDINE_OLO' => $ordineOlo,
        'ESITO_FINALE_CONSEGNA' => $statoOrdine === '0' ? 'SUCCESSO (Linea Attiva)' : 'FALLITO (Impossibilità Tecnica)',
        'METRI_FIBRA_STESI_PRIVATO' => !empty($metriFibra) ? "{$metriFibra} mt" : 'Non dichiarati',
        'COSTI_ADDIZIONALI_INFRASTRUTTURA' => !empty($costoExtra) ? "€ {$costoExtra}" : 'Nessuno',
        'SERIALE_CPE_REGISTRATO' => !empty($serialRouter) ? $serialRouter : 'Nessun apparato erogato',
        'DATA_VARIAZIONE_SISTEMA' => date('Y-m-d H:i:s')
    ];

    // Aggiornamento fittizio nel CRM / DB OLO per attivare la fatturazione commerciale della linea
    if ($statoOrdine === '0') {
        $risultatoElaborazione['AZIONE_SISTEMA_OLO'] = 'Emissione automatica del trigger di attivazione profilo e avvio ciclo di fatturazione.';
    } else {
        $risultatoElaborazione['AZIONE_SISTEMA_OLO'] = 'Spostamento dell’ordine in stato KO Tecnico definitivo. Invio alert al reparto commerciale.';
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Open Fiber Package - Step 11</title>
    <script src="https://jsdelivr.net"></script>
</head>
<body class="bg-slate-900 text-slate-100 p-8">

    <div class="max-w-4xl mx-auto bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-lg">
        
        <!-- Intestazione dello Step -->
        <div class="border-b border-slate-700 pb-4 mb-6 flex justify-between items-start">
            <div>
                <span class="text-xs font-mono text-indigo-400 font-bold uppercase tracking-wider">Ricezione Notifiche Asincrone</span>
                <h2 class="text-xl font-bold text-white mt-1">Step 11: Ricezione OF_CompletionOrder_DIA</h2>
                <p class="text-xs text-slate-400 mt-1">Configurazione del comportamento del SOAP Server all'arrivo dei dati definitivi di chiusura cantiere ed espletamento linea.</p>
            </div>
            <div class="text-right shrink-0">
                <?php if ($isWsdlPresent): ?>
                    <span class="bg-green-950 text-green-400 border border-green-800 text-[10px] font-mono px-2 py-1 rounded font-bold">WSDL PRONTO</span>
                <?php else: ?>
                    <span class="bg-rose-950 text-rose-400 border border-rose-800 text-[10px] font-mono px-2 py-1 rounded font-bold">WSDL SERVER ASSENTE (STEP 4)</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Specifica dei Vincoli Fisici -->
        <div class="mb-6">
            <h3 class="text-xs font-mono text-slate-300 uppercase tracking-wider mb-2">📌 Consuntivazione Dati Fisici On-Field (Pagine 36-37)</h3>
            <div class="bg-slate-900 p-4 rounded-lg border border-slate-700 font-mono text-[11px] text-slate-400 space-y-1.5">
                <div>• <strong class="text-slate-200">COLLEGAMENTO_FO</strong>: Esprime in metri la distanza reale tra il confine della proprietà privata e la sede utente.</div>
                <div>• <strong class="text-slate-200">COSTO_EXTRA</strong>: Indica l'importo preventivato per opere civili extra. Importante per la riconciliazione dei costi di attivazione.</div>
            </div>
        </div>

        <!-- Simulatore di Ricezione Chiusura Cantiere -->
        <div class="bg-slate-900/40 p-4 rounded-xl border border-slate-700/60 mb-6">
            <h3 class="text-sm font-bold font-mono text-white mb-4">🧪 Simulatore di Chiusura Ordine da parte di OF</h3>
            
            <form action="" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <input type="hidden" name="action_simulate" value="1">
                
                <div>
                    <label class="block text-slate-400 mb-1 font-mono">CODICE_ORDINE_OLO (Riferimento Interno)</label>
                    <input type="text" name="sim_ordine_olo" value="123_ORD2026_A" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono">
                </div>
                <div>
                    <label class="block text-slate-400 mb-1 font-mono">ESITO ESPLETAMENTO (STATO_ORDINE)</label>
                    <select id="sim_stato_ordine" name="sim_stato_ordine" onchange="toggleEspletamentoFields(this.value)" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono">
                        <option value="0">0 - Espletato OK (Attivazione completata con successo)</option>
                        <option value="1">1 - Espletato KO (Impossibilità tecnica / Rifiuto definitivo)</option>
                    </select>
                </div>
                
                <!-- Container dati tecnici visibile solo in caso di espletamento positivo -->
                <div id="ok_dati_tecnici_block" class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-slate-400 mb-1 font-mono">Distanza Fibra Stesa (Metri)</label>
                        <input type="text" name="sim_metri_fo" value="35" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono" placeholder="es: 15">
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1 font-mono">Costi Addizionali (€)</label>
                        <input type="text" name="sim_costo_extra" value="0.00" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono" placeholder="es: 150.00">
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1 font-mono">Seriale Router/CPE Installato</label>
                        <input type="text" name="sim_serial" value="SN-OF-ZTE-99882" class="w-full bg-slate-900 border border-slate-600 p-2 rounded text-white font-mono" placeholder="es: SN-XXXXX">
                    </div>
                </div>
                
                <div class="md:col-span-2 pt-2">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-mono font-bold py-2 px-4 rounded transition cursor-pointer">
                        SIMULA RICEZIONE COMPLETAMENTO ORDINE
                    </button>
                </div>
            </form>
        </div>

        <!-- Log Risposta Server -->
        <?php if ($risultatoElaborazione): ?>
            <div class="mt-4">
                <h4 class="text-xs font-mono text-emerald-400 font-bold uppercase tracking-wider mb-2">📥 Log Dati Acquisiti ed Elaborati dal Server di Riconciliazione</h4>
                <pre class="bg-slate-900 p-4 rounded-lg border border-slate-700 font-mono text-[11px] text-slate-300 overflow-x-auto"><?php echo htmlspecialchars(print_r($risultatoElaborazione, true)); ?></pre>
            </div>
        <?php endif; ?>

    </div>

    <script>
        function toggleEspletamentoFields(val) {
            const block = document.getElementById('ok_dati_tecnici_block');
            // Se l'espletamento è KO (1), nascondiamo l'inserimento dei parametri fisici di successo della linea
            if (val === '0') {
                block.classList.remove('hidden');
            } else {
                block.classList.add('hidden');
            }
        }
        document.addEventListener('DOMContentLoaded', () => {
            toggleEspletamentoFields(document.getElementById('sim_stato_ordine').value);
        });
    </script>
</body>
</html>

--- step 12

<?php
declare(strict_types=1);

// 1. Connessione al database
$pdo = new \PDO('mysql:host=localhost;dbname=openfiber_db', 'user', 'password', [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
]);

$messaggio = "";

// 2. Elaborazione di azioni manuali di svuotamento o reset della coda per i test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_control'])) {
    $command = $_POST['action_control'];
    
    if ($command === 'reset_failed') {
        // Riporta i job falliti in stato pending per un nuovo tentativo manuale
        $pdo->query("UPDATE of_queue_retry SET status = 'pending', attempts = 0, next_attempt_at = NOW() WHERE status = 'failed'");
        $messaggio = "✅ Tutti i job precedentemente falliti sono stati reinseriti in coda (pending).";
    } elseif ($command === 'purge_all') {
        // Svuota l'intera tabella della coda
        $pdo->query("TRUNCATE TABLE of_queue_retry");
        $messaggio = "🗑️ Coda di retry svuotata completamente.";
    }
}

// 3. Raccolta delle metriche reali della coda per l'interfaccia grafica
$counts = ['pending' => 0, 'processing' => 0, 'completed' => 0, 'failed' => 0];
$stmtStats = $pdo->query("SELECT status, COUNT(*) as totale FROM of_queue_retry GROUP BY status");
while ($row = $stmtStats->fetch(\PDO::FETCH_ASSOC)) {
    $counts[$row['status']] = (int)$row['totale'];
}

// Recuperiamo gli ultimi 5 eventi inseriti in coda per il debug visivo
$stmtJobs = $pdo->query("SELECT id, service_name, codice_ordine_olo, id_notifica, retry_type, attempts, status, next_attempt_at FROM of_queue_retry ORDER BY id DESC LIMIT 5");
$latestJobs = $stmtJobs->fetchAll(\PDO::FETCH_ASSOC) ?: [];
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Open Fiber Package - Step 12</title>
    <script src="https://jsdelivr.net"></script>
</head>
<body class="bg-slate-900 text-slate-100 p-8">

    <div class="max-w-5xl mx-auto bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-lg">
        
        <!-- Intestazione dello Step -->
        <div class="border-b border-slate-700 pb-4 mb-6">
            <span class="text-xs font-mono text-emerald-400 font-bold uppercase tracking-wider">Automazioni e Coda di Background</span>
            <h2 class="text-xl font-bold text-white mt-1">Step 12: Monitoraggio Coda Retry Asincrona & Backoff</h2>
            <p class="text-xs text-slate-400 mt-1">Supervisiona lo stato dei tentativi di reinvio automatico e gestisci la manutenzione dei flussi asincroni di Delivery.</p>
        </div>

        <!-- Notifiche di feedback azioni -->
        <?php if (!empty($messaggio)): ?>
            <div class="mb-6 p-3 rounded text-xs font-mono bg-slate-900 border border-slate-700 text-slate-300">
                <?php echo $messaggio; ?>
            </div>
        <?php endif; ?>

        <!-- Griglia dei Contatori Statistici (Stato dei Job della Coda) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-slate-900 p-4 rounded-xl border border-slate-700 text-center">
                <span class="block text-xs font-mono text-slate-400 uppercase">In Attesa (Pending)</span>
                <span class="block text-2xl font-bold font-mono text-amber-400 mt-1"><?php echo $counts['pending']; ?></span>
            </div>
            <div class="bg-slate-900 p-4 rounded-xl border border-slate-700 text-center">
                <span class="block text-xs font-mono text-slate-400 uppercase">In Lavorazione</span>
                <span class="block text-2xl font-bold font-mono text-indigo-400 mt-1 animate-pulse"><?php echo $counts['processing']; ?></span>
            </div>
            <div class="bg-slate-900 p-4 rounded-xl border border-slate-700 text-center">
                <span class="block text-xs font-mono text-slate-400 uppercase">Completati OK</span>
                <span class="block text-2xl font-bold font-mono text-emerald-400 mt-1"><?php echo $counts['completed']; ?></span>
            </div>
            <div class="bg-slate-900 p-4 rounded-xl border border-slate-700 text-center">
                <span class="block text-xs font-mono text-slate-400 uppercase">Falliti Definitivi</span>
                <span class="block text-2xl font-bold font-mono text-rose-500 mt-1"><?php echo $counts['failed']; ?></span>
            </div>
        </div>

        <!-- Riepilogo Regole di Specifica Tecnica -->
        <div class="mb-6 bg-slate-900/60 p-4 rounded-xl border border-slate-700/60">
            <h3 class="text-xs font-mono text-slate-300 uppercase tracking-wider mb-2">📌 Allineamento Regole di Rigenerazione Identificativi (Pagina 12)</h3>
            <div class="font-mono text-[11px] text-slate-400 space-y-1">
                <div>• <span class="text-amber-400 font-bold">AUTOMATIC RETRY (Timeout / Rete)</span>: Il worker esegue il re-invio mantenendo l'ordine e l'ID_NOTIFICA identici all'originale.</div>
                <div>• <span class="text-rose-400 font-bold">NACK RETRY (KO Formale / Validazione)</span>: Il worker modifica l'ID_NOTIFICA generandone uno nuovo prima dell'inoltro.</div>
            </div>
        </div>

        <!-- Sezione Registro degli ultimi Job registrati -->
        <div class="mb-6">
            <h3 class="text-sm font-bold font-mono text-white mb-3">📋 Ultimi 5 Eventi Registrati nella Coda Asincrona</h3>
            <?php if (empty($latestJobs)): ?>
                <p class="text-xs text-slate-500 italic font-mono bg-slate-900 p-4 rounded-lg border border-slate-700">Nessuna transazione è attualmente presente o è passata dalla coda dei retry asincroni.</p>
            <?php else: ?>
                <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                    <?php foreach ($latestJobs as $job): ?>
                        <div class="p-3 bg-slate-900 rounded-lg border border-slate-700 text-xs font-mono flex flex-col md:flex-row md:items-center justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-200"><?php echo htmlspecialchars($job['service_name']); ?></span>
                                    <span class="text-[10px] px-1.5 py-0.2 rounded font-bold <?php echo $job['retry_type'] === 'AUTOMATIC' ? 'bg-amber-950 text-amber-400 border border-amber-800' : 'bg-rose-950 text-rose-400 border border-rose-800'; ?>">
                                        <?php echo $job['retry_type']; ?>
                                    </span>
                                </div>
                                <div class="text-[10px] text-slate-400 mt-1">
                                    Ordine: <?php echo htmlspecialchars($job['codice_ordine_olo']); ?> | Notifica: <?php echo htmlspecialchars($job['id_notifica']); ?>
                                </div>
                            </div>
                            <div class="text-left md:text-right shrink-0">
                                <div class="text-[10px]">Stato: 
                                    <span class="font-bold <?php echo $job['status'] === 'completed' ? 'text-emerald-400' : ($job['status'] === 'failed' ? 'text-rose-400' : 'text-amber-400'); ?>">
                                        <?php echo strtoupper($job['status']); ?>
                                    </span> 
                                    (Tentativi: <?php echo $job['attempts']; ?>)
                                </div>
                                <div class="text-[9px] text-slate-500 mt-0.5">Prossimo tentativo: <?php echo $job['next_attempt_at']; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pannello Azioni di Manutenzione Strumentale -->
        <div class="border-t border-slate-700 pt-6 mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex flex-wrap gap-2">
                <form action="" method="POST" onsubmit="return confirm('Vuoi davvero riaccodare i job falliti?');">
                    <input type="hidden" name="action_control" value="reset_failed">
                    <button type="submit" class="bg-slate-700 hover:bg-slate-600 text-slate-200 font-mono text-[11px] py-2 px-3 rounded transition cursor-pointer">
                        🔄 Riavvia Job Falliti
                    </button>
                </form>
                <form action="" method="POST" onsubmit="return confirm('Sei sicuro di voler svuotare interamente la coda?');">
                    <input type="hidden" name="action_control" value="purge_all">
                    <button type="submit" class="bg-rose-950/40 hover:bg-rose-950/80 text-rose-400 border border-rose-900 font-mono text-[11px] py-2 px-3 rounded transition cursor-pointer">
                        🗑️ Svuota Coda
                    </button>
                </form>
            </div>
            
            <span class="text-xs text-emerald-400 font-bold font-mono bg-emerald-950/40 border border-emerald-900 px-4 py-2 rounded-lg">
                🎉 INIZIALIZZAZIONE COMPLETA (12/12 STEP)
            </span>
        </div>

    </div>

</body>
</html>

