<?php
// Caminho do diretório principal
$diretorio = 'F:\Certificado de Cursos';

// Função para buscar apenas arquivos no diretório especificado (sem subdiretórios)
function buscarArquivos($diretorio) {
    $arquivos = [];
    
    // Verifica se o diretório existe
    if (is_dir($diretorio)) {
        // Obtém todos os arquivos e pastas no diretório
        $itens = scandir($diretorio);

        // Filtra apenas arquivos, removendo '.' e '..'
        foreach ($itens as $item) {
            $caminho_item = $diretorio . DIRECTORY_SEPARATOR . $item;
            if (is_file($caminho_item)) {
                $arquivos[] = $caminho_item;
            }
        }
    }
    
    return $arquivos;
}

// Função de comparação para ordenar os arquivos por data de modificação (mais recente primeiro)
function comparar_modificacao($a, $b) {
    return filemtime($b) - filemtime($a);
}

// Busca os arquivos no diretório (sem subdiretórios)
$arquivos = buscarArquivos($diretorio);

// Verifica se existem arquivos encontrados
if (!empty($arquivos)) {
    // Ordena os arquivos por data de modificação (mais recente primeiro)
    usort($arquivos, 'comparar_modificacao');
    
    // Pega o último arquivo modificado
    $ultimo_arquivo = $arquivos[0];
    
    echo "Último arquivo modificado: " . basename($ultimo_arquivo) . "<br>";
} else {
    echo "Nenhum arquivo encontrado no diretório.<br>";
}
?>
