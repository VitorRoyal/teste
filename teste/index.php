<?php
// Caminho do diretório principal
$diretorio = 'F:\Certificado de Cursos';

// Função para buscar arquivos recursivamente, mas ignorando pastas como .git
function buscarArquivos($diretorio) {
    $arquivos = [];
    
    // Verifica se o diretório existe
    if (is_dir($diretorio)) {
        // Obtém todos os arquivos e subdiretórios
        $itens = scandir($diretorio);

        // Filtra os itens para remover '.' e '..' e outros indesejados, como '.git'
        $itens = array_diff($itens, array('.', '..', '.git'));

        // Percorre os itens do diretório
        foreach ($itens as $item) {
            $caminho_item = $diretorio . '/' . $item;

            // Se for um arquivo, adiciona na lista
            if (is_file($caminho_item)) {
                $arquivos[] = $caminho_item;
            }
            // Se for um diretório, chama a função recursivamente (ignora .git)
            elseif (is_dir($caminho_item)) {
                $arquivos = array_merge($arquivos, buscarArquivos($caminho_item));
            }
        }
    }

    return $arquivos;
}

// Função de comparação para ordenar os arquivos por data de modificação (mais recente primeiro)
function comparar_modificacao($a, $b) {
    return filemtime($b) - filemtime($a);
}

// Busca os arquivos no diretório e subdiretórios
$arquivos = buscarArquivos($diretorio);

// Verifica se existem arquivos encontrados
if (count($arquivos) > 0) {
    // Ordena os arquivos por data de modificação (último modificado por primeiro)
    usort($arquivos, 'comparar_modificacao');

    // Pega o último arquivo adicionado (o primeiro na lista após ordenar)
    $ultimo_arquivo = $arquivos[0];

    // Variável para armazenar arquivos já encontrados
    if (!isset($arquivos_encontrados)) {
        $arquivos_encontrados = [];
    }

    // Verifica se o arquivo já foi encontrado anteriormente
    if (in_array($ultimo_arquivo, $arquivos_encontrados)) {
        // Se o arquivo já foi encontrado, exibe mensagem
        echo "Arquivo já obtido: " . $ultimo_arquivo . "<br>";
    } else {
        // Se for a primeira vez que o arquivo é encontrado, armazena e exibe a mensagem
        echo "Primeira vez encontrando o arquivo: " . $ultimo_arquivo . "<br>";
        // Armazena o arquivo encontrado
        $arquivos_encontrados[] = $ultimo_arquivo;
    }
} else {
    echo "Nenhum arquivo encontrado no diretório.<br>";
}
?>
