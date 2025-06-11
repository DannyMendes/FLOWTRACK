<?php
require 'config/database.php';
session_start();


// Verifica se a req foi POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Loga o conteúdo de $_FILES para ver se o PHP recebeu os arquivos
    error_log("Conteúdo de \$_FILES na requisição POST: " . print_r($_FILES, true));

    // Verifica se o ID da tarefa foi enviado e é um número
    if (isset($_POST['id']) && is_numeric($_POST['id'])) {
        // Filtra e sanitiza o ID da tarefa para segurança
        $tarefaId = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);

        // Obtém e sanitiza os outros dados do formulário
        $statusId = filter_input(INPUT_POST, 'status_id', FILTER_SANITIZE_NUMBER_INT);
        $matriculaCarrinha = filter_input(INPUT_POST, 'matricula_carrinha', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $motorista = filter_input(INPUT_POST, 'motorista', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        // Verifica o checkbox, se marcado, valor é 1, senão 0
        $verificacaoMaterial = isset($_POST['verificacao_material']) ? 1 : 0;
        $descricaoTrabalho = filter_input(INPUT_POST, 'descricao_trabalho', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $respostaPergunta1 = filter_input(INPUT_POST, 'resposta_pergunta1', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $respostaPergunta2 = filter_input(INPUT_POST, 'resposta_pergunta2', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $acao = filter_input(INPUT_POST, 'acao', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        // Obtém o ID do usuário logado da sessão.
        $usuarioLogadoId = $_SESSION['id_usuario'] ?? null;


        // Se o ID nao logado
        if ($usuarioLogadoId === null) {
            echo "usuario_nao_logado";
            exit();
        }

        // Verifica se os dados essenciais (tarefaId, statusId, acao) estão presentes
        if ($tarefaId && $statusId && $acao === 'atualizar_status') {
            try {
                // Inicia uma transação no BD
                $pdo->beginTransaction();

                // Buscar o status atual da tarefa e o ID criador
                $stmt_get_tarefa = $pdo->prepare("SELECT t.status, t.usuario_criacao AS usuario_criador_id FROM tarefas t WHERE t.id = :id");
                $stmt_get_tarefa->bindParam(':id', $tarefaId, PDO::PARAM_INT);
                $stmt_get_tarefa->execute();
                $tarefa_atual = $stmt_get_tarefa->fetch(PDO::FETCH_ASSOC);
                $statusAtualId = $tarefa_atual['status'] ?? null;
                $usuarioCriadorId = $tarefa_atual['usuario_criacao'] ?? null;

                // Buscar a ordem dos status atual e do status alvo
                $stmt_get_ordem = $pdo->prepare("SELECT ordem FROM status_tarefas WHERE id = ?");
                $stmt_get_ordem->execute([$statusAtualId]);
                $statusAtualOrdemResult = $stmt_get_ordem->fetch(PDO::FETCH_ASSOC);
                $statusAtualOrdem = $statusAtualOrdemResult['ordem'] ?? null;

                $stmt_get_ordem->execute([$statusId]);
                $statusAlvoOrdemResult = $stmt_get_ordem->fetch(PDO::FETCH_ASSOC);
                $statusAlvoOrdem = $statusAlvoOrdemResult['ordem'] ?? null;

                $podeAlterar = false;

                // Permitir salvar o mesmo status -info adicionais
                if ($statusAtualId === $statusId) {
                    $podeAlterar = true;
                }
                // Permitir status PENDENTE (ID 11) vá direto para INICIADO (ID 13)
                elseif ($statusAtualId == 11 && $statusId == 13) {
                    $podeAlterar = true;
                }
                // Permitir avançar para o próximo status na ordem
                elseif ($statusAlvoOrdem === $statusAtualOrdem + 1) {
                    $podeAlterar = true;
                }

                // senao aborta
                if (!$podeAlterar) {
                    echo "alteracao_nao_permitida";
                    $pdo->rollBack(); 
                    exit();
                }

                // Buscar o nome do status alvo 
                $stmt_get_status_nome = $pdo->prepare("SELECT nome FROM status_tarefas WHERE id = :id");
                $stmt_get_status_nome->bindParam(':id', $statusId, PDO::PARAM_INT);
                $stmt_get_status_nome->execute();
                $statusAlvoNomeResult = $stmt_get_status_nome->fetch(PDO::FETCH_ASSOC);
                $statusAlvoNome = $statusAlvoNomeResult['nome'] ?? '';

                // Inserir uma nova entrada no histórico de status da tarefa
                $stmt_historico = $pdo->prepare("
                    INSERT INTO historico_status (
                        tarefa_id, status_id, usuario_id, data_hora,
                        matricula_carrinha, motorista, verificacao_material,
                        descricao_trabalho, resposta_pergunta1, resposta_pergunta2
                    ) VALUES (
                        :tarefa_id, :status_id, :usuario_id, NOW(),
                        :matricula, :motorista, :material_ok,
                        :descricao, :pergunta1, :pergunta2
                    )
                ");
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

                // Atualizar o status atual da tarefa
                $stmt_update_tarefa = $pdo->prepare("UPDATE tarefas SET status = :status_id WHERE id = :id");
                $stmt_update_tarefa->bindParam(':status_id', $statusId, PDO::PARAM_INT);
                $stmt_update_tarefa->bindParam(':id', $tarefaId, PDO::PARAM_INT);
                $stmt_update_tarefa->execute();

                // tipo de acesso do usuário logado
                $stmt_get_acesso = $pdo->prepare("SELECT tipo_acesso FROM usuarios WHERE id = :id_usuario");
                $stmt_get_acesso->bindParam(':id_usuario', $usuarioLogadoId, PDO::PARAM_INT);
                $stmt_get_acesso->execute();
                $tipoAcessoUsuario = $stmt_get_acesso->fetchColumn();

                // Ver se o usuário logado é = o q iniciou a tarefa ou se é 'Administrador'
                if ($usuarioLogadoId !== $usuarioInicioId && $tipoAcessoUsuario !== 'Administrador') {
                    echo "permissao_negada";
                    $pdo->rollBack();
                    exit();
                }
                if ($statusAlvoNome === 'FINALIZADO') {
                    $gestorAcessoTotal = false; 
                    if ($usuarioLogadoId !== $usuarioCriadorId && !$gestorAcessoTotal) {
                        echo "permissao_negada";
                        $pdo->rollBack();
                        exit();
                    }

                    // Validações de campos obrigatórios para FINALIZADO
                    if (empty($descricaoTrabalho)) {
                        echo "descricao_final_obrigatoria";
                        $pdo->rollBack();
                        exit();
                    }

                    // Lógica de upload e armazenamento de fotos
                    if (!empty($_FILES['fotos']['name'][0])) {
                       $pastaDestinoFisico = __DIR__ . '/../../uploads/'; 
                    
                        if (!is_dir($pastaDestinoFisico)) {
                            mkdir($pastaDestinoFisico, 0777, true);
                            error_log("Diretório de uploads criado: " . $pastaDestinoFisico); // Loga a criação
                        } else {
                            error_log("Diretório de uploads já existe: " . $pastaDestinoFisico); // Loga que já existe
                        }

                        foreach ($_FILES['fotos']['tmp_name'] as $key => $tmpName) {
                            $nomeOriginalArquivo = $_FILES['fotos']['name'][$key];
                            // Gera um nome único para o arquivo
                            $nomeUnico = uniqid() . '_' . basename($nomeOriginalArquivo);

                            // Caminho físico completo onde o arquivo será salvo no servidor
                            $caminhoArquivoFisico = $pastaDestinoFisico . $nomeUnico;
                            $caminhoArquivoWeb = '/FLOWTRACK/uploads/' . $nomeUnico; 

                            // Tenta mover o arquivo temporário para o destino final
                            if (move_uploaded_file($tmpName, $caminhoArquivoFisico)) {
                                error_log("Arquivo movido com sucesso: " . $caminhoArquivoFisico); // Loga o sucesso
                                // Insere o caminho WEB (URL) no banco de dados
                                $stmtFoto = $pdo->prepare("INSERT INTO fotos_trabalho (historico_status_id, caminho_arquivo) VALUES (:historico_id, :caminho)");
                                $stmtFoto->bindParam(':historico_id', $historicoStatusId, PDO::PARAM_INT);
                                $stmtFoto->bindParam(':caminho', $caminhoArquivoWeb, PDO::PARAM_STR); 
                                $stmtFoto->execute();
                            } else {
                                // Erro ao mover o arquivo, loga e reverte a transação
                                $error_code = $_FILES['fotos']['error'][$key];
                                $php_error_message = '';
                                switch ($error_code) {
                                    case UPLOAD_ERR_INI_SIZE:
                                        $php_error_message = 'O arquivo excede o limite de tamanho definido no php.ini.';
                                        break;
                                    case UPLOAD_ERR_FORM_SIZE:
                                        $php_error_message = 'O arquivo excede o limite de tamanho definido no formulário HTML.';
                                        break;
                                    case UPLOAD_ERR_PARTIAL:
                                        $php_error_message = 'O upload do arquivo foi feito apenas parcialmente.';
                                        break;
                                    case UPLOAD_ERR_NO_FILE:
                                        $php_error_message = 'Nenhum arquivo foi enviado.';
                                        break;
                                    case UPLOAD_ERR_NO_TMP_DIR:
                                        $php_error_message = 'Faltando uma pasta temporária.';
                                        break;
                                    case UPLOAD_ERR_CANT_WRITE:
                                        $php_error_message = 'Falha ao gravar o arquivo em disco.';
                                        break;
                                    case UPLOAD_ERR_EXTENSION:
                                        $php_error_message = 'Uma extensão do PHP interrompeu o upload do arquivo.';
                                        break;
                                    default:
                                        $php_error_message = 'Erro desconhecido no upload.';
                                        break;
                                }
                                error_log("Erro ao mover o arquivo '{$nomeOriginalArquivo}' de '{$tmpName}' para '{$caminhoArquivoFisico}'. Código de erro PHP: {$error_code}. Mensagem: {$php_error_message}");
                                echo "erro_upload_foto";
                                $pdo->rollBack();
                                exit();
                            }
                        }
                    } else {
                        error_log("Nenhuma foto foi enviada para o status FINALIZADO.");
                    }
                }

                // Confirma todas as operações da transação no bd
                $pdo->commit();
                echo "success"; // Retorna sucesso para o frontend

            } catch (PDOException $e) {
                // Em caso de erro
                $pdo->rollBack();
                error_log("Erro no banco de dados ao atualizar status: " . $e->getMessage());
                
                echo "erro_banco"; // Retorna um erro genérico para o frontend
            } catch (Exception $e) {
                // Captura outras exceções que não sejam do PDO
                $pdo->rollBack();
                error_log("Erro geral ao atualizar status: " . $e->getMessage());
                echo "erro_geral";
            }
        } else {
            echo "dados_invalidos"; 
        }
    } else {
        echo "id_invalido"; 
    }
} else {
    echo "metodo_invalido"; // Requisição não foi POST
}
?>
