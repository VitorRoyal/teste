<?php
// Configuração do banco de dados
$host = 'localhost';
$dbname = 'backup_certificados';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}

// Diretórios de origem e destino dos arquivos
$diretorio_origem = '';
$diretorio_destino = '';

// Função para buscar arquivos no diretório especificado (sem subdiretórios) 
function buscarArquivos($diretorio) {
    $arquivos = array();
    
    if (is_dir($diretorio)) {
        $itens = scandir($diretorio);
        foreach ($itens as $item) {
            $caminho_item = $diretorio . DIRECTORY_SEPARATOR . $item;
            if (is_file($caminho_item)) {
                $arquivos[$caminho_item] = filectime($caminho_item); // Usa a data de criação
            }
        }
    }
    return $arquivos;
}

// Busca os arquivos no diretório de origem
$arquivos = buscarArquivos($diretorio_origem);

if (!empty($arquivos)) {
    // Ordena os arquivos pela data de criação (mais recente primeiro)
    arsort($arquivos);
    reset($arquivos);
    $ultimo_arquivo = key($arquivos);
    $nome_arquivo = basename($ultimo_arquivo);
    
    // Verifica se o arquivo já está no banco de dados (pelo nome antigo) 
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM arquivos_backup WHERE nome_antigo = ?");
    $stmt->execute([$nome_arquivo]);
    $ja_existe = $stmt->fetchColumn();
    
    if (!$ja_existe) {
        // Verifica se o diretório de destino existe, se não, cria
        if (!is_dir($diretorio_destino)) {
            mkdir($diretorio_destino, 0777, true);
        }
        
        // Determina o próximo número do arquivo
        $contador = 1;
        do {
            $novo_nome = "arquivo " . $contador . "." . pathinfo($ultimo_arquivo, PATHINFO_EXTENSION);
            $novo_caminho = $diretorio_destino . DIRECTORY_SEPARATOR . $novo_nome;
            $contador++;
        } while (file_exists($novo_caminho));
        
        // Copia o arquivo para o diretório de destino com o novo nome
        if (copy($ultimo_arquivo, $novo_caminho)) {
            echo "Arquivo copiado com sucesso para: " . $novo_caminho . "<br>";
            
            // Insere no banco de dados com a data formatada no padrão BR
            $data_copia = date("Y-m-d");
            $stmt = $pdo->prepare("INSERT INTO arquivos_backup (nome_antigo, nome_novo, data_copia) VALUES (?, ?, ?)");
            $stmt->execute([$nome_arquivo, $novo_nome, $data_copia]);
        } else {
            echo "Erro ao copiar o arquivo.";
        }
    } else {
        echo "O arquivo já foi copiado anteriormente.";
    }
} else {
    echo "Nenhum arquivo encontrado no diretório.";
}
?>