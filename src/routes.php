<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
    $this->logger->info("Slim-Skeleton '/' route");
    return $this->renderer->render($response, 'index.phtml', $args);
});

$app->get("/books/", function (Request $request, Response $response) {
    $sql = "SELECT * FROM books";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson(["status" => "success", "data" => $result], 200);
});

$app->get("/books/{id}", function (Request $request, Response $response, $args) {
    $id = $args["id"];
    $sql = "SELECT * FROM books WHERE book_id=:id";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([":id" => $id]);
    $result = $stmt->fetch();
    return $response->withJson(["status" => "success", "data" => $result], 200);
});

$app->get("/books/search/", function (Request $request, Response $response, $args) {
    $keyword = $request->getQueryParam("keyword");
    $sql = "SELECT * FROM books WHERE title LIKE '%$keyword%' OR description LIKE '%$keyword%' OR author LIKE '%$keyword%'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([":id" => $id]);
    $result = $stmt->fetchAll();
    return $response->withJson(["status" => "success", "data" => $result], 200);
});

$app->post('/books/', function (Request $request, Response $response, $args) {
    $error = 0;
    $data = array();
    $uploadedFile = '';
    $filename = '';
    $insertString = '';
    $insertStringFinal = '';

    $requestbody = $request->getParsedBody();
    $uploadedFiles = $request->getUploadedFiles();

    if ($requestbody['title'] == '') {
        $error = 1;
        return $response->withJson(["status" => "failed", "data" => "Please enter title", "code" => 100]);
    }
    if ($error == 0) {
        if (isset($requestbody) && $requestbody != false) {
            foreach ($requestbody as $field => $value) {
                $insertString .= "`$field`='" . addslashes($value) . "', ";
            }
            $insertStringFinal = substr($insertString, 0, -2);
        }
        $sql = "INSERT INTO books SET $insertStringFinal";
        $data = array(
            'title' => $requestbody['title'],
            'author' => $requestbody['author'],
            'description' => $requestbody['description'],
        );
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute()) {
            $lastInsertId = $this->db->lastInsertId();
            $data['id'] = $lastInsertId;
            if (isset($uploadedFiles['cover']) && $uploadedFiles['cover'] != "") {
                $uploadedFile = $uploadedFiles['cover'];
            }
            if ($uploadedFile != '' && $uploadedFile->getError() === UPLOAD_ERR_OK) {
                $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                $filename = sprintf(time() . '_' . '%s.%0.8s', $lastInsertId, $extension);
                $directory = $this->get('settings')['upload_directory'];
                $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
                $data['cover'] = $filename;
                $sql = "UPDATE books SET cover = '$filename' WHERE book_id = $lastInsertId";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $url = $request->getUri()->getBaseUrl() . "/uploads/" . $filename;
                $data['url'] = $url;
            }
            return $response->withJson(["status" => "success", "data" => $data], 200);
        }
        return $response->withJson(["status" => "failed", "data" => "0"], 200);
    }

});

$app->post('/books/{id}', function (Request $request, Response $response, $args) {

    $uploadedFile = '';
    $filename = '';
    $sqlUpdateString = '';
    $sqlUpdateStringFinal = '';
    $error = 0;

    $requestbody = $request->getParsedBody();
    $uploadedFiles = $request->getUploadedFiles();
    if ($requestbody['title'] == '') {
        $error = 1;
        return $response->withJson(["status" => "failed", "data" => "Please enter title", "code" => 100]);
    }
    if ($error == 0) {
        if (isset($requestbody) && $requestbody != false) {
            foreach ($requestbody as $field => $value) {
                $sqlUpdateString .= "`$field`='" . addslashes($value) . "', ";
            }
            $sqlUpdateStringFinal = substr($sqlUpdateString, 0, -2);
        }
        $sql = "UPDATE books SET $sqlUpdateStringFinal WHERE book_id = " . $args['id'];
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = array(
            "id" => $args["id"],
            "title" => $requestbody["title"],
            "author" => $requestbody["author"],
            "description" => $requestbody["description"],
        );
        if (isset($uploadedFiles['cover']) && $uploadedFiles['cover'] != "") {
            $uploadedFile = $uploadedFiles['cover'];
            if ($uploadedFile != '' && $uploadedFile->getError() === UPLOAD_ERR_OK) {
                $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                $filename = sprintf(time() . '_' . '%s.%0.8s', $args["id"], $extension);
                $directory = $this->get('settings')['upload_directory'];
                $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
                $sql = "UPDATE books SET $sqlUpdateStringFinal,cover='$filename' WHERE book_id=".$args['id'];
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $url = $request->getUri()->getBaseUrl() . "/uploads/" . $filename;
                $data['cover'] = $filename;
                $data['url'] = $url;
            }
        }
        
        return $response->withJson(["status" => "success", "data" => $data], 200);
    }
});

$app->delete("/books/{id}", function (Request $request, Response $response, $args) {
    $id = $args["id"];
    $sql = "DELETE FROM books WHERE book_id=:id";
    $stmt = $this->db->prepare($sql);
    $data = [
        ":id" => $id,
    ];
    if ($stmt->execute($data)) {
        return $response->withJson(["status" => "success", "data" => "1"], 200);
    }
    return $response->withJson(["status" => "failed", "data" => "0"], 200);
});
