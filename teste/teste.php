<?php
require 'vendor/autoload.php'; // Carregar o PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

// Configuração do banco de dados
$host = 'localhost';
$dbname = 'backup_certificados';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("sqlsrv:Server=$host;Database=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}

// Caminho do arquivo Excel
$arquivo = 'F:\\Certificado de Cursos\\teste\\BASE_LINK.xlsx';

if (!file_exists($arquivo)) {
    die("Arquivo Excel não encontrado.");
}

// Carrega o arquivo Excel
$spreadsheet = IOFactory::load($arquivo);
$sheet = $spreadsheet->getActiveSheet();
$totalLinhas = $sheet->getHighestRow();

$batchSize = 500; // Inserção em lotes
$valores = [];

for ($i = 2; $i <= $totalLinhas; $i++) { // Começamos no índice 2 para ignorar o cabeçalho
    $chaveLoja = $sheet->getCell("A$i")->getValue();
    $nomeLoja = $sheet->getCell("B$i")->getValue();
    $onlyLinks = $sheet->getCell("C$i")->getValue();

    $valores[] = "('$chaveLoja', '$nomeLoja', '$onlyLinks')";

    // Se atingiu o tamanho do lote ou for a última linha, insere no banco
    if (count($valores) >= $batchSize || $i == $totalLinhas) {
        $sql = "INSERT INTO tabela_destino (CHAVE_LOJA, NOME_LOJA, ONLYLINKS) VALUES " . implode(", ", $valores);
        $pdo->exec($sql);
        $valores = []; // Limpa o array para o próximo lote
    }
}

echo "Dados inseridos com sucesso na tabela_destino!<br>";

// MERGE para atualizar ou inserir na MESU.TB_ARQUIVO
$sqlMerge = "
    MERGE INTO MESU.TB_ARQUIVO AS TARGET
    USING tabela_destino AS SOURCE
    ON TARGET.CHAVE_LOJA = SOURCE.CHAVE_LOJA
    WHEN MATCHED THEN
        UPDATE SET TARGET.ONLYLINKS = SOURCE.ONLYLINKS
    WHEN NOT MATCHED THEN
        INSERT (CHAVE_LOJA, NOME_LOJA, ONLYLINKS) 
        VALUES (SOURCE.CHAVE_LOJA, SOURCE.NOME_LOJA, SOURCE.ONLYLINKS);
";

$pdo->exec($sqlMerge);
echo "MERGE realizado com sucesso!";
?>
