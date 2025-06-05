<?php

require 'config/database.php';
require '../../vendor/autoload.php'; // Carrega o Composer para usar o Dompdf

use Dompdf\Dompdf;
use Dompdf\Options;

// Verifica se o ID da tarefa foi passado via GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID da tarefa inválido.";
    exit;
}

$tarefa_id = $_GET['id'];

try {
    // Busca os dados da tarefa
    $stmt_tarefa = $pdo->prepare("
        SELECT
            t.tema,
            t.descricao,
            t.localizacao,
            t.data_alteracao,
            u.nome AS usuario_alteracao,
            GROUP_CONCAT(DISTINCT r.resposta SEPARATOR '; ') AS respostas,
            GROUP_CONCAT(DISTINCT f.nome_arquivo SEPARATOR '; ') AS fotos
        FROM tarefas t
        LEFT JOIN usuarios u ON t.usuario_alteracao_id = u.id
        LEFT JOIN respostas_tarefas rt ON t.id = rt.tarefa_id
        LEFT JOIN perguntas r ON rt.pergunta_id = r.id
        LEFT JOIN fotos_tarefas ft ON t.id = ft.tarefa_id
        LEFT JOIN arquivos f ON ft.arquivo_id = f.id
        WHERE t.id = :tarefa_id
        GROUP BY t.id
    ");
    $stmt_tarefa->bindParam(':tarefa_id', $tarefa_id, PDO::PARAM_INT);
    $stmt_tarefa->execute();
    $tarefa = $stmt_tarefa->fetch(PDO::FETCH_ASSOC);

    if (!$tarefa) {
        echo "Tarefa não encontrada.";
        exit;
    }

    // Configurações do Dompdf
    $options = new Options();
    $options->set('defaultFont', 'dejavusans');
    $dompdf = new Dompdf($options);

    // Conteúdo HTML do PDF
    $html = '<!DOCTYPE html>';
    $html .= '<html lang="pt">';
    $html .= '<head>';
    $html .= '<meta charset="UTF-8">';
    $html .= '<title>Detalhes da Tarefa - ' . htmlspecialchars($tarefa['tema']) . '</title>';
    $html .= '<style>';
    $html .= 'body { font-family: dejavusans, sans-serif; }';
    $html .= '.container { width: 80%; margin: 0 auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }';
    $html .= 'h1, h2 { color: #333; }';
    $html .= 'p { line-height: 1.5; }';
    $html .= '.info { margin-bottom: 15px; }';
    $html .= '.label { font-weight: bold; margin-right: 5px; }';
    $html .= '.fotos-container { margin-top: 20px; }';
    $html .= '.foto { max-width: 200px; height: auto; margin-right: 10px; margin-bottom: 10px; border: 1px solid #eee; padding: 5px; }';
    $html .= '</style>';
    $html .= '</head>';
    $html .= '<body>';
    $html .= '<div class="container">';
    $html .= '<h1>Detalhes da Tarefa</h1>';
    $html .= '<div class="info">';
    $html .= '<p><span class="label">Tema:</span> ' . htmlspecialchars($tarefa['tema']) . '</p>';
    $html .= '<p><span class="label">Local:</span> ' . htmlspecialchars($tarefa['localizacao']) . '</p>';
    $html .= '<p><span class="label">Descrição:</span> ' . nl2br(htmlspecialchars($tarefa['descricao'])) . '</p>';
    $html .= '<p><span class="label">Data de Alteração:</span> ' . htmlspecialchars($tarefa['data_alteracao']) . '</p>';
    $html .= '<p><span class="label">Usuário da Alteração:</span> ' . htmlspecialchars($tarefa['usuario_alteracao']) . '</p>';

    // Exibe as respostas, se houver
    if (!empty($tarefa['respostas'])) {
        $respostas_array = explode('; ', $tarefa['respostas']);
        $html .= '<h2>Respostas:</h2>';
        $html .= '<ul>';
        foreach ($respostas_array as $resposta) {
            $html .= '<li>' . htmlspecialchars($resposta) . '</li>';
        }
        $html .= '</ul>';
    }

    // Exibe as fotos anexadas, se houver
    if (!empty($tarefa['fotos'])) {
        $fotos_array = explode('; ', $tarefa['fotos']);
        $html .= '<div class="fotos-container">';
        $html .= '<h2>Fotos Anexadas:</h2>';
        foreach ($fotos_array as $nome_arquivo) {
            $caminho_foto = '../../uploads/' . $nome_arquivo; // Ajuste o caminho conforme a sua estrutura
            if (file_exists($caminho_foto)) {
                $base64 = base64_encode(file_get_contents($caminho_foto));
                $image_src = 'data:image/png;base64,' . $base64; // Assumindo que as fotos são PNG
                $html .= '<img src="' . $image_src . '" alt="' . htmlspecialchars($nome_arquivo) . '" class="foto">';
            } else {
                $html .= '<p>Foto não encontrada: ' . htmlspecialchars($nome_arquivo) . '</p>';
            }
        }
        $html .= '</div>';
    }

    $html .= '</div>';
    $html .= '</div>';
    $html .= '</body>';
    $html .= '</html>';

    $dompdf->loadHtml($html);

    // (Opcional) Define o tamanho do papel e a orientação
    $dompdf->setPaper('A4', 'portrait');

    // Renderiza o PDF
    $dompdf->render();

    // Envia o PDF para o navegador para download
    $dompdf->stream('detalhes_tarefa_' . $tarefa_id . '.pdf', ['Attachment' => true]);

} catch (PDOException $e) {
    echo "Erro ao buscar dados da tarefa: " . $e->getMessage();
}

?>