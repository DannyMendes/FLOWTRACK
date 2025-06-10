<?php
require 'config/database.php';
session_start();
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifique se o ID da tarefa foi enviado
    if (isset($_POST['id']) && is_numeric($_POST['id'])) {
        $tarefaId = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);

        // Obtenha os outros dados do formulário
        $statusId = filter_input(INPUT_POST, 'status_id', FILTER_SANITIZE_NUMBER_INT);
        $matriculaCarrinha = filter_input(INPUT_POST, 'matricula_carrinha', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $motorista = filter_input(INPUT_POST, 'motorista', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $verificacaoMaterial = isset($_POST['verificacao_material']) ? 1 : 0;
        $descricaoTrabalho = filter_input(INPUT_POST, 'descricao_trabalho', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $respostaPergunta1 = filter_input(INPUT_POST, 'resposta_pergunta1', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $respostaPergunta2 = filter_input(INPUT_POST, 'resposta_pergunta2', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $acao = filter_input(INPUT_POST, 'acao', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $usuarioLogadoId = $_SESSION['id_usuario'] ?? null; // Assume que o ID do usuário logado está na sessão

        // Verifique se o usuário está logado ANTES de qualquer operação no banco de dados
        if ($usuarioLogadoId === null) {
            echo "usuario_nao_logado";
            exit();
        }

        if ($tarefaId && $statusId && $acao === 'atualizar_status') {
            try {
                $pdo->beginTransaction();

                // Buscar o status atual e o ID do criador da tarefa
                $stmt_get_tarefa = $pdo->prepare("SELECT t.status, t.usuario_criacao AS usuario_criador_id FROM tarefas t WHERE t.id = :id");
                $stmt_get_tarefa->bindParam(':id', $tarefaId, PDO::PARAM_INT);
                $stmt_get_tarefa->execute();
                $tarefa_atual = $stmt_get_tarefa->fetch(PDO::FETCH_ASSOC);
                $statusAtualId = $tarefa_atual['status'] ?? null;
                $usuarioCriadorId = $tarefa_atual['usuario_criacao'] ?? null; // Correção aqui: usar o alias

                // Buscar a ordem dos status atual e alvo
                $stmt_get_ordem = $pdo->prepare("SELECT ordem FROM status_tarefas WHERE id = ?");
                $stmt_get_ordem->execute([$statusAtualId]);
                $statusAtualOrdemResult = $stmt_get_ordem->fetch(PDO::FETCH_ASSOC);
                $statusAtualOrdem = $statusAtualOrdemResult['ordem'] ?? null;

                $stmt_get_ordem->execute([$statusId]);
                $statusAlvoOrdemResult = $stmt_get_ordem->fetch(PDO::FETCH_ASSOC);
                $statusAlvoOrdem = $statusAlvoOrdemResult['ordem'] ?? null;

                $podeAlterar = false;

                // Permitir avançar para o próximo status na ordem
                if ($statusAlvoOrdem === $statusAtualOrdem + 1) {
                    $podeAlterar = true;
                } elseif ($statusAtualId === $statusId) {
                    $podeAlterar = true; // Permitir salvar mesmo status (para informações adicionais)
                }

                if ($podeAlterar) {
                    // Buscar o nome do status alvo para a lógica de fotos
                    $stmt_get_status_nome = $pdo->prepare("SELECT nome FROM status_tarefas WHERE id = :id");
                    $stmt_get_status_nome->bindParam(':id', $statusId, PDO::PARAM_INT);
                    $stmt_get_status_nome->execute();
                    $statusAlvoNomeResult = $stmt_get_status_nome->fetch(PDO::FETCH_ASSOC);
                    $statusAlvoNome = $statusAlvoNomeResult['nome'] ?? '';

                    // Inserir no histórico de status
                    $stmt_historico = $pdo->prepare("INSERT INTO historico_status (tarefa_id, status_id, usuario_id, data_hora, matricula_carrinha, motorista, verificacao_material, descricao_trabalho, resposta_pergunta1, resposta_pergunta2)
                                                    VALUES (:tarefa_id, :status_id, :usuario_id, NOW(), :matricula, :motorista, :material_ok, :descricao, :pergunta1, :pergunta2)");
                    $stmt_historico->bindParam(':tarefa_id', $tarefaId, PDO::PARAM_INT);
                    $stmt_historico->bindParam(':status_id', $statusId, PDO::PARAM_INT);
                    $stmt_historico->bindParam(':usuario_id', $usuarioLogadoId, PDO::PARAM_INT);
                    $stmt_historico->bindParam(':matricula', $matriculaCarrinha, PDO::PARAM_STR);
                    $stmt_historico->bindParam(':motorista', $motorista, PDO::PARAM_STR);
                    $stmt_historico->bindParam(':material_ok', $verificacaoMaterial, PDO::PARAM_INT);
                    $stmt_historico->bindParam(':descricao', $descricaoTrabalho, PDO::PARAM_STR);
                    $stmt_historico->bindParam(':pergunta1', $respostaPergunta1, PDO::PARAM_STR);
                    $stmt_historico->bindParam(':pergunta2', $respostaPergunta2, PDO::PARAM_STR);
                    $stmt_historico->execute();
                    $historicoStatusId = $pdo->lastInsertId();

                    // Atualizar o status atual da tarefa na tabela 'tarefas'
                    $stmt_update_tarefa = $pdo->prepare("UPDATE tarefas SET status = :status_id WHERE id = :id");
                    $stmt_update_tarefa->bindParam(':status_id', $statusId, PDO::PARAM_INT);
                    $stmt_update_tarefa->bindParam(':id', $tarefaId, PDO::PARAM_INT);
                    $stmt_update_tarefa->execute();

                    // Lógica para salvar fotos se o status for 'FINALIZADO'
                    if ($statusAlvoNome === 'FINALIZADO') {
                        // Verificar permissão para finalizar
                        $gestorAcessoTotal = false; // Adicione sua lógica real
                        if ($usuarioLogadoId !== $usuarioCriadorId && !$gestorAcessoTotal) {
                            echo "permissao_negada";
                            $pdo->rollBack();
                            exit();
                        }

                        if (empty($descricaoTrabalho)) {
                            echo "descricao_final_obrigatoria";
                            $pdo->rollBack();
                            exit();
                        }

                        if (!empty($_FILES['fotos']['name'][0])) {
                            $pastaDestino = '../uploads/';
                            foreach ($_FILES['fotos']['tmp_name'] as $key => $tmpName) {
                                $nomeArquivo = $_FILES['fotos']['name'][$key];
                                $caminhoArquivo = $pastaDestino . uniqid() . '_' . basename($nomeArquivo);
                                if (move_uploaded_file($tmpName, $caminhoArquivo)) {
                                    $stmtFoto = $pdo->prepare("INSERT INTO fotos_trabalho (historico_status_id, caminho_arquivo) VALUES (:historico_id, :caminho)");
                                    $stmtFoto->bindParam(':historico_id', $historicoStatusId, PDO::PARAM_INT);
                                    $stmtFoto->bindParam(':caminho', $caminhoArquivo, PDO::PARAM_STR);
                                    $stmtFoto->execute();
                                } else {
                                    error_log("Erro ao mover o arquivo " . $nomeArquivo);
                                    echo "erro_upload_foto";
                                    $pdo->rollBack();
                                    exit();
                                }
                            }
                        }
                    }

                    $pdo->commit();
                    echo "success";

                } else {
                    echo "alteracao_nao_permitida";
                }

            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Erro ao atualizar status: " . $e->getMessage());
                var_dump($e);
                echo "erro_banco";
            }
        } else {
            echo "dados_invalidos";
        }
    } else {
        echo "id_invalido";
    }
} else {
    echo "metodo_invalido";
}
?>