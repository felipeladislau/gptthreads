<?php
/**
 * chat.php
 *
 * Sistema de chatbot utilizando threads para um assistant personalizado.
 *
 * Fluxo:
 * 1. Se não houver cookie "chat_thread", cria uma thread usando o endpoint de Threads,
 *    passando a mensagem do usuário como inicial.
 * 2. Se houver cookie, adiciona a nova mensagem do usuário via endpoint de Messages.
 * 3. Em seguida, chama o endpoint de Runs para executar a thread e aguarda sua conclusão.
 * 4. Após o run, utiliza o endpoint de List Messages para obter todas as mensagens da thread.
 * 5. Atualiza o histórico local (armazenado em um arquivo na pasta "threads", com nome igual ao thread id)
 *    com as mensagens obtidas e retorna o histórico atualizado.
 *
 * Documentações:
 * - Threads: https://platform.openai.com/docs/api-reference/threads
 * - Messages: https://platform.openai.com/docs/api-reference/messages
 * - Runs: https://platform.openai.com/docs/api-reference/runs
 * - List Messages: https://platform.openai.com/docs/api-reference/messages/listMessages
 */

// Exibe erros para debug (ajuste para produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define o header para retorno em JSON
header('Content-Type: application/json');

// ***********************
// CONFIGURAÇÕES GERAIS
// ***********************
$openai_api_key = 'YOUR_OPENAI_API_KEY'; // Substitua pela sua API Key da OpenAI
$assistant_id   = 'YOUR_ASSISTANT_ID'; // ID do seu assistant personalizado
$beta_header    = "OpenAI-Beta: assistants=v2"; // Cabeçalho obrigatório para a API de Assistants
$log_file       = __DIR__ . '/log.txt';

// ***********************
// FUNÇÕES AUXILIARES
// ***********************

/**
 * Registra mensagens de erro no arquivo log.txt.
 */
function log_error($message) {
    global $log_file;
    error_log("[" . date('Y-m-d H:i:s') . "] " . $message . "\n", 3, $log_file);
}

/**
 * Cria uma nova thread usando o endpoint de Threads.
 * Envia a mensagem inicial do usuário na propriedade "messages".
 *
 * @param string $assistant_id
 * @param string $user_message
 * @param string $openai_api_key
 * @param string $beta_header
 * @return string|false ID da thread ou false em caso de erro.
 */
function createThread($assistant_id, $user_message, $openai_api_key, $beta_header) {
    $url = "https://api.openai.com/v1/threads";
    $data = [
        // O parâmetro "assistant" não é enviado na criação da thread
        "messages"  => [
            [
                "role"    => "user",
                "content" => $user_message
            ]
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $openai_api_key,
        $beta_header
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
         $error_msg = curl_error($ch);
         log_error("cURL Error in createThread: " . $error_msg);
         curl_close($ch);
         return false;
    }
    curl_close($ch);
    $respData = json_decode($response, true);
    if (!$respData || !isset($respData['id'])) {
         log_error("Erro ao criar thread: " . $response);
         return false;
    }
    return $respData['id'];
}

/**
 * Adiciona uma mensagem à thread via endpoint de Messages.
 *
 * @param string $thread_id
 * @param string $role (user ou assistant)
 * @param string $content
 * @param string $openai_api_key
 * @param string $beta_header
 * @return array|false Resposta da API ou false em caso de erro.
 */
function addMessage($thread_id, $role, $content, $openai_api_key, $beta_header) {
    $url = "https://api.openai.com/v1/threads/" . $thread_id . "/messages";
    $data = [
        "role"    => $role,
        "content" => $content
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
         "Content-Type: application/json",
         "Authorization: Bearer " . $openai_api_key,
         $beta_header
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
         $error_msg = curl_error($ch);
         log_error("cURL Error in addMessage: " . $error_msg);
         curl_close($ch);
         return false;
    }
    curl_close($ch);
    $respData = json_decode($response, true);
    if (!$respData || !isset($respData['id'])) {
         log_error("Erro ao adicionar mensagem: " . $response);
         return false;
    }
    return $respData;
}

/**
 * Inicia um run para a thread (POST /v1/threads/{thread_id}/runs) e retorna o run_id.
 * Neste run, passamos o assistant_id conforme a documentação.
 *
 * @param string $thread_id
 * @param string $openai_api_key
 * @param string $beta_header
 * @return string|false run_id ou false em caso de erro.
 */
function startRun($thread_id, $openai_api_key, $beta_header) {
    $url = "https://api.openai.com/v1/threads/" . $thread_id . "/runs";
    $data = [
        "assistant_id" => $assistant_id_global = $GLOBALS['assistant_id'] // utiliza o assistant_id definido globalmente
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
         "Content-Type: application/json",
         "Authorization: Bearer " . $openai_api_key,
         $beta_header
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
         $error_msg = curl_error($ch);
         log_error("cURL Error in startRun: " . $error_msg);
         curl_close($ch);
         return false;
    }
    curl_close($ch);
    $respData = json_decode($response, true);
    if (!$respData || !isset($respData['id'])) {
         log_error("Erro ao iniciar run: " . $response);
         return false;
    }
    return $respData['id'];
}

/**
 * Obtém o status do run via GET /v1/threads/{thread_id}/runs/{run_id}.
 *
 * @param string $run_id
 * @param string $thread_id
 * @param string $openai_api_key
 * @param string $beta_header
 * @return array|false Dados do run ou false em caso de erro.
 */
function getRunStatus($run_id, $thread_id, $openai_api_key, $beta_header) {
    $url = "https://api.openai.com/v1/threads/" . $thread_id . "/runs/" . $run_id;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
         "Authorization: Bearer " . $openai_api_key,
         $beta_header
    ]);
    
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
         $error_msg = curl_error($ch);
         log_error("cURL Error in getRunStatus: " . $error_msg);
         curl_close($ch);
         return false;
    }
    curl_close($ch);
    $respData = json_decode($response, true);
    if (!$respData) {
         log_error("Erro ao obter status do run: " . $response);
         return false;
    }
    return $respData;
}

/**
 * Executa o run: inicia o run e faz polling até que esteja concluído.
 * Retorna true se concluído, ou false em caso de erro.
 *
 * @param string $thread_id
 * @param string $openai_api_key
 * @param string $beta_header
 * @return bool
 */
function runThread($thread_id, $openai_api_key, $beta_header) {
     $run_id = startRun($thread_id, $openai_api_key, $beta_header);
     if (!$run_id) {
         return false;
     }
     
     // Polling: aguarda até que o run seja concluído (máx. 10 tentativas)
     $max_attempts = 10;
     $attempt = 0;
     while ($attempt < $max_attempts) {
         sleep(1); // espera 1 segundo
         $statusData = getRunStatus($run_id, $thread_id, $openai_api_key, $beta_header);
         log_error("Run status: " . json_encode($statusData));
         if (!$statusData) {
             return false;
         }
         if (isset($statusData['status']) && $statusData['status'] === 'completed') {
             return true;
         } elseif (isset($statusData['status']) && $statusData['status'] === 'failed') {
             log_error("Run falhou: " . json_encode($statusData));
             return false;
         }
         $attempt++;
     }
     log_error("Run não concluiu após $max_attempts tentativas.");
     return false;
 }
 
 /**
  * Lista todas as mensagens da thread via o endpoint List Messages.
  * Retorna um array de mensagens ou false em caso de erro.
  * Documentação: https://platform.openai.com/docs/api-reference/messages/listMessages
  */
 function listMessages($thread_id, $openai_api_key, $beta_header) {
     $url = "https://api.openai.com/v1/threads/" . $thread_id . "/messages";
     
     $ch = curl_init($url);
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
     curl_setopt($ch, CURLOPT_HTTPGET, true);
     curl_setopt($ch, CURLOPT_HTTPHEADER, [
         "Authorization: Bearer " . $openai_api_key,
         $beta_header
     ]);
     
     $response = curl_exec($ch);
     if (curl_errno($ch)) {
         $error_msg = curl_error($ch);
         log_error("cURL Error in listMessages: " . $error_msg);
         curl_close($ch);
         return false;
     }
     curl_close($ch);
     $respData = json_decode($response, true);
     
     if (!$respData || !isset($respData['data'])) {
         log_error("Erro ao listar mensagens: " . $response);
         return false;
     }
     return $respData['data'];
 }
 
 /**
  * Retorna o caminho do arquivo de histórico da conversa.
  * O arquivo é armazenado na pasta "threads" e seu nome é o thread id.
  */
 function getConversationFilePath($thread_id) {
     $conv_dir = __DIR__ . '/threads';
     if (!file_exists($conv_dir)) {
         if (!mkdir($conv_dir, 0777, true)) {
             log_error("Erro ao criar diretório de threads: $conv_dir");
         }
     }
     return $conv_dir . '/' . $thread_id . '.json';
 }
 
 /**
  * Carrega o histórico da conversa a partir do arquivo.
  */
 function loadConversation($filePath, $thread_id) {
     if (file_exists($filePath)) {
         $data = file_get_contents($filePath);
         $conv_data = json_decode($data, true);
         if (!$conv_data) {
             log_error("Erro ao decodificar JSON do arquivo: $filePath");
             return ["thread" => $thread_id, "messages" => []];
         }
         return $conv_data;
     }
     return ["thread" => $thread_id, "messages" => []];
 }
 
 /**
  * Salva o histórico da conversa no arquivo.
  */
 function saveConversation($filePath, $conv_data) {
     $result = file_put_contents($filePath, json_encode($conv_data));
     if ($result === false) {
         log_error("Erro ao salvar a conversa no arquivo: $filePath");
         return false;
     }
     return true;
 }
 
 // ----------------------
 // PROCESSAMENTO PRINCIPAL
 // ----------------------
 
 // Para requisições GET, retorna o histórico da conversa
 if ($_SERVER['REQUEST_METHOD'] === 'GET') {
     if (!isset($_COOKIE['chat_thread'])) {
         echo json_encode(["thread" => "", "messages" => []]);
         exit;
     }
     $thread_id = $_COOKIE['chat_thread'];
     $conv_file = getConversationFilePath($thread_id);
     $conv_data = loadConversation($conv_file, $thread_id);

     // Inverte a ordem das mensagens
    $conv_data['messages'] = array_reverse($conv_data['messages']);

    // Retorna o conv_data formatado como JSON
    echo json_encode($conv_data);


     exit;
 }
 
 // Processamento de requisições POST: nova mensagem do usuário
 $user_message = isset($_POST['message']) ? trim($_POST['message']) : "";
 if ($user_message === "") {
     log_error("Mensagem vazia enviada pelo usuário.");
     echo json_encode(["error" => "Mensagem vazia"]);
     exit;
 }
 
 if (!isset($_COOKIE['chat_thread'])) {
     // Primeira interação: cria a thread com a mensagem do usuário
     $thread_id = createThread($assistant_id, $user_message, $openai_api_key, $beta_header);
     if (!$thread_id) {
         echo json_encode(["error" => "Erro ao criar thread"]);
         exit;
     }
     // Armazena o thread id em cookie por 30 dias
     setcookie('chat_thread', $thread_id, time() + 3600 * 24 * 30, "/");
 } else {
     $thread_id = $_COOKIE['chat_thread'];
     // Para interações seguintes, adiciona a nova mensagem do usuário à thread
     $msg_response = addMessage($thread_id, "user", $user_message, $openai_api_key, $beta_header);
     if (!$msg_response) {
         echo json_encode(["error" => "Erro ao adicionar mensagem"]);
         exit;
     }
 }
 
 // Atualiza o histórico local: carrega o arquivo de conversa
 $conv_file = getConversationFilePath($thread_id);
 $conv_data = loadConversation($conv_file, $thread_id);
 
 // Adiciona a mensagem do usuário ao histórico local
 $conv_data['messages'][] = [
     "role"    => "user",
     "content" => $user_message
 ];
 
 // Executa o run para processar a thread (apenas para disparar o processamento)
 $run_completed = runThread($thread_id, $openai_api_key, $beta_header);
 if (!$run_completed) {
     echo json_encode(["error" => "Erro ao executar a thread"]);
     exit;
 }
 
 // Agora, lista todas as mensagens da thread usando o endpoint List Messages
 $list_messages = listMessages($thread_id, $openai_api_key, $beta_header);
 if (!$list_messages || !is_array($list_messages)) {
     log_error("Erro ao listar mensagens: " . json_encode($list_messages));
     echo json_encode(["error" => "Erro ao listar mensagens"]);
     exit;
 }else{
     log_error("Listagem de mensagens: " . json_encode($list_messages));
 }
 
 // Atualiza o histórico local com as mensagens obtidas
 $conv_data['messages'] = [];
 foreach ($list_messages as $msg) {
     if (isset($msg['role']) && isset($msg['content'])) {
         $conv_data['messages'][] = [
             "role"    => $msg['role'],
             "content" => $msg['content']
         ];
     }
 }

 // Inverte a ordem das mensagens
 $conv_data['messages'] = array_reverse($conv_data['messages']);
 
 // Salva o histórico atualizado
 if (!saveConversation($conv_file, $conv_data)) {
     echo json_encode(["error" => "Erro ao salvar a conversa"]);
     exit;
 }
 
 // Retorna o histórico atualizado para o front-end
 echo json_encode($conv_data);
 exit;
?> 