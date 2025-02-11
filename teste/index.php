<?php
require 'vendor/autoload.php'; // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

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

// Processamento do arquivo XLSX e inserção no banco
$xlsx_caminho = 'F:\\Certificado de Cursos\\teste\\BASE_LINK.xlsx';

if (file_exists($xlsx_caminho)) {
    $spreadsheet = IOFactory::load($xlsx_caminho);
    $sheet = $spreadsheet->getActiveSheet();
    $dados = $sheet->toArray();

    array_shift($dados);

    $stmt = $pdo->prepare("INSERT INTO tabela_destino (CHAVE_LOJA, NOME_LOJA, ONLYLINKS) VALUES (?, ?, ?)");

    foreach ($dados as $linha) {
        $chave_loja = $linha[0] ?? null;
        $nome_loja = $linha[1] ?? null;
        $onlylinks = $linha[2] ?? null;

        if ($chave_loja && $nome_loja && $onlylinks) {
            $stmt->execute([$chave_loja, $nome_loja, $onlylinks]);
        }
    }

    echo "Dados inseridos com sucesso!<br>";

    // Agora fazemos o UPDATE com JOIN
    $stmt = $pdo->prepare("
        UPDATE tabela_destino td
        JOIN tabela_b_links bl ON td.CHAVE_LOJA = bl.CHAVE_LOJA
        SET bl.B_LINK = td.ONLYLINKS, 
            bl.data_att = NOW()
        WHERE bl.B_LINK IS NULL
    ");
    $stmt->execute();

    echo "Dados atualizados com sucesso!<br>";
}

// Consulta ao banco para exibir os dados
$stmt = $pdo->query("SELECT * FROM tabela_destino");
$dados = $stmt->fetchAll();

// Capturar a saída na variável
ob_start();

// Exibir os dados da tabela no terminal
echo "CHAVE_LOJA | NOME_LOJA | ONLYLINKS\n";
echo str_repeat("-", 50) . "\n";

$chave_loja_array = [];
$nome_loja_array = [];
$onlylinks_array = [];

foreach ($dados as $linha) {
    echo $linha['CHAVE_LOJA'] . " | " . $linha['NOME_LOJA'] . " | " . $linha['ONLYLINKS'] . "\n";

    // Armazena os dados nos arrays para o JavaScript
    $chave_loja_array[] = $linha['CHAVE_LOJA'];
    $nome_loja_array[] = $linha['NOME_LOJA'];
    $onlylinks_array[] = $linha['ONLYLINKS'];
}

// Armazena o resultado da query em uma variável
$tabela_output = ob_get_clean();

// Exibe a tabela capturada
echo "<pre>$tabela_output</pre>";
?>

<script>
    let chave_loja = <?php echo json_encode($chave_loja_array); ?>;
    let nome_loja = <?php echo json_encode($nome_loja_array); ?>;
    let onlylinks = <?php echo json_encode($onlylinks_array); ?>;

    let dadosParaAPI = {
        chave_loja: chave_loja,
        nome_loja: nome_loja,
        onlylinks: onlylinks
    };

    console.log('Dados prontos para envio à API:', dadosParaAPI);

    // Enviar para a API (exemplo usando fetch)
    fetch('https://sua-api.com/endpoint', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(dadosParaAPI)
    })
    .then(response => response.json())
    .then(data => console.log('Resposta da API:', data))
    .catch(error => console.error('Erro ao enviar dados:', error));
</script>
