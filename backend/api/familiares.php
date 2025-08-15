<?php
// /api/familiares.php (Arquivo Unificado)

// 1. CABEÇALHOS DE SEGURANÇA E CORS
// ===================================================
$frontend_url = "http://localhost:3000"; 
header("Access-Control-Allow-Origin: " . $frontend_url );
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200 );
    exit();
}

header("Content-Type: application/json; charset=UTF-8");

// 2. INICIALIZAÇÃO E DEPENDÊNCIAS
// ===================================================
require_once __DIR__ . '/../config/db.php'; 

// 3. LÓGICA PRINCIPAL DA API
// ===================================================
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $usuario_id = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT);
            if (!$usuario_id) {
                http_response_code(400 );
                echo json_encode(['erro' => 'ID do usuário é obrigatório.']);
                exit;
            }

            // CORREÇÃO: Query simplificada para ler os totais diretamente da tabela.
            // É mais rápido e resolve o problema de exibição.
            $sql = "SELECT id, nome, foto, salario, limiteCartao, limiteCheque FROM familiares WHERE usuario_id = :uid ORDER BY nome ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['uid' => $usuario_id]);
            $familiares = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($familiares);
            break;

        case 'POST':
            // Lógica unificada para CRIAR e EDITAR.
            // O frontend agora envia 'multipart/form-data' por causa da foto.
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $usuario_id = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
            $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
            $salario = filter_input(INPUT_POST, 'salario', FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
            $limiteCartao = filter_input(INPUT_POST, 'limiteCartao', FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
            $limiteCheque = filter_input(INPUT_POST, 'limiteCheque', FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);

            if (!$usuario_id || !$nome) {
                http_response_code(400 );
                echo json_encode(['erro' => 'Dados obrigatórios (usuário e nome) não fornecidos.']);
                exit;
            }

            $caminhoFoto = filter_input(INPUT_POST, 'foto_existente', FILTER_SANITIZE_STRING); // Pega a foto antiga, se houver

            // Lógica de Upload: só executa se um novo arquivo for enviado
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
                $dirUpload = __DIR__ . '/../uploads';
                if (!is_dir($dirUpload)) mkdir($dirUpload, 0775, true);
                
                $nomeArquivo = uniqid('familiar_') . '-' . basename($_FILES['foto']['name']);
                $caminhoCompleto = $dirUpload . '/' . $nomeArquivo;

                if (move_uploaded_file($_FILES['foto']['tmp_name'], $caminhoCompleto)) {
                    // Se o upload deu certo, apaga a foto antiga (se existir e não for a padrão)
                    if ($caminhoFoto && $caminhoFoto !== 'default-avatar.png' && file_exists($dirUpload . '/' . $caminhoFoto)) {
                        unlink($dirUpload . '/' . $caminhoFoto);
                    }
                    $caminhoFoto = $nomeArquivo; // Atualiza para o nome do novo arquivo
                }
            }

            if ($id) { // ATUALIZAÇÃO
                $sql = "UPDATE familiares SET nome = :nome, salario = :salario, limiteCartao = :limiteCartao, limiteCheque = :limiteCheque, foto = :foto WHERE id = :id AND usuario_id = :usuario_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':nome' => $nome, ':salario' => $salario, ':limiteCartao' => $limiteCartao, ':limiteCheque' => $limiteCheque, ':foto' => $caminhoFoto, ':id' => $id, ':usuario_id' => $usuario_id]);
                echo json_encode(['sucesso' => true, 'mensagem' => 'Familiar atualizado.']);
            } else { // CRIAÇÃO
                $sql = "INSERT INTO familiares (usuario_id, nome, salario, limiteCartao, limiteCheque, foto) VALUES (:uid, :nome, :salario, :limiteCartao, :limiteCheque, :foto)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':uid' => $usuario_id, ':nome' => $nome, ':salario' => $salario, ':limiteCartao' => $limiteCartao, ':limiteCheque' => $limiteCheque, ':foto' => $caminhoFoto ?? 'default-avatar.png']);
                http_response_code(201 );
                echo json_encode(['sucesso' => true, 'id' => $pdo->lastInsertId()]);
            }
            break;

        case 'DELETE':
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            $usuario_id = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT);

            if (!$id || !$usuario_id) {
                http_response_code(400 );
                echo json_encode(['erro' => 'ID do familiar e do usuário são obrigatórios.']);
                exit;
            }
            
            // Antes de apagar o registro, pega o nome do arquivo da foto para apagar o arquivo físico
            $stmtFoto = $pdo->prepare("SELECT foto FROM familiares WHERE id = :id AND usuario_id = :usuario_id");
            $stmtFoto->execute([':id' => $id, ':usuario_id' => $usuario_id]);
            $fotoParaApagar = $stmtFoto->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM familiares WHERE id = :id AND usuario_id = :usuario_id");
            $stmt->execute([':id' => $id, ':usuario_id' => $usuario_id]);

            if ($stmt->rowCount() > 0) {
                // Se o registro foi apagado do banco, apaga o arquivo da foto
                if ($fotoParaApagar && $fotoParaApagar !== 'default-avatar.png') {
                    $caminhoArquivoFoto = __DIR__ . '/../uploads/' . $fotoParaApagar;
                    if (file_exists($caminhoArquivoFoto)) {
                        unlink($caminhoArquivoFoto);
                    }
                }
                echo json_encode(['sucesso' => true, 'mensagem' => 'Familiar excluído.']);
            } else {
                http_response_code(404 );
                echo json_encode(['erro' => 'Familiar não encontrado ou não pertence a este usuário.']);
            }
            break;

        default:
            http_response_code(405 );
            echo json_encode(['erro' => 'Método não permitido.']);
            break;
    }
} catch (\Throwable $e) {
    error_log("Erro na API de familiares: " . $e->getMessage());
    http_response_code(500 );
    echo json_encode(['erro' => 'Ocorreu um erro crítico no servidor.', 'detalhes' => $e->getMessage()]);
}
?>
