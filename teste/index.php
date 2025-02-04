<?php
// Configuração do banco de dados
$host = 'localhost';
$dbname = 'backup_certificados';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}

// Diretórios de origem e destino dos arquivos
$diretorio_origem = 'F:\\Certificado de Cursos';
$diretorio_destino = 'F:\\Certificado de Cursos\\teste';

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
        
        // Define o novo nome do arquivo
        $novo_nome = "BASE_LINK.csv";
        $novo_caminho = $diretorio_destino . DIRECTORY_SEPARATOR . $novo_nome;
        
        // Copia o arquivo para o diretório de destino (substituindo se já existir)
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

function converterParaUTF8($string) {
    return mb_convert_encoding($string, 'UTF-8', 'auto');
}


// Processamento do arquivo CSV e exibição na tela
$csv_caminho = $diretorio_destino . DIRECTORY_SEPARATOR . "BASE_LINK.csv";

if (file_exists($csv_caminho)) {
    if (($handle = fopen($csv_caminho, "r")) !== FALSE) {
        echo "<table border='1'>";
        echo "<tr><th>CHAVE_LOJA</th><th>NOME_LOJA</th><th>ONLYLINKS</th></tr>";

        // Ignorar o cabeçalho, se existir
        fgetcsv($handle, 1000, ",");

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $chave_loja = converterParaUTF8($data[0]);
            $nome_loja = converterParaUTF8($data[1]);
            $onlylinks = converterParaUTF8($data[2]);

            // Exibir os dados na tela em formato de tabela
            echo "<tr>";
            echo "<td>{$chave_loja}</td>";
            echo "<td>{$nome_loja}</td>";
            echo "<td>{$onlylinks}</td>";
            echo "</tr>";
        }
        echo "</table>";

        fclose($handle);
    } else {
        echo "Erro ao abrir o arquivo CSV.";
    }
} else {
    echo "Arquivo CSV não encontrado.";
}

?>
